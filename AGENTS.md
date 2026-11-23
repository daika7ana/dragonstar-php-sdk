# AGENTS.md

## Project

Typed PHP 8.3+ SDK for the Dragon Star Curier API v1
(`daika7ana/dragonstar-php-sdk`, namespace `Daika7ana\DragonStar`). Library only:
no server, no web entrypoint, no framework in `require`, no Node toolchain.
PSR-18 core, one resource class per API area, readonly DTOs, enums.

Base URL `https://api.curierdragonstar.ro`, paths `/v1/...`, no staging host.
`Dragon Star Curier API v1.json` at the repo root is the authoritative OpenAPI
contract — check it before changing a request or response shape.

## Commands

```bash
composer install
composer ci         # validate --strict, pint --test, phpstan, phpunit tests/Unit
composer pint       # style check only, never fixes
composer stan       # PHPStan level 8 over src/
composer test       # full PHPUnit suite, smoke self-skips without a key
composer test:unit  # tests/Unit only
composer test:smoke # tests/Smoke only, self-skips without a key
vendor/bin/pint     # the only way to auto-fix style
```

- `composer.lock` is gitignored, so CI resolves fresh; keep `guzzlehttp/psr7` at
  `>=2` because `Client::create()` instantiates `GuzzleHttp\Psr7\HttpFactory`.
- Quality gates: PHPStan level 8 over `src/` (vendor/tests excluded), Laravel
  Pint (`per` preset), PHPUnit strict (`.dist`), `composer validate --strict`.
  CI runs validate, Pint and PHPStan on PHP 8.3 and unit tests on 8.3/8.4/8.5,
  branch `master`.
- CI skips every job for commits/PRs starting `docs` or `chore`, and for messages
  containing `[skip ci]` / `[skip-ci]`. Smoke never runs in CI.

## Layout

`src/` holds `Client.php`, `Config/`, `Http/`, `Support/`, `Exceptions/`,
`Enums/`, `DTOs/<Area>/` and `Resources/`. `tests/Support/` holds the fakes,
`tests/Unit/` mirrors `src/`, `tests/Smoke/` the live test; `docs/` holds the
user guides.

## Conventions

- Never import Symfony, Laravel, or another framework into `src/`, `tests/`, or
  docs, and never make one a runtime dependency; framework integration is out of
  scope. No new runtime dependencies without an explicit decision. Requests are
  built through the injected PSR-17 factories and absolute URIs assembled
  internally; callers never pass a URL.
- Request DTOs are `final readonly`; `toArray()` drops nulls and keeps `false`
  flags. Response DTOs take `array<string, mixed>` and tolerate missing or
  malformed values.
- `docs/` is user-facing documentation: behavior changes go in the matching file,
  not just in code. `CHANGELOG.md` is Keep a Changelog, newest first, tags `vX.Y.Z`.
- Commits and PR titles use Conventional Commits (`<type>(<scope>): <subject>`,
  lowercase imperative, no trailing period).

## HTTP Transport And Timeouts

- `Client` builds requests with the injected PSR-17 `RequestFactoryInterface` /
  `StreamFactoryInterface`, consumes PSR-7 `ResponseInterface`, and depends only
  on PSR-18 `Psr\Http\Client\ClientInterface` — never a concrete client.
- `src/Http/HttpClientInterface.php` widens PSR-18 with
  `sendRequest(RequestInterface $request, ?float $timeout = null)`.
  `src/Http/GuzzleHttpClient.php` is the bundled transport (Guzzle `http_errors`
  and redirects disabled, so 4xx/5xx reach the mappers);
  `src/Http/Psr18HttpClientAdapter.php` wraps any other PSR-18 client, rejects a
  non-null per-call timeout with `InvalidArgumentException`, and rethrows a
  foreign `ClientExceptionInterface` as `TransportException` (original as
  `previous`). `TransportException` implements `ClientExceptionInterface`.
- `Client::__construct()` throws `InvalidArgumentException` when
  `ClientConfig::timeout` is non-null and the client is not this package's
  `HttpClientInterface`. `ClientConfig::timeout` is `?float`, default `null`;
  `connectTimeout` exists only on
  `GuzzleHttpClient::__construct(?GuzzleClient $guzzleClient = null, ?float $timeout = null, ?float $connectTimeout = null)`,
  which applies `DEFAULT_TIMEOUT = 30.0` / `DEFAULT_CONNECT_TIMEOUT = 10.0` only
  when it creates its own Guzzle client. Precedence: per-call
  `Client::send($request, $t)` → `ClientConfig::timeout` → transport constructor
  arguments → 30.0/10.0.
- `guzzlehttp/guzzle` and `guzzlehttp/psr7` are runtime dependencies (default
  transport and `GuzzleHttp\Psr7\HttpFactory`), so `Client::create()` needs no
  arguments; no `class_exists` guard, no `suggest` entry.

## API Quirks

- Auth is one `X-Api-Key` header, omitted when the key is empty;
  `Client::create(apiKey: …)` is the shortcut. No OAuth or token store.
- Errors follow RFC 9457: `DragonStarError` exposes the parsed body; branch on
  `$error->raw['code']`, never `message`. `ApiResponseMapper` throws
  `AuthenticationException` (401), `ValidationException` (400/422; a non-empty
  `errors` map never changes the class, it only rides along on the error) and
  `UnexpectedStatusException` (anything else); each
  carries `response`, `retryAfter` (from `Retry-After`) and `error`. A success
  body missing `awb`, `totalAmount` or `pickupId` becomes
  `invalid_response`; only `POST /v1/manifests` (200) and
  `POST /v1/pickups/branch` (201) may return an empty success body.
- County and locality are names (`Cluj`, `Cluj-Napoca`), never codes. Parcel needs
  `weight` + `pieces`; pallet needs `weight`; envelope ignores both and records
  `0.5 kg` / `1 piece`.
- `track()` includes events, `trackMany()` excludes them, caps at 100 AWBs and
  reports unknown/foreign AWBs in `notFound`. Labels are case-sensitive `A4`/`A6`;
  ZPL pieces number from 1 and piece 1 carries the AWB barcode. `reverseShipment`
  conflicts with `codPaymentType: account`.
- Rate limits per key+route: GET 40/s and 1200/min, others 20/s and 600/min; 429
  carries `Retry-After`. Binary endpoints return `DTOs\Common\Document` with an
  overridden `Accept` — never JSON-decode them. Branch endpoints pin `pcId`.

## Tests

- Unit tests never touch the network. `TestClientFactory::create($handler)`
  returns `[Client, FakeHttpClient]`; `ResponseFixtureFactory` has `problem()`,
  `awbCreated()` and `validationError()` payloads.
- Assert method, path, query, headers and body on `$httpClient->lastRequest`;
  every new endpoint needs such a test covering request, response and the
  operation-specific failure code.
- `tests/Smoke/*` is `#[Group('smoke')]` and skips itself without
  `DRAGONSTAR_TEST_API_KEY` (set it in the gitignored `phpunit.xml`); the key's
  IP allowlist must include the machine. `DRAGONSTAR_SMOKE_AWB` adds live
  tracking of an existing AWB. `DRAGONSTAR_SMOKE_ENABLE_WRITES=true` opts into
  AWB and pickup creation; every created AWB is deleted and every pickup
  cancelled in a `try/finally`, and a failed cleanup fails the test.
  `composer ci` must pass before done.
