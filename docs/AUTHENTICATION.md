# Authentication

The Dragon Star Curier API uses a per-account **API key**. There is no OAuth
flow and no token refresh.

## Getting a key

Keys are issued in the Dragon Star account under **Settings → Access API**.
Each key is bound to the public IP addresses it may be used from.

## Sending the key

The SDK adds the key to every request when one is configured:

```php
use Daika7ana\DragonStar\Client;

$client = Client::create(apiKey: 'your-api-key');
```

HTTP header:

```http
X-Api-Key: your-api-key
```

The API also accepts `Authorization: Bearer <key>` and gives it priority when
both are present. This SDK always authenticates with `X-Api-Key` (the header is
omitted when no key is configured).

## Failure modes

| Status | `code` | Meaning |
| --- | --- | --- |
| `401` | `unauthorized` | No key sent, or the key is unknown/expired. `detail` distinguishes the two. |
| `403` | `ip_not_allowed` | The key is valid but the request IP is not on its allowlist. |
| `403` | `account_not_eligible` | The account may not use this operation. |

All three throw `Daika7ana\DragonStar\Exceptions\ApiException`.

```php
use Daika7ana\DragonStar\Exceptions\ApiException;

try {
    $client->directory()->counties();
} catch (ApiException $e) {
    if ($e->errorCode === 'ip_not_allowed') {
        // Add this machine's public IP to the key in the Dragon Star account.
    }
}
```

## Rate limits

Limits apply per API key and per route:

| Method | Per second | Per minute |
| --- | --- | --- |
| `GET` | 40 | 1200 |
| Everything else | 20 | 600 |

Responses carry `X-RateLimit-Limit` and `X-RateLimit-Remaining`.

On exceeding a limit:

- status `429`
- `code: rate_limit_exceeded`
- `Retry-After` in seconds (`1` for the per-second limit, `60` for the per-minute limit)

The SDK exposes it as `$exception->retryAfter`.

Public routes (documentation, health and unknown paths) have a stricter,
IP-based limit; repeatedly probing unknown paths can temporarily ban the IP
(`code: temporarily_banned`).

## Key rotation

```php
$client->setApiKey('new-key');
```

The next request uses the new key; no re-authentication step is required.
