<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Unit\Config;

use Daika7ana\DragonStar\Config\ClientConfig;
use PHPUnit\Framework\TestCase;

class ClientConfigTest extends TestCase
{
    public function testDefaultsTargetProductionWithNoKeyOrTimeout(): void
    {
        $config = new ClientConfig();

        $this->assertSame('https://api.curierdragonstar.ro', $config->baseUrl);
        $this->assertSame('', $config->apiKey);
        $this->assertNull($config->timeout);
    }

    public function testWithMethodsCloneAndTrimTheBaseUrl(): void
    {
        $config = new ClientConfig();
        $updated = $config->withBaseUrl('https://proxy.example/')->withApiKey('key')->withTimeout(5.0);

        $this->assertNotSame($config, $updated);
        $this->assertSame('https://api.curierdragonstar.ro', $config->baseUrl);
        $this->assertSame('', $config->apiKey);
        $this->assertNull($config->timeout);
        $this->assertSame('https://proxy.example', $updated->baseUrl);
        $this->assertSame('key', $updated->apiKey);
        $this->assertSame(5.0, $updated->timeout);
        $this->assertNull($updated->withTimeout(null)->timeout);
    }
}
