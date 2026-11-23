<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Smoke;

use PHPUnit\Framework\Attributes\Group;

/**
 * Live API check. Skipped unless DRAGONSTAR_TEST_API_KEY is set; the key must
 * be allowed to call from the machine's public IP.
 */
#[Group('smoke')]
class ClientSmokeTest extends SmokeTestCase
{
    public function testCountiesCanBeFetched(): void
    {
        $counties = $this->client()->directory()->counties();

        $this->assertGreaterThan(0, $counties->count());
        $this->assertContains('Cluj', $counties->all());
    }

    public function testLocalitiesCanBeFetchedForCounty(): void
    {
        $localities = $this->client()->directory()->localities('Cluj');

        $this->assertGreaterThan(0, $localities->count());
        $this->assertTrue($localities->contains('Cluj-Napoca'));
    }

    public function testLocalitiesWithDistancesCanBeFetchedForCounty(): void
    {
        $localities = $this->client()->directory()->localitiesWithDistances('Cluj');
        $clujNapoca = $localities->first(
            static fn($locality): bool => $locality->name === 'Cluj-Napoca',
        );

        $this->assertGreaterThan(0, $localities->count());
        $this->assertNotNull($clujNapoca);
        $this->assertSame(0, $clujNapoca->km);
    }

    public function testBranchesCanBeFetched(): void
    {
        $branches = $this->client()->directory()->branches();

        $this->assertGreaterThan(0, $branches->branches->count());

        foreach ($branches->branches as $branch) {
            $this->assertGreaterThan(0, $branch->branchId);
            $this->assertNotSame('', $branch->name);
        }
    }
}
