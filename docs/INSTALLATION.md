# Installation

## Requirements

- PHP **8.3+** with `ext-json`
- Composer 2
- PSR-7 / PSR-17 / PSR-18 interfaces (installed automatically; Guzzle supplies
  the implementations and ships with the package)

## Install

```bash
composer require daika7ana/dragonstar-php-sdk
```

The package ships with a Guzzle adapter and Guzzle is installed as a runtime
dependency, so there is no extra install step. It also requires `psr/http-client`,
`psr/http-message` and `psr/http-factory`, so any PSR-18 client can be passed to
`Client::create()` and is wrapped automatically — supplying its own PSR-17 factories
only if you do not use Guzzle's.

## Verify

```php
use Daika7ana\DragonStar\Client;

$client = Client::create(apiKey: 'your-api-key');

var_dump($client->getConfig()->baseUrl); // https://api.curierdragonstar.ro
```

If the key is valid and your public IP is allowlisted,
`$client->directory()->counties()` returns a non-empty collection.

See [Configuration](CONFIGURATION.md) and [Authentication](AUTHENTICATION.md) next.
