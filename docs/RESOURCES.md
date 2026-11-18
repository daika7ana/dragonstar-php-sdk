# Resources

Every resource is lazy and reachable from the client:

```php
$client->awb();       // shipments
$client->pickups();   // pickup orders
$client->manifests(); // borderouri
$client->directory(); // nomenclature
```

Request payloads accept either a typed DTO or a plain array with the same
field names. Responses are always DTOs.

## `awb()`

`Daika7ana\DragonStar\Resources\AwbResource`

### `create(AwbCreateRequest|array $request): AwbCreated`

`POST /v1/awb` — creates a shipment. Expects `201`.

```php
$created = $client->awb()->create(new AwbCreateRequest(
    shipmentType: ShipmentType::Parcel,
    recipientName: 'Ion Popescu',
    recipientAddress: 'Str. Memorandumului nr. 28',
    recipientCounty: 'Cluj',
    recipientCity: 'Cluj-Napoca',
    weight: 5,
    pieces: 1,
));
```

`AwbCreated` exposes the recorded values, which can differ from the request:
`weight` is always `0.5` for an envelope, `pieces` is always `1` for an
envelope or pallet, and `codPaymentType` may be changed by the account
configuration.

### `createForBranch(int $branchId, AwbCreateRequest|array $request): AwbCreated`

`POST /v1/awb/branch` — the sender is the branch. The SDK adds `pcId`.

```php
use Daika7ana\DragonStar\DTOs\Awb\AwbCreateRequest;

$request = new AwbCreateRequest(
    shipmentType: ShipmentType::Parcel,
    recipientName: 'Ion Popescu',
    recipientAddress: 'Str. Memorandumului nr. 28',
    recipientCounty: 'Cluj',
    recipientCity: 'Cluj-Napoca',
    weight: 5,
    pieces: 1,
);

$created = $client->awb()->createForBranch(454, $request);
```

### `quote(AwbQuoteRequest|array $request): Quote`

`POST /v1/awb/quote` — estimates the cost without creating anything.

### `quoteForBranch(int $branchId, AwbQuoteRequest|array $request): Quote`

`POST /v1/awb/quote/branch`.

### `track(int $awb, bool $includeEvents = true): Tracking`

`GET /v1/awb/{awb}` — current status plus the route, newest event first, capped
at 100 events.

```php
$tracking = $client->awb()->track(94348148, includeEvents: false);

$tracking->status?->statusCode;    // ShipmentStatus enum
$tracking->status?->description;   // English
$tracking->status?->descriptionRo; // Romanian
```

`includeEvents: false` sends `?events=false` and leaves `$tracking->events`
empty.

### `trackMany(array $awbs, bool $includeEvents = false): TrackingBatch`

`POST /v1/awb/tracking` — up to 100 AWBs. Results keep the requested order.
Unknown or foreign AWBs appear in `notFound`.

```php
$batch->count;      // number of results
$batch->results;    // Collection<int, Tracking>; each has ->awb
$batch->notFound;   // list<int>
```

Unlike `track()`, events are excluded by default; pass `includeEvents: true`.

### `delete(int $awb): void`

`DELETE /v1/awb/{awb}` — expects `204`. Only possible while the shipment has
not been picked up, added to a manifest or invoiced. Otherwise the API answers
`409` with `awb_not_deletable`.

### `returnLegs(int $awb): Collection<int, ReturnLeg>`

`GET /v1/awb/{awb}/return` — return shipments linked to the AWB.

### `label(int $awb): Document`

`GET /v1/awb/{awb}/label` — the account's configured label format.

### `labelAs(int $awb, LabelFormat $format): Document`

`GET /v1/awb/{awb}/label/{format}` — `LabelFormat::A4` or `LabelFormat::A6`.
The format is case-sensitive in the API; the enum guarantees the correct case.

### `zplLabel(int $awb, int $piece = 1): Document`

`GET /v1/awb/{awb}/label/zpl/{nb}` — raw ZPL for one piece. Piece `1` carries
the AWB barcode. The payload contains only `^XA`…`^XZ`; printer width, length
and darkness come from the printer configuration (100×150 mm, 203 DPI).

### Documents

`Document` exposes:

```php
$document->contentType;          // "application/pdf", "application/octet-stream", …
$document->getFilename('awb.pdf');
$document->getContents();        // rewinds the stream first when seekable
$document->getDownloadHeaders('awb.pdf');
```

## `pickups()`

`Daika7ana\DragonStar\Resources\PickupResource`

### `create(PickupCreateRequest|array $request): PickupCreated`

`POST /v1/pickups` — expects `201`, returns `{"pickupId": 12345}`.

```php
$pickup = $client->pickups()->create(new PickupCreateRequest(
    pickupDate: '2026-11-19',
    pickupWindowStart: 10,
    pickupWindowEnd: 14,
    weight: 10,
    pickupLocation: 'Depozit Cluj',
    pickupAddress: 'Str. Fabricii nr. 10',
    parcels: 2,
));
```

Keep `$pickup->pickupId`; it is needed to cancel.

### `createForBranch(int $branchId, PickupCreateRequest|array $request): PickupCreated`

`POST /v1/pickups/branch`. This endpoint documents no response body, so
`pickupId` may be `null` here; the request itself is still confirmed with `201`.

### `cancel(int $pickupId): void`

`DELETE /v1/pickups/{pickup}` — expects `204`, and also works after the order
was sent to a courier. A refused, collected or already cancelled pickup yields
`409` with `pickup_not_cancellable`.

## `manifests()`

`Daika7ana\DragonStar\Resources\ManifestResource`

### `create(): ManifestCreated`

`POST /v1/manifests` — closes every shipment that is not in a manifest yet.
Expects `200` or `201`.

```php
$manifest = $client->manifests()->create();

$manifest->manifestId;    // int|null; null when nothing was open
$manifest->shipmentCount; // int|null
```

Shipments in a manifest can no longer be deleted.

### `pdf(int $manifestId): Document`

`GET /v1/manifests/{manifest}/pdf`.

## `directory()`

`Daika7ana\DragonStar\Resources\DirectoryResource`

### `counties(): Collection<string, string>`

`GET /v1/counties` — key is the code, value is the name:

```php
$counties->get('CJ'); // "Cluj"
```

Everywhere else in the API the **value** is used, in request bodies and in the
path segment of the locality routes.

### `localities(string $county): Collection<int, string>`

`GET /v1/counties/{county}/localities` — county name, URL-encoded by the SDK.
Case and diacritics do not matter to the API.

### `localitiesWithDistances(string $county): Collection<int, LocalityDistance>`

`GET /v1/counties/{county}/localities-km` — the API returns `"Apahida:14"`;
the SDK parses it into `->name` and `->km`.

### `branches(): BranchList`

`GET /v1/branches` — branches the master account may ship for.

```php
$branches->masterId;
$branches->branches;   // Collection<int, Branch>
$branches->branches->first()?->branchId;
```
