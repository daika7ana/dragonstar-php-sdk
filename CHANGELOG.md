# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.0] - 2026-11-25

### Changed

- `ValidationException::$errors` defaults to `[]` and `$previous` accepts any `Throwable`, aligning the constructor with the sibling SDKs.

## [1.3.0] - 2026-11-23

### Added

- Live smoke coverage for localities, branches, quoting, tracking, labels and opt-in AWB/pickup lifecycle with guaranteed cleanup.

- `DTOs\Common\Errors\DragonStarError` carrying the parsed RFC 9457 body plus `isValidationRelated()`/`isAuthenticationRelated()`.
- `AuthenticationException`, `ValidationException` and `UnexpectedStatusException`, each exposing `response`, `retryAfter` and `error`.
- `composer test:smoke` script; unit tests for `ClientConfig` and the enums.

### Changed

- **Breaking:** HTTP errors are classified as `AuthenticationException` (401), `ValidationException` (400/422) or `UnexpectedStatusException` (any other non-2xx or unparseable body).
- **Breaking:** `invalid_response` and `invalid_request` are reported by `UnexpectedStatusException`; read the code from `$error->raw['code']`.
- `UnexpectedStatusException::getCode()` returns the HTTP status when the exception carries a response, `0` otherwise.
- **Breaking:** the exception class is decided by status alone; a non-empty `errors` map on a `403`/`404`/`5xx` no longer raises `ValidationException`, the map stays in `$error->raw['errors']`.
- `AuthenticationException` and `ValidationException` derive `getCode()` from the response status, like `UnexpectedStatusException`.

### Removed

- **Breaking:** `ApiException`; its `status`, `errorCode`, `type`, `title`, `detail`, `errors` and `retryAfter` properties moved to the new exception set and `DragonStarError`.

## [1.2.0] - 2026-11-22

### Added

- `Client::withTimeout(?float $timeout)` derives a client sharing the transport, PSR-17 factories and API key with a different request timeout budget.

## [1.1.0] - 2026-11-20

### Added

- `Client::create()` and `Client::__construct()` accept any PSR-18 `ClientInterface`; a bare client is wrapped in `Psr18HttpClientAdapter`.
- `GuzzleHttpClient::DEFAULT_TIMEOUT`/`DEFAULT_CONNECT_TIMEOUT` and `timeout:`/`connectTimeout:` constructor arguments.

### Changed

- **Breaking:** `ClientConfig::timeout` defaults to `null` instead of `30.0`. The bundled transport applies 30 s request / 10 s connect, adding a 10 s connect cap that was previously absent. A custom `HttpClientInterface` receives `null` unless a timeout is configured.
- A non-null `ClientConfig::timeout` combined with a bare PSR-18 client throws `InvalidArgumentException`, and a per-call timeout passed to such a client throws too.
- A PSR-18 client that throws a `ClientExceptionInterface` is now wrapped in `TransportException`.

### Removed

- **Breaking:** `Daika7ana\DragonStar\Support\HttpFoundationBridge` and the `symfony/http-foundation` dependency. Convert between PSR-7 and HttpFoundation types in application code.

## [1.0.0] - 2026-11-19

### Added

- API-key client targeting `https://api.curierdragonstar.ro`, with a PSR-18
  `HttpClientInterface` and a Guzzle adapter.
- `ClientConfig` with base URL, API key and a 30-second default timeout; `Client::create()` accepts
  PSR-17 factories, a custom HTTP client and a key shortcut.
- `awb()` resource: create, create-for-branch, quote, quote-for-branch, track,
  track-many, delete, return legs, PDF labels (default, A4/A6) and ZPL labels.
- `pickups()` resource: create, create-for-branch and cancel.
- `manifests()` resource: create and PDF download.
- `directory()` resource: counties, localities, localities with distances and
  branches.
- Typed request DTOs for AWB creation, quotes and pickups; typed response DTOs
  for created AWBs, quotes, tracking, return legs, pickups, manifests,
  branches and documents.
- `ShipmentType`, `CodPaymentType`, `LabelFormat` and `ShipmentStatus` enums.
- `ApiException` mapping RFC 9457 problem documents, including stable error
  codes, field errors and `Retry-After`.
- `Document` for binary responses with filename and download-header helpers.
- Optional `HttpFoundationBridge` for Symfony/Laravel.
- Unit test suite (87 tests) and an opt-in live smoke test.
- Documentation for installation, configuration, authentication, resources,
  DTOs, testing and errors.
- CI for Pint, PHPStan level 8 and PHPUnit on PHP 8.3–8.5.
