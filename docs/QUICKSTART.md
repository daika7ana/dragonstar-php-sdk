# Quickstart

This guide takes you from install to a printed label.

## 1. Install

```bash
composer require daika7ana/dragonstar-php-sdk
```

## 2. Create a client

```php
use Daika7ana\DragonStar\Client;

$client = Client::create(apiKey: 'your-api-key');
```

The key must be allowlisted for your machine's public IP.

## 3. Look up the destination

Counties and localities are the API's own nomenclature. Requests take the
county **name**, not its code:

```php
$counties = $client->directory()->counties();

echo $counties->get('CJ'); // Cluj

$localities = $client->directory()->localities('Cluj');

foreach ($localities as $locality) {
    echo $locality, PHP_EOL;
}
```

## 4. Estimate the cost

```php
use Daika7ana\DragonStar\DTOs\Awb\AwbQuoteRequest;
use Daika7ana\DragonStar\Enums\ShipmentType;

$quote = $client->awb()->quote(new AwbQuoteRequest(
    shipmentType: ShipmentType::Parcel,
    recipientCounty: 'Cluj',
    recipientCity: 'Cluj-Napoca',
    weight: 5,
    length: 40,
    width: 30,
    height: 20,
    codAmount: 150,
));

echo $quote->totalAmount; // RON, VAT included
```

`quote()` creates nothing. `isListRate` tells you whether the list price was
used instead of a negotiated tariff.

## 5. Create the AWB

```php
use Daika7ana\DragonStar\DTOs\Awb\AwbCreateRequest;
use Daika7ana\DragonStar\Enums\CodPaymentType;
use Daika7ana\DragonStar\Enums\ShipmentType;

$created = $client->awb()->create(new AwbCreateRequest(
    shipmentType: ShipmentType::Parcel,
    recipientName: 'Ion Popescu',
    recipientAddress: 'Str. Memorandumului nr. 28',
    recipientCounty: 'Cluj',
    recipientCity: 'Cluj-Napoca',
    weight: 5,
    pieces: 1,
    codAmount: 150,
    codPaymentType: CodPaymentType::Cash,
    recipientPhone: '0722123456',
    notes: 'Fragil',
));

echo $created->awb;         // 94348148
echo $created->totalAmount; // RON, VAT included
```

## 6. Download the label

```php
use Daika7ana\DragonStar\Enums\LabelFormat;

$label = $client->awb()->label($created->awb);
file_put_contents($label->getFilename('awb.pdf'), $label->getContents());

// Or force a paper size
$a6 = $client->awb()->labelAs($created->awb, LabelFormat::A6);

// Raw ZPL for a 100×150 mm, 203 DPI printer; 1 = the piece with the AWB barcode
$zpl = $client->awb()->zplLabel($created->awb, 1);
```

## 7. Track it

```php
$tracking = $client->awb()->track($created->awb);

echo $tracking->status?->descriptionRo;

foreach ($tracking->events as $event) {
    printf("%s %s %s\n", $event->timestamp, $event->description, $event->location?->name);
}
```

Skip the route with `track($awb, includeEvents: false)`, or track up to 100
AWBs at once:

```php
$batch = $client->awb()->trackMany([94348148, 94348149]);

foreach ($batch->results as $result) {
    echo $result->awb, ' ', $result->status?->description, PHP_EOL;
}

// AWBs that do not exist or belong to another client
$batch->notFound;
```

## 8. Close the day

```php
$pickup = $client->pickups()->create([
    'pickupDate' => '2026-11-19',
    'pickupWindowStart' => 10,
    'pickupWindowEnd' => 14,
    'pickupLocation' => 'Depozit Cluj',
    'pickupAddress' => 'Str. Fabricii nr. 10',
    'pickupCounty' => 'Cluj',
    'pickupCity' => 'Cluj-Napoca',
    'pickupContact' => 'Maria Ionescu',
    'pickupPhone' => '0744123456',
    'parcels' => 2,
    'weight' => 10,
]);

$manifest = $client->manifests()->create();

if ($manifest->manifestId !== null) {
    $pdf = $client->manifests()->pdf($manifest->manifestId);
    file_put_contents('manifest.pdf', $pdf->getContents());
}
```

## 9. Errors

```php
use Daika7ana\DragonStar\Exceptions\AuthenticationException;
use Daika7ana\DragonStar\Exceptions\UnexpectedStatusException;
use Daika7ana\DragonStar\Exceptions\ValidationException;

try {
    $client->awb()->track(94348148);
} catch (ValidationException $e) {
    echo $e->error?->raw['code']; // validation_failed
    var_dump($e->errors);         // field errors
} catch (AuthenticationException|UnexpectedStatusException $e) {
    echo $e->error?->raw['code']; // stable, e.g. awb_not_found
    echo $e->getMessage();        // human-readable detail
}
```

See [Errors](ERRORS.md) for all codes.
