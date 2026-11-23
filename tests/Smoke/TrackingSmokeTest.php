<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Smoke;

use Daika7ana\DragonStar\DTOs\Awb\TrackingEvent;
use Daika7ana\DragonStar\Exceptions\UnexpectedStatusException;
use PHPUnit\Framework\Attributes\Group;

#[Group('smoke')]
class TrackingSmokeTest extends SmokeTestCase
{
    private const UNKNOWN_AWB = 99999999;

    public function testTrackReturnsCurrentStatusWithEvents(): void
    {
        $awb = $this->configuredAwb();

        if ($awb === null) {
            $this->markTestSkipped('DRAGONSTAR_SMOKE_AWB is not configured.');
        }

        $tracking = $this->client()->awb()->track($awb);

        $this->assertNotNull($tracking->status);
        $this->assertNotSame('', $tracking->status->description);

        foreach ($tracking->events as $event) {
            $this->assertInstanceOf(TrackingEvent::class, $event);
            $this->assertNotSame('', $event->description);
        }
    }

    public function testTrackUnknownAwbReportsNotFoundProblem(): void
    {
        try {
            $this->client()->awb()->track(self::UNKNOWN_AWB);
            $this->fail('Expected UnexpectedStatusException for an unknown AWB.');
        } catch (UnexpectedStatusException $exception) {
            $this->assertSame(404, $exception->response?->getStatusCode());
            $this->assertSame('awb_not_found', $exception->error?->raw['code'] ?? null);
        }
    }

    public function testTrackManyWithoutEventsReportsUnknownAwbs(): void
    {
        $knownAwb = $this->configuredAwb();
        $requested = $knownAwb === null ? [self::UNKNOWN_AWB] : [$knownAwb, self::UNKNOWN_AWB];

        $batch = $this->client()->awb()->trackMany($requested);

        $this->assertContains(self::UNKNOWN_AWB, $batch->notFound);

        if ($knownAwb === null) {
            $this->assertSame(0, $batch->results->count());

            return;
        }

        $this->assertSame(1, $batch->results->count());
        $first = $batch->results->first();

        $this->assertNotNull($first);
        $this->assertSame($knownAwb, $first->awb);
        $this->assertSame([], $first->events);
    }
}
