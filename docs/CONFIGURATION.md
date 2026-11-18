# Configuration

`Daika7ana\DragonStar\Config\ClientConfig` is a small value object. Prefer the
`with*()` methods — they return a modified clone and leave the original
untouched:

```php
use Daika7ana\DragonStar\Config\ClientConfig;

$config = (new ClientConfig(apiKey: 'key'))
    ->withBaseUrl('https://proxy.example.com')
    ->withTimeout(30.0);
```

| Property | Constructor argument | Default | Meaning |
| --- | --- | --- | --- |
| `baseUrl` | `baseUrl` | `https://api.curierdragonstar.ro` | API root; no trailing slash |
| `apiKey` | `apiKey` | `''` | Sent as `X-Api-Key`; omitted when empty |
| `timeout` | `timeout` | `30.0` | Request timeout in seconds; `null` defers to the HTTP client (Guzzle's default is no timeout) |

## Creating a client

```php
use Daika7ana\DragonStar\Client;

// Shortest form
$client = Client::create(apiKey: 'your-api-key');

// Full control
$client = Client::create(config: new ClientConfig(
    baseUrl: ClientConfig::BASE_URL_PRODUCTION,
    apiKey: 'your-api-key',
    timeout: 30.0,
));
```

`Client::create()` accepts:

| Parameter | Purpose |
| --- | --- |
| `httpClient` | A `HttpClientInterface` (PSR-18 plus timeout) |
| `requestFactory` | A PSR-17 request factory |
| `streamFactory` | A PSR-17 stream factory |
| `config` | A `ClientConfig` |
| `apiKey` | Convenience shortcut; applied on top of `config` |

If no factories are injected, `GuzzleHttp\Psr7\HttpFactory` is used. If no
HTTP client is injected, the bundled `GuzzleHttpClient` is used.

## Base URL

No staging environment is published for this API. Point `baseUrl` at a proxy
or a local mock when you need one:

```php
$client = Client::create(
    config: (new ClientConfig(apiKey: 'key'))->withBaseUrl('http://localhost:8080'),
);
```

## Timeouts

```php
$config = new ClientConfig(apiKey: 'key', timeout: 15.0);
$client = Client::create(config: $config);

// Per-call override
$client->send($client->createRequest('GET', '/v1/counties'), 2.5);
```

## Changing the key at runtime

```php
$client->setApiKey('rotated-key');
```

`getConfig()` always reflects the current key.

## Custom HTTP client

```php
use Daika7ana\DragonStar\Client;
use Daika7ana\DragonStar\Http\HttpClientInterface;

/** @var HttpClientInterface $customClient */
$client = Client::create(httpClient: $customClient, apiKey: 'key');
```

Your implementation must implement:

```php
public function sendRequest(
    Psr\Http\Message\RequestInterface $request,
    ?float $timeout = null,
): Psr\Http\Message\ResponseInterface;
```

Transport failures should surface as `Daika7ana\DragonStar\Exceptions\TransportException`.

## Optional HttpFoundation bridge

```php
use Daika7ana\DragonStar\Support\HttpFoundationBridge;
```

- `toSymfonyRequest()` / `toSymfonyResponse()` — PSR-7 to Symfony
- `fromSymfonyRequest()` — Symfony to PSR-7

Install `symfony/http-foundation` yourself to use it.
