# Data Transfer Objects

DTOs live under `Daika7ana\DragonStar\DTOs`. Requests are `final readonly`
classes with a `toArray()` method; responses expose public readonly properties
and are built with `fromArray()`.

Request properties that are `null` are omitted from the JSON body. Boolean
flags have a `false` default and are always sent, so the payload states exactly
what the SDK was configured with.

## Request DTOs

### `DTOs\Awb\AwbCreateRequest`

Payload for `awb()->create()` and `awb()->createForBranch()`.

| Property | Type | Default | Notes |
| --- | --- | --- | --- |
| `shipmentType` | `ShipmentType` | — | Required |
| `recipientName` | `string` | — | Required, printed on the label |
| `recipientAddress` | `string` | — | Required, printed on the label |
| `recipientCounty` | `string` | — | Required; county **name**, e.g. `Cluj` |
| `recipientCity` | `string` | — | Required; locality from the nomenclature |
| `weight` | `?float` | `null` | kg; required for parcel/pallet, ignored for envelope |
| `pieces` | `?int` | `null` | 1–99; required for parcel, ignored otherwise |
| `length` | `?float` | `null` | cm; `length`, `width`, `height` are all-or-none |
| `width` | `?float` | `null` | cm |
| `height` | `?float` | `null` | cm |
| `codAmount` | `?float` | `null` | RON, 0–15000 |
| `codPaymentType` | `?CodPaymentType` | `null` | Required when `codAmount > 0`; there is no default |
| `insuredValue` | `?float` | `null` | Declared value, 0–15000 |
| `returnAwb` | `bool` | `false` | Signed return AWB |
| `returnDocuments` | `bool` | `false` | Return signed accompanying documents |
| `returnPackaging` | `bool` | `false` | Return packaging to the sender |
| `returnParcel` | `bool` | `false` | Return a parcel to the sender |
| `smsNotification` | `bool` | `false` | Requires a mobile `recipientPhone` |
| `openOnDelivery` | `bool` | `false` | Open-on-delivery |
| `saturdayDelivery` | `bool` | `false` | Saturday delivery |
| `deliverToBranch` | `bool` | `false` | Deliver to the courier's branch |
| `recipientPays` | `bool` | `false` | Transport paid by the recipient |
| `reverseShipment` | `bool` | `false` | Incompatible with `codPaymentType: account` |
| `notes` | `?string` | `null` | Max 1000 characters, printed on the label |
| `documentDetails` | `?string` | `null` | Max 1000 characters |
| `recipientContact` | `?string` | `null` | Max 100 characters |
| `recipientPhone` | `?string` | `null` | Max 50 characters |
| `recipientEmail` | `?string` | `null` | Max 100 characters |
| `recipientPostalCode` | `?string` | `null` | Max 20 characters |

```php
$request = new AwbCreateRequest(
    shipmentType: ShipmentType::Parcel,
    recipientName: 'Ion Popescu',
    recipientAddress: 'Str. Memorandumului nr. 28',
    recipientCounty: 'Cluj',
    recipientCity: 'Cluj-Napoca',
    weight: 5,
    pieces: 1,
    codAmount: 150,
    codPaymentType: CodPaymentType::Cash,
);

$request->toArray();
```

### `DTOs\Awb\AwbQuoteRequest`

Same shipment fields, without recipient identity, `pieces`, phone, email or
postal code.

| Property | Type | Default |
| --- | --- | --- |
| `shipmentType` | `ShipmentType` | — (required) |
| `recipientCounty` | `string` | — (required) |
| `recipientCity` | `string` | — (required) |
| `weight` | `?float` | `null` |
| `length`, `width`, `height` | `?float` | `null` |
| `codAmount` | `?float` | `null` |
| `codPaymentType` | `?CodPaymentType` | `null` |
| `insuredValue` | `?float` | `null` |
| `returnAwb` … `reverseShipment` | `bool` | `false` |
| `notes`, `documentDetails` | `?string` | `null` |

`codPaymentType` is required when `codAmount > 0`, and `account` cannot be
combined with `reverseShipment`.

### `DTOs\Pickup\PickupCreateRequest`

Payload for `pickups()->create()` and `createForBranch()`.

| Property | Type | Default | Notes |
| --- | --- | --- | --- |
| `pickupDate` | `string` | — | Required, `Y-m-d`, not in the past |
| `pickupWindowStart` | `int` | — | Required, 9–16 |
| `pickupWindowEnd` | `int` | — | Required, 10–17 |
| `weight` | `float` | — | Required, total kg |
| `pickupLocation` | `?string` | `null` | Makes the address fields required |
| `pickupAddress` | `?string` | `null` | Max 500 characters |
| `pickupCounty` | `?string` | `null` | County name |
| `pickupCity` | `?string` | `null` | Locality |
| `pickupContact` | `?string` | `null` | |
| `pickupPhone` | `?string` | `null` | |
| `pickupEmail` | `?string` | `null` | |
| `parcels` | `?int` | `null` | Required when `pallets` is absent/0 |
| `pallets` | `?int` | `null` | Required when `parcels` is absent/0 |
| `volume` | `?float` | `null` | Cubic metres |
| `notes` | `?string` | `null` | Max 1000 characters |

