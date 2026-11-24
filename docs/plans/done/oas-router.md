# OAS router — task list

Spec: `docs/designs/done/oas-router.md`. Execution: native, TDD (red → green → commit).
Test namespace mirrors `src/`: `Projom\Tests\Unit\Http\OAS[\Route]`.
Run before each commit: `./vendor/bin/phpunit --testsuite=Unit`.
Commit format: imperative, lowercase, no period, no body, no trailer.

## Task 1 — OAS type mapping (D6)
- `OAS\Route\Parameter` stops extending the deleted class.
- `Parameter::toParameterType(string $oasType): ParameterType`
- Tests `tests/Unit/Http/OAS/Route/ParameterTest.php`:
  - `integer`→`INTEGER`, `string`→`STRING`, `boolean`→`BOOL` (data provider)
  - `number`, `array`, `object`, `''` → `RuntimeException`
- Commit: `map oas schema types to parameter types`

## Task 2 — normalize OAS parameters (D3, D7, D8, D9, U1)
- `Parameter::normalize(array $pathLevel, array $operationLevel): array`
  → `['path' => [name => ParameterType], 'requiredQuery' => [...], 'optionalQuery' => [...]]`
- Tests:
  - query `required: true` → `requiredQuery`; absent or `false` → `optionalQuery`
  - path param → `path`, always required
  - header/cookie params dropped
  - operation-level overrides path-level on same `name`+`in`
  - unsupported type → `RuntimeException`
- Commit: `normalize oas parameters into path and query definitions`

## Task 3 — requestBody default (U1)
- `Payload::normalize(array $requestBody): bool`, absent `required` → `false`
- Tests: `[]`→false; `{}` without `required`→false; `required: true`→true
- Commit: `follow oas default for required request body`

## Task 4 — operationId to controller (D11)
- `OAS\Controller::normalize(string $operationId): array` stays; add guard:
  empty → `RuntimeException`
- Tests: `Recipe_Controller@getRecipe`; `recipe_controller` (no `@`) → method `''`;
  `''` → `RuntimeException`
- Commit: `reject operations without operation id`

## Task 5 — InputDefinition fills core Definition (D1, D12)
- Finish the uncommitted work. Remove commented-out code and unused imports.
- `InputDefinition::createFromDetails(Method $method, array $details, array $pathLevelParameters = []): InputDefinition`
  calls `parent::__construct($method, $controllerMethod)`
- `InputDefinition::controller(): string`
- `InputDefinition::pathParameterTypes(): array`
- Tests `tests/Unit/Http/OAS/Route/InputDefinitionTest.php`:
  - required/optional query defs land in the core public arrays
  - `requiredPayload` set from requestBody
  - `controller()` and `method()` from operationId; `method()` falls back to verb
- Commit: `build oas input definitions on the core definition`

## Task 6 — OAS\Route builds its Action (D12)
- `OAS\Route::setup()` uses `controller()` + `method()`
- Test: route with a GET definition → `match()` + `process()` → `action()->get()`
  returns `[controller, method]`
- Commit: `create oas route actions from input definitions`

## Task 7 — path translation (D2, D4, D5)
- New `OAS\Path::translate(string $oasPath, array $pathParameterTypes): string`
- Tests `tests/Unit/Http/OAS/PathTest.php`:
  - `/recipes` unchanged
  - `/recipes/{recipe_id}` + `[recipe_id => INTEGER]` → `/recipes/{integer:recipe_id}`
  - two params in one path
  - undeclared param → `RuntimeException`
- Commit: `translate oas path templates to router paths`

## Task 8 — Document assembles routes (D4, D10, D13, U2)
- Rename `tests/Unit/Http/OASTest.php` → `tests/Unit/Http/OAS/DocumentTest.php`,
  use `Document::load`, add `operationId` to fixtures.
- `Document::load` order: resolve `$ref` → skip non-verb keys → build definitions
  → collect path types (conflict → throw) → `Path::translate` → `Route::create`.
- `decodeFile`: missing `yaml_parse` → `RuntimeException`.
- Tests (existing six, plus):
  - path-level `summary`/`parameters` keys do not throw
  - conflicting path-param types across verbs → `RuntimeException`
  - local `$ref` `#/components/x` → `RuntimeException`
  - YAML test: `#[RequiresPhpExtension('yaml')]`
- Commit: `assemble routable oas routes from documents`

## Task 9 — Router integration (U4, D14)
- `Router::addOASRoutes(string $filePath): void`
- Tests in `RouterTest`: OAS JSON fixture with
  `/recipes/{recipe_id}` (`integer`), GET `Recipe_Controller@getRecipe`, required query `page`:
  - `route()` on `/recipes/5?page=1` → `[controller, 'getRecipe']`
  - wrong verb → 405 path not reached (assert via `match` only; `route()` exits)
- Commit: `add oas routes to the router`

## Task 10 — clean-up and YAML wiring (U2, U3)
- Delete `OAS\Response`, `OAS\Route\Security`.
- `composer.json` `suggest`: `ext-yaml`. CI `setup-php` `extensions: yaml`.
- Commits: `remove unused oas response and security stubs`,
  `declare yaml as an optional extension`

## Final
- Full suite green; README example for `addOASRoutes`; final review.
