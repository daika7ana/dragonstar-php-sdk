<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Unit\Resources;

use Daika7ana\DragonStar\Tests\Support\TestClientFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ManifestResourceTest extends TestCase
{
    public function testCreateReturnsTheManifest(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(201, [], '{"manifestId":77,"shipmentCount":12}'),
        );

        $manifest = $client->manifests()->create();

        $this->assertSame(77, $manifest->manifestId);
        $this->assertSame(12, $manifest->shipmentCount);
        $this->assertSame('POST', $httpClient->lastRequest?->getMethod());
        $this->assertSame('/v1/manifests', $httpClient->lastRequest?->getUri()->getPath());
    }

    public function testCreateReturnsNullWhenThereWasNothingToClose(): void
    {
        [$client, ] = TestClientFactory::create(static fn() => new Response(200));

        $manifest = $client->manifests()->create();

        $this->assertNull($manifest->manifestId);
        $this->assertNull($manifest->shipmentCount);
    }

    public function testPdfReturnsADocument(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(200, ['Content-Type' => 'application/pdf'], '%PDF%'),
        );

        $document = $client->manifests()->pdf(77);

        $this->assertSame('application/pdf', $document->contentType);
        $this->assertSame('%PDF%', $document->getContents());
        $this->assertSame('/v1/manifests/77/pdf', $httpClient->lastRequest?->getUri()->getPath());
    }
}
