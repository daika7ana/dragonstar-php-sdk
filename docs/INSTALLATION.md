# Installation

## Requirements

- PHP **8.3+** with `ext-json`
- Composer 2
- PSR-7 / PSR-17 / PSR-18 compatible environment (installed automatically)

## Install

```bash
composer require daika7ana/dragonstar-php-sdk
```

The package ships with a Guzzle adapter. It also requires `psr/http-client`,
`psr/http-message` and `psr/http-factory`, so any PSR-18 client can be used.

## Optional Symfony/Laravel bridge

`Daika7ana\DragonStar\Support\HttpFoundationBridge` is opt-in. It is not in
`require`, because the core must stay framework-agnostic:

```bash
composer require symfony/http-foundation
```

## Verify

```php
use Daika7ana\DragonStar\Client;

$client = Client::create(apiKey: 'your-api-key');

var_dump($client->getConfig()->baseUrl); // https://api.curierdragonstar.ro
```

If the key is valid and your public IP is allowlisted,
`$client->directory()->counties()` returns a non-empty collection.

See [Configuration](CONFIGURATION.md) and [Authentication](AUTHENTICATION.md) next.