The API may move the pickup window: a start hour already passed today becomes
the next hour, and from 16:00 the pickup moves to the next day.

## Response DTOs

### `DTOs\Awb\AwbCreated`

| Property | Type |
| --- | --- |
| `awb` | `int` |
| `totalAmount` | `float` (VAT included) |
| `weight` | `?float` (recorded weight) |
| `pieces` | `?int` (recorded pieces) |
| `codAmount` | `?float` |
| `codPaymentType` | `?CodPaymentType` |

### `DTOs\Awb\Quote`

`baseRate`, `distanceCost`, `weightCost`, `insuranceCost` (`float`),
`isListRate` (`bool`), `netAmount`, `vatAmount`, `totalAmount` (`float`).

### `DTOs\Awb\Tracking`

| Property | Type | Notes |
| --- | --- | --- |
| `status` | `?TrackingStatus` | Current status |
| `events` | `list<TrackingEvent>` | Newest first; empty when `events=false` |
| `awb` | `?int` | Set on `trackMany()` results |

### `DTOs\Awb\TrackingStatus`

`statusCode` (`?ShipmentStatus`), `description` (`string`, English),
`descriptionRo` (`?string`), `timestamp` (`?string`, ISO 8601 with offset).

### `DTOs\Awb\TrackingEvent`

`statusCode` (`?ShipmentStatus`), `eventCode` (`?string`), `description`
(`string`), `isException` (`bool`), `timestamp` (`?string`), `location`
(`?TrackingLocation`).

`TrackingLocation`: `name` (`string`), `code` (`?string`).

An unknown future status code maps to `null` instead of throwing.

### `DTOs\Awb\TrackingBatch`

| Property | Type | Notes |
| --- | --- | --- |
| `results` | `Collection<int, Tracking>` | Each item has `->awb` |
| `notFound` | `list<int>` | Unknown or foreign AWBs |
| `count` | `int` | Result count as reported by the API |

### `DTOs\Awb\ReturnLeg`

`awb` (`int`), `type` (`string`), `typeCode` (`?int`), `status`
(`?TrackingStatus`), `lastEvent` (`?TrackingEvent`).

### `DTOs\Pickup\PickupCreated`

`pickupId` (`?int`) — `null` is possible on the branch endpoint, which
documents no response body.

### `DTOs\Manifest\ManifestCreated`

`manifestId` (`?int`, `null` when nothing was open), `shipmentCount` (`?int`).

### `DTOs\Directory\BranchList`

`branches` (`Collection<int, Branch>`), `masterId` (`?int`).

`Branch`: `branchId` (`int`), `name` (`string`), `county`, `city`, `address`
(`?string`).

### `DTOs\Directory\LocalityDistance`

`name` (`string`), `km` (`?int`), parsed from the API's `"nume:km"` strings.

### `DTOs\Common\Document`

| Property / method | Type | Notes |
| --- | --- | --- |
| `stream` | `StreamInterface` | Raw response body |
| `contentType` | `?string` | From the response header |
| `contentDisposition` | `?string` | From the response header |
| `getFilename($default)` | `string` | Parses RFC 5987/`filename=` |
| `getContents()` | `string` | Rewinds when seekable |
| `getDownloadHeaders($default)` | `array` | Ready for a framework response |

## Enums

### `Enums\ShipmentType`

`Envelope = 'envelope'`, `Parcel = 'parcel'`, `Pallet = 'pallet'`.

### `Enums\CodPaymentType`

`Cash = 'cash'`, `BankOrder = 'bankOrder'`, `Cheque = 'cheque'`,
`Account = 'account'`.

### `Enums\LabelFormat`

`A4 = 'A4'`, `A6 = 'A6'`.

### `Enums\ShipmentStatus`

| Case | Value | Meaning |
| --- | --- | --- |
| `Generated` | 1 | Generat |
| `DeliveryException` | 2 | Excepție |
| `Delivered` | 3 | Livrat |
| `Notified` | 4 | Avizat |
| `PickedUp` | 5 | Preluat |
| `InTransit` | 6 | În tranzit |
| `Returned` | 7 | Returnat |
| `OutForDelivery` | 18 | Spre livrare |
| `Redirected` | 19 | Redirecționat |
| `DestinationCenter` | 21 | În centru destinație |

## Collection helper methods

Every list returned by the SDK is a `Daika7ana\SimpleCollections\Collection`
from the [simple-collections](https://github.com/daika7ana/simple-collections)
package. It implements `Countable`, `IteratorAggregate`, `ArrayAccess` and
`JsonSerializable`, is generically typed, and ships Laravel-style helpers:
`count`, `first`, `last`, `get`, `keys`, `values`, `has`, `filter`, `reject`,
`map`, `mapWithKeys`, `pluck`, `sortBy`, `sum`, `push`, `prepend`, `each`,
`toArray`, `all` and more.

```php
use Daika7ana\DragonStar\DTOs\Awb\Tracking;

$awbs = $batch->results->map(fn (Tracking $t) => $t->awb)->all();
```
