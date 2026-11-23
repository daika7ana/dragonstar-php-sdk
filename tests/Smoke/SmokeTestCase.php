<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Smoke;

use Daika7ana\DragonStar\Client;
use PHPUnit\Framework\TestCase;

abstract class SmokeTestCase extends TestCase
{
    private ?Client $smokeClient = null;

    protected function client(): Client
    {
        if ($this->smokeClient instanceof Client) {
            return $this->smokeClient;
        }

        $apiKey = getenv('DRAGONSTAR_TEST_API_KEY');

        if ($apiKey === false || $apiKey === '') {
            $this->markTestSkipped('DRAGONSTAR_TEST_API_KEY is not configured.');
        }

        $this->smokeClient = Client::create(apiKey: $apiKey);

        return $this->smokeClient;
    }

    protected function requireWriteAccess(string $workflow): void
    {
        $value = getenv('DRAGONSTAR_SMOKE_ENABLE_WRITES');
        $enabled = is_string($value) && in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);

        if (! $enabled) {
            $this->markTestSkipped(sprintf(
                'Skipping %s because DRAGONSTAR_SMOKE_ENABLE_WRITES is not enabled.',
                $workflow,
            ));
        }
    }

    protected function configuredAwb(): ?int
    {
        $awb = getenv('DRAGONSTAR_SMOKE_AWB');

        return is_string($awb) && ctype_digit($awb) ? (int) $awb : null;
    }
}
