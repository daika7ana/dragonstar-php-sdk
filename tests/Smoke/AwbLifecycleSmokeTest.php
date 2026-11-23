<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Smoke;

use Daika7ana\DragonStar\DTOs\Awb\AwbCreateRequest;
use Daika7ana\DragonStar\Enums\LabelFormat;
use Daika7ana\DragonStar\Enums\ShipmentType;
use PHPUnit\Framework\Attributes\Group;

#[Group('smoke')]
class AwbLifecycleSmokeTest extends SmokeTestCase
{
    /**
     * A one-way AWB covers create/track/label/ZPL; returnLegs() is omitted
     * because the API rejects it with 422 when the shipment has no return leg.
     */
    public function testCreateInspectAndDeleteAwb(): void
    {
        $this->requireWriteAccess('awb lifecycle');
        $client = $this->client();
        $awb = null;

        try {
            $created = $client->awb()->create($this->createRequest());
            $awb = $created->awb;

            $this->assertGreaterThan(0, $awb);
            $this->assertGreaterThan(0.0, $created->totalAmount);
            $this->assertSame(1, $created->pieces);

            $tracking = $client->awb()->track($awb);
            $this->assertNotNull($tracking->status);

            $label = $client->awb()->label($awb);
            $this->assertStringStartsWith('%PDF', $label->getContents());

            $a4 = $client->awb()->labelAs($awb, LabelFormat::A4);
            $this->assertStringStartsWith('%PDF', $a4->getContents());

            $zpl = $client->awb()->zplLabel($awb);
            $this->assertStringContainsString('^XA', $zpl->getContents());
        } finally {
            // no-residue guarantee: a failing delete throws out of here and fails the test
            if ($awb !== null) {
                $client->awb()->delete($awb);
            }
        }
    }

    public function testCreateForBranchAndDeleteAwb(): void
    {
        $this->requireWriteAccess('awb branch lifecycle');
        $client = $this->client();
        $branch = $client->directory()->branches()->branches->first();

        if ($branch === null) {
            $this->markTestSkipped('The account has no branches to create an AWB for.');
        }

        $awb = null;

        try {
            $created = $client->awb()->createForBranch($branch->branchId, $this->createRequest());
            $awb = $created->awb;

            $this->assertGreaterThan(0, $awb);
            $this->assertGreaterThan(0.0, $created->totalAmount);
        } finally {
            if ($awb !== null) {
                $client->awb()->delete($awb);
            }
        }
    }

    private function createRequest(): AwbCreateRequest
    {
        return new AwbCreateRequest(
            shipmentType: ShipmentType::Parcel,
            recipientName: 'SDK Smoke Test',
            recipientAddress: 'Str. Fabricii nr. 10',
            recipientCounty: 'Cluj',
            recipientCity: 'Cluj-Napoca',
            weight: 1.0,
            pieces: 1,
            notes: 'SDK smoke test, deleted immediately',
        );
    }
}
