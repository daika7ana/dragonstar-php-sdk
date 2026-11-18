<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Unit\DTOs;

use Daika7ana\DragonStar\DTOs\Awb\AwbCreateRequest;
use Daika7ana\DragonStar\DTOs\Awb\AwbCreated;
use Daika7ana\DragonStar\DTOs\Awb\Tracking;
use Daika7ana\DragonStar\DTOs\Awb\TrackingBatch;
use Daika7ana\DragonStar\DTOs\Directory\LocalityDistance;
use Daika7ana\DragonStar\DTOs\Manifest\ManifestCreated;
use Daika7ana\DragonStar\DTOs\Pickup\PickupCreateRequest;
use Daika7ana\DragonStar\Enums\CodPaymentType;
use Daika7ana\DragonStar\Enums\ShipmentStatus;
use Daika7ana\DragonStar\Enums\ShipmentType;
use PHPUnit\Framework\TestCase;

class DtoNullabilityTest extends TestCase
{
    public function testCreateRequestOmitsNullsButKeepsFalseFlags(): void
    {
        $payload = (new AwbCreateRequest(
            shipmentType: ShipmentType::Parcel,
            recipientName: 'Ion Popescu',
            recipientAddress: 'Str. Memorandumului nr. 28',
            recipientCounty: 'Cluj',
            recipientCity: 'Cluj-Napoca',
            weight: 5,
            pieces: 1,
            codPaymentType: CodPaymentType::BankOrder,
        ))->toArray();

        $this->assertSame('parcel', $payload['shipmentType']);
        $this->assertSame('bankOrder', $payload['codPaymentType']);
        $this->assertSame(5.0, $payload['weight']);
        $this->assertSame(1, $payload['pieces']);
        $this->assertFalse($payload['reverseShipment']);
        $this->assertArrayNotHasKey('length', $payload);
        $this->assertArrayNotHasKey('insuredValue', $payload);
        $this->assertArrayNotHasKey('notes', $payload);
        $this->assertArrayNotHasKey('recipientPhone', $payload);
    }

    public function testPickupRequestOmitsNulls(): void
    {
        $payload = (new PickupCreateRequest(
            pickupDate: '2026-11-19',
            pickupWindowStart: 10,
            pickupWindowEnd: 14,
            weight: 10,
            parcels: 2,
        ))->toArray();

        $this->assertSame([
            'pickupDate' => '2026-11-19',
            'pickupWindowStart' => 10,
            'pickupWindowEnd' => 14,
            'weight' => 10.0,
            'parcels' => 2,
        ], $payload);
    }

    public function testAwbCreatedCastsNumbersAndUnknownPaymentTypes(): void
    {
        $created = AwbCreated::fromArray([
            'awb' => 94348148,
            'totalAmount' => '9.83',
            'weight' => null,
            'pieces' => 1,
            'codPaymentType' => 'cash',
        ]);

        $this->assertSame(94348148, $created->awb);
        $this->assertSame(9.83, $created->totalAmount);
        $this->assertNull($created->weight);
        $this->assertSame(CodPaymentType::Cash, $created->codPaymentType);

        $unknown = AwbCreated::fromArray(['awb' => 1, 'totalAmount' => 1, 'codPaymentType' => 'something_new']);
        $this->assertNull($unknown->codPaymentType);
    }

    public function testTrackingMapsUnknownStatusToNull(): void
    {
        $tracking = Tracking::fromArray([
            'status' => ['statusCode' => 999, 'description' => 'Future status'],
        ]);

        $this->assertNull($tracking->status?->statusCode);
        $this->assertSame('Future status', $tracking->status?->description);
        $this->assertSame([], $tracking->events);

        $known = Tracking::fromArray(['status' => ['statusCode' => 18, 'description' => 'Out for delivery']]);
        $this->assertSame(ShipmentStatus::OutForDelivery, $known->status?->statusCode);
    }

    public function testTrackingBatchDefaultsToAnEmptyCollection(): void
    {
        $batch = TrackingBatch::fromArray([]);

        $this->assertSame(0, $batch->count);
        $this->assertCount(0, $batch->results);
        $this->assertSame([], $batch->notFound);
    }

    public function testManifestWithoutIdIsNull(): void
    {
        $manifest = ManifestCreated::fromArray([]);

        $this->assertNull($manifest->manifestId);
        $this->assertNull($manifest->shipmentCount);
    }

    public function testLocalityDistanceParsing(): void
    {
        $locality = LocalityDistance::fromString('Cluj-Napoca:0');

        $this->assertSame('Cluj-Napoca', $locality->name);
        $this->assertSame(0, $locality->km);

        $withoutDistance = LocalityDistance::fromString('Apahida');
        $this->assertSame('Apahida', $withoutDistance->name);
        $this->assertNull($withoutDistance->km);
    }
}
