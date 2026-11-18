# Error Handling

Every API error is surfaced as one of three exceptions:

- `Daika7ana\DragonStar\Exceptions\ApiException` — the API answered with a
  non-success status, or with a success status and an unusable body.
- `Daika7ana\DragonStar\Exceptions\TransportException` — the request never
  produced a response (DNS, TLS, timeout, connection reset).
- `Daika7ana\DragonStar\Exceptions\DragonStarException` — the common base, if
  you prefer a single catch.

## ApiException

The API returns RFC 9457 `application/problem+json` documents:

```json
{
  "type": "https://api.curierdragonstar.ro/errors/awb-not-found",
  "title": "Resource not found",
  "status": 404,
  "code": "awb_not_found",
  "detail": "AWB 94348148 does not exist."
}
```

`ApiException` exposes those fields:

| Property | Type | Notes |
| --- | --- | --- |
| `status` | `int` | HTTP status (also the exception code) |
| `errorCode` | `string` | Stable code — branch on this |
| `type` | `string` | Problem type URI (not a URL to call) |
| `title` | `string` | Short, stable category |
| `detail` | `string` | Human-readable, may change |
| `errors` | `array<string, list<string>>` | Field messages on `validation_failed` |
| `retryAfter` | `?int` | Seconds, from `Retry-After` |
| `response` | `?ResponseInterface` | The raw PSR-7 response |

The exception message is `detail (HTTP <status>, <code>)`.

```php
use Daika7ana\DragonStar\DTOs\Awb\AwbCreateRequest;
use Daika7ana\DragonStar\Enums\ShipmentType;
use Daika7ana\DragonStar\Exceptions\ApiException;
use Daika7ana\DragonStar\Exceptions\TransportException;

$request = new AwbCreateRequest(
    shipmentType: ShipmentType::Parcel,
    recipientName: 'Ion Popescu',
    recipientAddress: 'Str. Memorandumului nr. 28',
    recipientCounty: 'Cluj',
    recipientCity: 'Cluj-Napoca',
    weight: 5,
    pieces: 1,
);

try {
    $client->awb()->create($request);
} catch (ApiException $e) {
    if ($e->errorCode === 'validation_failed') {
        foreach ($e->errors as $field => $messages) {
            printf("%s: %s\n", $field, implode(', ', $messages));
        }
    } elseif ($e->errorCode === 'rate_limit_exceeded') {
        sleep($e->retryAfter ?? 1);
    }
} catch (TransportException $e) {
    // Network failure; safe to retry idempotent requests.
}
```

## Codes

| Code | Typical status | Meaning |
| --- | --- | --- |
| `unauthorized` | 401 | Missing, unknown or expired API key |
| `ip_not_allowed` | 403 | Key valid, request IP not allowlisted |
| `account_not_eligible` | 403 | Account may not perform the operation |
| `invalid_json` | 400 | Body is not a JSON object |
| `empty_body` | 400 | Body was required but empty |
| `validation_failed` | 422 | See `$e->errors` |
| `awb_not_found` | 404 | No such AWB, or it belongs to another client |
| `return_not_found` | 404 | No return legs for that AWB |
| `awb_not_deletable` | 409 | Picked up, manifested or invoiced |
| `pickup_not_found` | 404 | No such pickup |
| `pickup_not_cancellable` | 409 | Refused, collected or already cancelled |
| `manifest_not_found` | 404 | No such manifest |
| `manifest_empty` | 4xx | The manifest has nothing to print |
| `county_not_found` | 404 | County is not in the nomenclature |
| `endpoint_not_found` | 404 | Path does not exist |
| `method_not_allowed` | 405 | Path exists on another method; see `Allow` |
| `rate_limit_exceeded` | 429 | Back off for `retryAfter` seconds |
| `temporarily_banned` | 429 | Repeated probes of unknown paths; wait it out |
| `service_unavailable` | 503 | Upstream unavailable; retry later |

## SDK-generated codes

Two codes never come from the server:

| Code | Status | Cause |
| --- | --- | --- |
| `invalid_response` | the response status (usually 200) | Body is not a JSON object |
| `invalid_request` | 0 | A request DTO contains a non-UTF-8 string and cannot be encoded |

## Validation

Only `422` carries field errors. The API normalizes them as
`field => list of messages`, and `$e->errors` preserves that shape. The
exception message uses `detail`, so it is safe to log but not to parse.

## Rate limiting

Headers `X-RateLimit-Limit` and `X-RateLimit-Remaining` are on every response.
On `429`, respect `Retry-After` (`1` for the per-second limit, `60` for the
per-minute limit). See [Authentication](AUTHENTICATION.md#rate-limits).

## Transport failures

`TransportException` implements `Psr\Http\Client\ClientExceptionInterface`, so
generic PSR-18 error handling also catches it.
