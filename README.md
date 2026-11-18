# Dragon Star PHP SDK

<div align="center">

[![CI](https://github.com/daika7ana/dragonstar-php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/daika7ana/dragonstar-php-sdk/actions/workflows/ci.yml)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php)](https://www.php.net/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-12%E2%80%9313-0A7BBB?style=flat-square)](docs/TESTING.md)
[![PSR Standards](https://img.shields.io/badge/PSR-7%2F17%2F18-blue?style=flat-square)](https://www.php-fig.org/)
[![License](https://img.shields.io/badge/license-GPL--3.0--or--later-green?style=flat-square)](LICENSE)

A modern, type-safe PHP SDK for the **Dragon Star Curier API v1**

</div>

> 🚀 Framework-agnostic, API-key powered, fully typed with DTOs, and production-ready.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Authentication](#authentication)
- [Supported Resources](#supported-resources)
- [Framework Support](#framework-support)
- [Testing](#testing)
- [Documentation](#documentation)

## Features

### Core Strengths

- ✅ **API Key Authentication** — Sent as `X-Api-Key` when a key is configured
- ✅ **PSR-18 HTTP Client** — Pluggable, with a Guzzle adapter by default
- ✅ **Fully Typed DTOs** — Requests and responses, no vague arrays required
- ✅ **Every v1 Endpoint** — AWB creation, quotes, tracking, labels, pickups, manifests, nomenclature
- ✅ **RFC 9457 Errors** — One `ApiException` exposing the stable `code`, field errors and `Retry-After`
- ✅ **Typed Tracking** — Status enum, events, locations and return legs
- ✅ **Binary Documents** — PDF/ZPL labels and manifest PDFs with filename helpers
- ✅ **Iterable Collections** — `count`, `first`, `last`, `get`, `filter`, `map`, `pluck`, …
- ✅ **Static Analysis with PHPStan** — Level 8 over `src/`
- ✅ **Symfony/Laravel Bridge** — Optional `HttpFoundationBridge`
- ✅ **Comprehensive Tests** — Unit suite plus an opt-in live smoke test

### Perfect For

- 🎯 Laravel applications or any PHP framework
- 🎯 Microservices and standalone PHP projects
- 🎯 Order fulfilment and AWB printing workflows
- 🎯 Production deployments with full test coverage

## Requirements

- **PHP 8.3+**
- PSR-7 / PSR-17 / PSR-18 compatible environment

## Installation

```bash
composer require daika7ana/dragonstar-php-sdk
```

## Quick Start

```php
use Daika7ana\DragonStar\Client;
use Daika7ana\DragonStar\DTOs\Awb\AwbQuoteRequest;
use Daika7ana\DragonStar\Enums\ShipmentType;

$client = Client::create(apiKey: 'your-api-key');

$quote = $client->awb()->quote(new AwbQuoteRequest(
    shipmentType: ShipmentType::Parcel,
    recipientCounty: 'Cluj',
    recipientCity: 'Cluj-Napoca',
    weight: 5,
));

echo $quote->totalAmount; // RON, VAT included
```

Create the shipment and download its label:

```php
use Daika7ana\DragonStar\DTOs\Awb\AwbCreateRequest;

$created = $client->awb()->create(new AwbCreateRequest(
    shipmentType: ShipmentType::Parcel,
    recipientName: 'Ion Popescu',
    recipientAddress: 'Str. Memorandumului nr. 28',
    recipientCounty: 'Cluj',
    recipientCity: 'Cluj-Napoca',
    weight: 5,
    pieces: 1,
    codAmount: 150,
    notes: 'Fragil',
));

$label = $client->awb()->label($created->awb);
file_put_contents($label->getFilename('awb.pdf'), $label->getContents());
```

## Configuration

### Base URL

The client targets production by default:

```php
use Daika7ana\DragonStar\Config\ClientConfig;

ClientConfig::BASE_URL_PRODUCTION; // https://api.curierdragonstar.ro
```

For a proxy or a local mock, set the URL explicitly:

```php
$config = new ClientConfig(baseUrl: 'https://proxy.example.com');
$client = Client::create(config: $config);
```

### API Key

```php
$client = Client::create(apiKey: 'your-api-key');

// or, later
$client->setApiKey('another-key');
```

### Request Timeouts

Requests time out after 30 seconds by default. Override it with
`new ClientConfig(timeout: 60.0)` or `withTimeout()`, and pass `timeout: null`
to defer to the HTTP client. A per-call override always wins:

```php
$client->send($request, 2.5);
```

## Authentication

Each request carries the API key in the `X-Api-Key` header. Keys are issued in
the Dragon Star account under **Settings → Access API**, and each key is bound
to the public IP addresses it may be used from — a request from anywhere else
returns `403` with `code: ip_not_allowed`.

```php
$client = Client::create(apiKey: getenv('DRAGONSTAR_API_KEY'));
```

The API also accepts the key as `Authorization: Bearer <key>`; this SDK always
uses `X-Api-Key`.

### Rate Limits

Limits apply per API key and per route:

| Method | Per second | Per minute |
| --- | --- | --- |
| `GET` | 40 | 1200 |
| Everything else | 20 | 600 |

Every response includes `X-RateLimit-Limit` and `X-RateLimit-Remaining`. On
exceeding a limit the API answers `429` with `code: rate_limit_exceeded` and a
`Retry-After` header, exposed as `$exception->retryAfter`.

## Supported Resources

### `awb()` — Shipments

- `create()` — Create an AWB
- `createForBranch()` — Create an AWB for a branch (`pcId`)
- `quote()` — Estimate a shipment's cost
- `quoteForBranch()` — Estimate for a branch
- `track()` — Track one AWB
- `trackMany()` — Track up to 100 AWBs, with `notFound`
- `delete()` — Delete an AWB while still deletable
- `returnLegs()` — Return legs linked to an AWB
- `label()` — Label in the account's configured format
- `labelAs()` — Label as `A4` or `A6`
- `zplLabel()` — Raw ZPL for one piece

### `pickups()` — Pickups

- `create()` — Order a pickup
- `createForBranch()` — Order a pickup for a branch
- `cancel()` — Cancel a pickup

### `manifests()` — Manifests

- `create()` — Close shipments into a new manifest
- `pdf()` — Download the manifest PDF

### `directory()` — Nomenclature

- `counties()` — Counties keyed by code
- `localities()` — Localities of a county
- `localitiesWithDistances()` — Localities with distance in km
- `branches()` — Branches the master account may ship for

## Framework Support

### Zero Framework Dependencies

This package works equally well in Laravel, Symfony, standalone PHP and
microservices. No framework is required by `require`.

### Optional: HttpFoundation Bridge

`HttpFoundationBridge` converts between PSR-7 and Symfony HttpFoundation for
Laravel/Symfony integrations. It needs Symfony's HttpFoundation, which this
package does not install: `composer require symfony/http-foundation`.

### Custom HTTP Client

Pass any PSR-18 client wrapped in `HttpClientInterface`, or inject PSR-17
factories:

```php
use Daika7ana\DragonStar\Client;
use Daika7ana\DragonStar\Http\HttpClientInterface;

/** @var HttpClientInterface $customClient */
$client = Client::create(httpClient: $customClient, apiKey: 'your-api-key');
```

`HttpClientInterface::sendRequest()` takes an optional timeout argument, so
custom clients can honour per-request timeouts.

## Testing

```bash
composer ci        # pint -> stan -> test:unit (exactly what CI runs)
composer pint      # style check only — never fixes
composer stan      # PHPStan level 8 over src/
composer test      # every test, smoke included
composer test:unit # tests/Unit only
```

The live smoke test (`tests/Smoke/ClientSmokeTest.php`) skips itself unless
`DRAGONSTAR_TEST_API_KEY` is set; copy `phpunit.xml.dist` to `phpunit.xml` and
fill it in to run it. The key's IP allowlist must include the machine.

[See detailed testing docs →](docs/TESTING.md)

## Documentation

Complete guides and references:

| Guide | Purpose |
|-------|---------|
| 📖 [Quickstart](docs/QUICKSTART.md) | Jump right in with working examples |
| 🔧 [Installation](docs/INSTALLATION.md) | Detailed setup instructions |
| ⚙️ [Configuration](docs/CONFIGURATION.md) | Environment setup and options |
| 🔐 [Authentication](docs/AUTHENTICATION.md) | API keys, IP allowlists and rate limits |
| 🔗 [Resources](docs/RESOURCES.md) | Complete resource reference |
| 📦 [DTOs](docs/DTOS.md) | Data Transfer Objects and enums |
| ✅ [Testing](docs/TESTING.md) | Unit and smoke test coverage |
| ❌ [Errors](docs/ERRORS.md) | Exception handling guide |
| 📚 [All Docs](docs/USAGE.md) | Documentation index |

---

<div align="center">

Made with ❤️

**Questions?** [Check the docs](docs/USAGE.md) or [open an issue](../../issues)

</div>
