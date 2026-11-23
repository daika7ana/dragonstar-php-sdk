<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Unit\Exceptions;

use Daika7ana\DragonStar\Exceptions\UnexpectedStatusException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class UnexpectedStatusExceptionTest extends TestCase
{
    public function testAcceptsNullResponse(): void
    {
        $exception = new UnexpectedStatusException();

        $this->assertNull($exception->response);
        $this->assertSame('Unexpected HTTP status', $exception->getMessage());
        $this->assertNull($exception->retryAfter);
        $this->assertNull($exception->error);
    }

    public function testDerivesMessageFromResponseStatus(): void
    {
        $exception = new UnexpectedStatusException(new Response(503));

        $this->assertSame(503, $exception->response?->getStatusCode());
        $this->assertSame(503, $exception->getCode());
        $this->assertSame('Unexpected HTTP status 503', $exception->getMessage());
    }

    public function testCodeIsZeroWithoutResponse(): void
    {
        $this->assertSame(0, (new UnexpectedStatusException())->getCode());
    }
}
