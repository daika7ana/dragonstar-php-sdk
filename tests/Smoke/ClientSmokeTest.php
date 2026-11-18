<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Smoke;

use Daika7ana\DragonStar\Client;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Live API check. Skipped unless DRAGONSTAR_TEST_API_KEY is set; the key must
 * be allowed to call from the machine's public IP.
 */
#[Group('smoke')]
class ClientSmokeTest extends TestCase
{
    public function testCountiesCanBeFetched(): void
    {
        $apiKey = getenv('DRAGONSTAR_TEST_API_KEY');

        if ($apiKey === false || $apiKey === '') {
            $this->markTestSkipped('DRAGONSTAR_TEST_API_KEY is not configured.');
        }

        $counties = Client::create(apiKey: $apiKey)->directory()->counties();

        $this->assertGreaterThan(0, $counties->count());
        $this->assertContains('Cluj', $counties->all());
    }
}
