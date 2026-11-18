# Repository Guide

Typed PHP 8.3+ SDK for the Dragon Star Curier API v1 (`daika7ana/dragonstar-php-sdk`,
namespace `Daika7ana\DragonStar`). Library only: no app entrypoint, no framework
in `require`, no Node toolchain (ignore the global pnpm default here).

## Commands

```bash
composer ci        # exactly what CI runs: pint --test, phpstan, unit tests
composer test      # every test; the live smoke test self-skips without a key
composer test:unit # tests/Unit only
composer test -- tests/Unit/Resources/AwbResourceTest.php
composer test -- --filter=AwbResourceTest
vendor/bin/pint    # the only way to fix style; `composer pint` is check-only
```

- `composer.lock` is gitignored, so CI always resolves latest. Keep constraints
  honest: `guzzlehttp/psr7` must stay `>=2` because `Client::create()`
  instantiates `GuzzleHttp\Psr7\HttpFactory`.
- CI triggers on `master` only. Pint and PHPStan run on 8.3; unit tests on
  8.3/8.4/8.5. Smoke tests never run in CI. `docs*` and `chore*` commits skip
  every job, as does any commit whose message contains `[skip ci]` or
  `[skip-ci]`; append `[skip ci]` to docs- and chore-only commit subjects.

## Architecture

- Authoritative contract: `Dragon Star Curier API v1.json` at the repo root
  (OpenAPI 3). Check it before changing any request or response shape.
- `src/Client.php` is the only entrypoint. `Client::create()` wires Guzzle and
  `HttpFactory` unless injected; `awb()`, `pickups()`, `manifests()` and
  `directory()` are lazy. Base URL is `https://api.curierdragonstar.ro`, paths
  are `/v1/...`; there is no staging host.
- Auth is one `X-Api-Key` header, omitted when the key is empty. No OAuth or
  token store.
- Status handling belongs in `Support\ApiResponseMapper` (`decodeJson`,
  `decodeJsonOrNull`, `assertStatus`). Non-expected statuses become
  `ApiException::fromResponse()`; callers branch on `errorCode`, never `detail`.
  Resources also reject success bodies missing required keys (`awb`,
  `totalAmount`, `pickupId`) as `invalid_response` — keep that when adding
  endpoints. Among JSON-decoding endpoints, only `POST /v1/manifests` (200) and
  `POST /v1/pickups/branch` (201) may return an empty success body.
- Request DTOs are `final readonly`; `toArray()` drops nulls and keeps `false`
  flags. Response DTOs take `array<string, mixed>` and tolerate missing or
  malformed values. Branch endpoints pin `pcId` (`['pcId' => $id] + $data`) so a
  caller cannot override it.
- Binary endpoints return `DTOs\Common\Document` and override `Accept`
  (`application/pdf`, `application/octet-stream`). Never JSON-decode them.
- `Support\HttpFoundationBridge` is the only Symfony-coupled file and
  `symfony/http-foundation` is dev-only. Never import Symfony from core code or
  make it a required dependency.

## Testing

- `tests/Unit` mirrors `src/`; there is no network. Build clients with
  `Tests\Support\TestClientFactory::create(callable)` and `FakeHttpClient`
  (returns a real PSR-7 `Response`), not hand-rolled PSR stubs.
- Each resource test asserts both the DTO mapping and the outgoing request via
  `$httpClient->lastRequest` (method, path, query, headers, body), plus the
  operation-specific failure code.
- `tests/Smoke/ClientSmokeTest.php` is `#[Group('smoke')]` and skips itself
  without `DRAGONSTAR_TEST_API_KEY` (set it in the gitignored `phpunit.xml`).
  The key's IP allowlist must include the machine, or the API returns
  `403 ip_not_allowed`.

## API behaviors that bite

- County and locality are **names** (`Cluj`, `Cluj-Napoca`), never codes;
  `counties()` returns the code→name map.
- Parcel requires `weight` + `pieces`; pallet requires `weight` (pieces
  ignored); envelope ignores both and records `0.5 kg` / `1 piece`.
- `track()` includes events by default; `trackMany()` excludes them by default,
  caps at 100 AWBs, preserves order and reports unknown or foreign AWBs in
  `notFound`.
- Label formats are case-sensitive `A4`/`A6`. ZPL piece numbering starts at 1,
  and piece 1 carries the AWB barcode.
- `reverseShipment` is incompatible with `codPaymentType: account`.
- Rate limits per key+route: GET 40/s and 1200/min; other methods 20/s and
  600/min. A 429 carries `Retry-After`, exposed as `ApiException::$retryAfter`.

## Conventions

- Commits and PR titles use Conventional Commits: `<type>(<scope>): <subject>`,
  lowercase imperative subject, no trailing period. Types: `feat`, `fix`,
  `docs`, `refactor`, `test`, `perf`, `chore`, `ci`, `build`, `style`.
- Behavior changes update the matching `docs/*.md` file and `CHANGELOG.md`
  (Keep a Changelog, newest section first, release tags `vX.Y.Z`).
