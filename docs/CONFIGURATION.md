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
| `timeout` | `timeout` | `null` | Request timeout in seconds; `null` defers to the HTTP client (the bundled `GuzzleHttpClient` uses 30.0s, and 10.0s to connect) |

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

`timeout` is optional; leave it `null` to use the transport's own timeout.

`Client::create()` accepts:

| Parameter | Purpose |
| --- | --- |
| `httpClient` | A PSR-18 client, or a `HttpClientInterface` (PSR-18 plus timeout). A non-null `ClientConfig::timeout` requires `HttpClientInterface` |
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

`ClientConfig::timeout` is `null` by default. Precedence: the per-call argument → `ClientConfig::timeout` → the transport's own default (`GuzzleHttpClient` uses 30 s to respond and 10 s to connect unless you pass `timeout:`/`connectTimeout:`; an injected `GuzzleClient` keeps its own configuration). A bare PSR-18 client keeps its own timeout and cannot receive the per-call argument — passing one throws `InvalidArgumentException`; combining a bare client with a non-null `ClientConfig::timeout` also throws.

When some calls need a different budget than the rest, derive a client:
`$slowClient = $client->withTimeout(30.0);` returns a new client sharing the
transport, the PSR-17 factories and the API key as of derivation (a later
`setApiKey()` on either client affects only that client), so no new transport is
built; the original client keeps its timeout. `withTimeout(null)` returns to the
transport default.

## Changing the key at runtime

```php
$client->setApiKey('rotated-key');
```

`getConfig()` always reflects the current key.

## Custom HTTP client

Any PSR-18 client works. Implement `HttpClientInterface` (adds `?float $timeout`) only if you want the SDK's per-request timeout.

```php
use Daika7ana\DragonStar\Client;

/** @var \Psr\Http\Client\ClientInterface $psr18 */
/** @var \Psr\Http\Message\RequestFactoryInterface $requestFactory */
/** @var \Psr\Http\Message\StreamFactoryInterface $streamFactory */
$client = Client::create(
    httpClient: $psr18,
    requestFactory: $requestFactory,
    streamFactory: $streamFactory,
    apiKey: 'key',
);
```

Transport failures should surface as `Daika7ana\DragonStar\Exceptions\TransportException`.
