# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-11-18

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
