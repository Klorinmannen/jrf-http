# Upgrade to PHP 8.5

Status: open — decision needed (A or B). Process level: Light.

## Context
- `composer.json` requires `php >=8.2.0`; CI runs only PHP 8.2; README badge says `^8.2`.
- PHP 8.2 security support ends 2026-12-31.
- Released versions exist (`v1.0.1`), so raising the minimum PHP version is a breaking change.

## Verified on PHP 8.5.12 (2026-11-24)
- All 141 unit tests pass with `error_reporting=-1` and deprecations displayed (1 skip: the missing-yaml test).
- Every file in `src/` loads without warnings.
- `src/` uses no 8.5 deprecations (non-canonical casts, backticks, `__sleep`/`__wakeup`).
- Every locked dependency allows PHP `>=8.2` or lower; nothing blocks 8.5.

## Options
| | A. Support 8.5 | B. Require 8.5 |
|---|---|---|
| `composer.json` | stays `>=8.2.0` | `>=8.5` |
| CI | matrix 8.2–8.5 | 8.5 only |
| Users on 8.2–8.4 | unaffected | cannot install new versions |
| Version bump | patch/minor | major (v2.0.0) |
| Gain | proof that 8.5 works | newer language features (pipe `\|>`, `#[\NoDiscard]`, `array_first`, property hooks, `#[\Override]`); PHPUnit 12+ (needs 8.3+) |

Middle ground: require `>=8.3` (drop 8.2 at its end of life), CI matrix 8.3–8.5.

Recommendation: A now; B only if this library has no other users or a v2 is planned.

## Scope
- `composer.json`: `require.php` (B only).
- `.github/workflows/ci.yml`: PHP version(s) for `setup-php`, as a matrix for A.
- `README.md`: PHP version badge.
- `composer.lock`: `composer update --lock` so `platform.php` matches.

## Acceptance criteria
- CI is green on every PHP version in scope.
- The yaml-dependent tests run in CI on 8.5, not skipped.
- `composer.json`, the lock file's platform and the README badge state the same minimum version.

## Risks / unknowns (only CI can confirm)
- `ext-yaml` installs through `setup-php` on 8.5.
- The Psalm action handles 8.5 (it runs with `continue-on-error`, so it cannot fail the build).
