# Error Handling

Every API error is surfaced as one of these exceptions:

- `Daika7ana\DragonStar\Exceptions\AuthenticationException` — the API
  answered `401` (missing, unknown or expired API key).
- `Daika7ana\DragonStar\Exceptions\ValidationException` — the request body
  was rejected (`400`/`422`).
- `Daika7ana\DragonStar\Exceptions\UnexpectedStatusException` — any other
  non-success status, or a success response with an unusable body.
- `Daika7ana\DragonStar\Exceptions\TransportException` — the request never
  produced a response (DNS, TLS, timeout, connection reset).
- `Daika7ana\DragonStar\Exceptions\DragonStarException` — the common base,
  if you prefer a single catch.

## Status mapping

| Response status | Exception |
| --- | --- |
| `401` | `AuthenticationException` |
| `400`, `422` | `ValidationException` |
| any other non-2xx | `UnexpectedStatusException` |

A `403` (`ip_not_allowed`, `account_not_eligible`) is an
`UnexpectedStatusException`, not an `AuthenticationException`: the key was
accepted, the operation was not permitted.

Status alone decides the class. A non-empty `errors` map never flips a
`403`/`404`/`5xx` into `ValidationException`; it stays in `$error->raw['errors']`.

## RFC 9457 problem details

The API returns `application/problem+json` documents:

```json
{
  "type": "https://api.curierdragonstar.ro/errors/awb-not-found",
  "title": "Resource not found",
  "status": 404,
  "code": "awb_not_found",
  "detail": "AWB 94348148 does not exist."
}
```

`Daika7ana\DragonStar\DTOs\Common\Errors\DragonStarError` is the shared
error DTO carried by the three HTTP-level exceptions:

| Property | Type | Notes |
| --- | --- | --- |
| `code` | `int` | HTTP status (or the supplied fallback when the body has none) |
| `message` | `string` | `detail`, falling back to `title` |
| `id` | `string` | Problem id when the API sends one |
| `raw` | `array<string, mixed>` | Full parsed body: `type`, `title`, `status`, `code`, `detail`, `errors` |
| `context` | `?string` | Optional problem context |
| `component` | `?string` | Optional component name |

The typed contract is `code`, `message`, `id`, `context` and `component`.
Everything else in the RFC 9457 document — `type`, `title` and the API error
code (`raw['code']`) — stays readable in `$error->raw[...]`. `context` and
`component` are carrier-defined: passed through when the server sends them,
`null` otherwise.

Branch on `raw['code']` (the stable API error code), never on `message`.
`isValidationRelated()` is true for status `400`/`422` or a non-empty
`errors` map; `isAuthenticationRelated()` is true for status `401`/`403`.

## Exception properties

| Exception | Properties |
| --- | --- |
| `AuthenticationException` | `response`, `retryAfter`, `error` |
| `ValidationException` | `errors` (`array<string, list<string>>`), `response`, `retryAfter`, `error` |
| `UnexpectedStatusException` | `response`, `retryAfter`, `error` |

`response` is the raw PSR-7 response, `retryAfter` the numeric `Retry-After`
value in seconds, and `error` the parsed `DragonStarError`. All three are
nullable: a locally raised exception has no response.

`getCode()` returns the HTTP status whenever the exception carries a
`response`, otherwise `0` — as for a request body that cannot be encoded.

```php
use Daika7ana\DragonStar\DTOs\Awb\AwbCreateRequest;
use Daika7ana\DragonStar\Enums\ShipmentType;
use Daika7ana\DragonStar\Exceptions\AuthenticationException;
use Daika7ana\DragonStar\Exceptions\TransportException;
use Daika7ana\DragonStar\Exceptions\UnexpectedStatusException;
use Daika7ana\DragonStar\Exceptions\ValidationException;

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
} catch (ValidationException $e) {
    foreach ($e->errors as $field => $messages) {
        printf("%s: %s\n", $field, implode(', ', $messages));
    }
} catch (AuthenticationException|UnexpectedStatusException $e) {
    $code = $e->error?->raw['code'] ?? 'unknown_error';

    if ($code === 'rate_limit_exceeded') {
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
| `awb_not_found` | 404 | Well-formed AWB (8 digits starting with 9) that does not exist or belongs to another client |
| `return_not_found` | 404 | The AWB has no return legs |
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

Two codes never come from the server; both are carried by
`UnexpectedStatusException::$error->raw['code']`:

| Code | Status | Cause |
| --- | --- | --- |
| `invalid_response` | the response status (usually 200) | Body is not a JSON object, or is missing a required field |
| `invalid_request` | 0 | A request DTO contains a non-UTF-8 string and cannot be encoded |

## Validation

`ValidationException` is thrown only for `400`/`422`. The API normalizes field
errors as `field => list of messages`, and `$e->errors` preserves that shape.
Scalar messages are wrapped in a list.

AWB numbers are validated before lookup: a value that is not 8 digits starting
with 9 fails with `validation_failed` (`awb` or `awbs` field), not
`awb_not_found`. In `trackMany`, one malformed entry rejects the whole batch.

`codPaymentType` is required when `codAmount > 0`, for both creation and
quoting — the API does not fall back to `cash`.

## Rate limiting

Headers `X-RateLimit-Limit` and `X-RateLimit-Remaining` are on every response.
On `429`, respect `Retry-After` (`1` for the per-second limit, `60` for the
per-minute limit). See [Authentication](AUTHENTICATION.md#rate-limits).

## Transport failures

`TransportException` implements `Psr\Http\Client\ClientExceptionInterface`, so
generic PSR-18 error handling also catches it.
