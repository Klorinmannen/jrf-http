# OAS router — decisions

Branch: `oas-router`. Level: Medium. Goal: routes loaded from an OAS 3.0 file route,
validate and dispatch through the same `Router`, `Assertion` and `Dispatcher` as
hand-written routes.

## Decisions made by the user
| # | Topic | Decision |
|---|---|---|
| U1 | `required` default (query params, requestBody) | Follow the OAS spec: absent = `false` |
| U2 | YAML | Optional. `ext-yaml` in composer `suggest`; clear `RuntimeException` when missing; YAML test skipped without the extension; CI enables it |
| U3 | `OAS\Response`, `OAS\Route\Security` | Delete (recover from git when needed) |
| U4 | Public API | `Router::addOASRoutes(string $filePath): void`, calling `OAS\Document::load` |

## Decisions by default (say if you disagree)
| # | Topic | Decision | Why |
|---|---|---|---|
| D1 | Reuse | `OAS\Route\InputDefinition extends Definition` and fills its public definition arrays | Core `Assertion` validates OAS routes unchanged |
| D2 | Path syntax | OAS `{recipe_id}` is translated to core `{integer:recipe_id}` before `Route::create` | Today `DynamicPath` reads `recipe_id` as a type and never matches |
| D3 | Path param type source | `schema.type` of the `in: path` parameter, from path-level or operation-level `parameters` | Path-level declaration is the common OAS style |
| D4 | Conflicting path types across operations | `RuntimeException` at load | One `Path` per route; fail early |
| D5 | Undeclared path param | `RuntimeException` at load | OAS mandates declaring them |
| D6 | Type mapping | `integer`→`INTEGER`, `string`→`STRING`, `boolean`→`BOOL`; anything else → `RuntimeException` at load | `boolean` has no enum case; `number`/`array`/`object` have no pattern |
| D7 | Query params | `required: true` → `requiredQueryParamDefinitions`; otherwise → `optionalQueryParamDefinitions` | Same semantics as core |
| D8 | `in: header`, `in: cookie` | Ignored (not validated) | Core `Definition` has no slot for them; selective OAS scope (README) |
| D9 | Path-level parameters | Merged into every operation; operation-level wins on same `name`+`in` | OAS 3.0 rule |
| D10 | Non-verb keys under a path (`summary`, `description`, `parameters`, `servers`) | Skipped as operations | Today `Method::from('SUMMARY')` throws `ValueError` |
| D11 | `operationId` | `Recipe_Controller@getRecipe` → `Recipe\Controller`, `getRecipe`. No `@method` → HTTP verb (core default). Missing `operationId` → `RuntimeException` at load | Router cannot dispatch without a controller |
| D12 | Controller storage | `InputDefinition` holds the controller class; `OAS\Route::setup` builds the `Action` from it | Controller may differ per operation |
| D13 | Local `$ref` (`#/components/...`) | `RuntimeException` "local \$ref not supported" | Today it fails with an unclear file error |
| D14 | Route keys | `addOASRoutes` appends `array_values(Document::load(...))` | Avoid path-key collisions across files |

## Out of scope
Response contracts, security schemes, `servers`, request-body schema/content-type
validation, header/cookie validation, nested JSON-pointer `$ref`s, `number` types.

## Known follow-up
Merging `main` will raise a rename/delete conflict: `main` deleted `src/Http/OAS.php`
(`360de81`); this branch renamed it to `src/Http/OAS/Document.php`. Keep this branch's file.
