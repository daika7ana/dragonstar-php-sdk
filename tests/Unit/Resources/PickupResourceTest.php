<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Unit\Resources;

use Daika7ana\DragonStar\Exceptions\ApiException;
use Daika7ana\DragonStar\Tests\Support\TestClientFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class PickupResourceTest extends TestCase
{
    public function testCreateReturnsThePickupId(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(201, [], '{"pickupId":12345}'),
        );

        $pickup = $client->pickups()->create([
            'pickupDate' => '2026-11-19',
            'pickupWindowStart' => 10,
            'pickupWindowEnd' => 14,
            'weight' => 10,
        ]);

        $this->assertSame(12345, $pickup->pickupId);
        $this->assertSame('POST', $httpClient->lastRequest?->getMethod());
        $this->assertSame('/v1/pickups', $httpClient->lastRequest?->getUri()->getPath());
        $this->assertSame(
            '{"pickupDate":"2026-11-19","pickupWindowStart":10,"pickupWindowEnd":14,"weight":10}',
            (string) $httpClient->lastRequest?->getBody(),
        );
    }

    public function testCreateRejectsASuccessBodyWithoutThePickupId(): void
    {
        [$client, ] = TestClientFactory::create(static fn() => new Response(201, [], '{}'));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('missing the required field "pickupId"');

        $client->pickups()->create([
            'pickupDate' => '2026-11-19',
            'pickupWindowStart' => 10,
            'pickupWindowEnd' => 14,
            'weight' => 10,
        ]);
    }

    public function testCreateRejectsAnEmptyBody(): void
    {
        [$client, ] = TestClientFactory::create(static fn() => new Response(201));

        $this->expectException(ApiException::class);

        $client->pickups()->create([
            'pickupDate' => '2026-11-19',
            'pickupWindowStart' => 10,
            'pickupWindowEnd' => 14,
            'weight' => 10,
        ]);
    }

    public function testCreateForBranchAddsPcIdAndToleratesAnEmptyBody(): void
    {
        [$client, $httpClient] = TestClientFactory::create(static fn() => new Response(201));

        $pickup = $client->pickups()->createForBranch(454, [
            'pickupDate' => '2026-11-19',
            'pickupWindowStart' => 10,
            'pickupWindowEnd' => 14,
            'weight' => 10,
        ]);

        $this->assertNull($pickup->pickupId);
        $this->assertSame('/v1/pickups/branch', $httpClient->lastRequest?->getUri()->getPath());
        $this->assertStringContainsString('"pcId":454', (string) $httpClient->lastRequest?->getBody());
    }

    public function testCancelAcceptsNoContent(): void
    {
        [$client, $httpClient] = TestClientFactory::create(static fn() => new Response(204));

        $client->pickups()->cancel(12345);

        $this->assertSame('DELETE', $httpClient->lastRequest?->getMethod());
        $this->assertSame('/v1/pickups/12345', $httpClient->lastRequest?->getUri()->getPath());
    }

    public function testCancellingACollectedPickupThrowsApiException(): void
    {
        [$client, ] = TestClientFactory::create(
            static fn() => new Response(409, [], json_encode([
                'type' => 'https://api.curierdragonstar.ro/errors/pickup-not-cancellable',
                'title' => 'Conflict',
                'status' => 409,
                'code' => 'pickup_not_cancellable',
                'detail' => 'The pickup has already been collected.',
            ], JSON_THROW_ON_ERROR)),
        );

        try {
            $client->pickups()->cancel(12345);
            $this->fail('Expected ApiException to be thrown.');
        } catch (ApiException $exception) {
            $this->assertSame(409, $exception->status);
            $this->assertSame('pickup_not_cancellable', $exception->errorCode);
        }
    }
}
