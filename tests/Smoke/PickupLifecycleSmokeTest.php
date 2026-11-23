<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Smoke;

use Daika7ana\DragonStar\DTOs\Pickup\PickupCreateRequest;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;

#[Group('smoke')]
class PickupLifecycleSmokeTest extends SmokeTestCase
{
    public function testCreateAndCancelPickup(): void
    {
        $this->requireWriteAccess('pickup lifecycle');
        $client = $this->client();
        $pickupId = null;

        try {
            $pickupDate = new DateTimeImmutable('tomorrow');

            if (in_array((int) $pickupDate->format('N'), [6, 7], true)) {
                $pickupDate = $pickupDate->modify('next monday');
            }

            $pickup = $client->pickups()->create(new PickupCreateRequest(
                pickupDate: $pickupDate->format('Y-m-d'),
                pickupWindowStart: 10,
                pickupWindowEnd: 12,
                weight: 1.0,
                pickupAddress: 'Str. Fabricii nr. 10',
                pickupCounty: 'Cluj',
                pickupCity: 'Cluj-Napoca',
                pickupContact: 'SDK Smoke Test',
                pickupPhone: '0744123456',
                pickupEmail: 'smoke@example.test',
                parcels: 1,
                notes: 'SDK smoke test, cancelled immediately',
            ));

            $pickupId = $pickup->pickupId;

            $this->assertNotNull($pickupId);
            $this->assertGreaterThan(0, $pickupId);
        } finally {
            // no-residue guarantee: a failing cancel throws out of here and fails the test
            if ($pickupId !== null) {
                $client->pickups()->cancel($pickupId);
            }
        }
    }
}
