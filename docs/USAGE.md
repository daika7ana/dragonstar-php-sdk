# Dragon Star PHP SDK - Usage Guide

Use this page as the entry point for package documentation.

## Documentation Sections

- [Quickstart](QUICKSTART.md)
- [Installation](INSTALLATION.md)
- [Configuration](CONFIGURATION.md)
- [Authentication](AUTHENTICATION.md)
- [Resources](RESOURCES.md)
- [Data Transfer Objects (DTOs)](DTOS.md)
- [Testing](TESTING.md)
- [Error Handling](ERRORS.md)

## Notes

- The package is framework-agnostic and PSR-first by design.
- All v1 endpoints documented by Dragon Star are implemented; there is no v2.
- API keys are bound to IP allowlists; a new machine needs its public IP added
  to the key in the Dragon Star account.
- County and locality fields take **names** from `directory()`, not codes.
- Amounts are RON; `Quote` and `AwbCreated` totals include VAT.
- Tracking statuses map to the `ShipmentStatus` enum; unknown future codes
  become `null`.
- Labels and manifest PDFs are returned as `Document`; call `getContents()` to
  write them to disk.
- Collection helpers are documented in
  [Data Transfer Objects (DTOs)](DTOS.md#collection-helper-methods).
- Error codes and retry behavior are documented in [Errors](ERRORS.md).
- Live smoke coverage is documented in [Testing](TESTING.md#smoke-test).
