<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Unit\Support;

use Daika7ana\DragonStar\Exceptions\ApiException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ApiExceptionTest extends TestCase
{
    public function testScalarFieldErrorsAreNormalizedToLists(): void
    {
        $exception = ApiException::fromResponse(new Response(422, [], json_encode([
            'title' => 'Validation failed',
            'status' => 422,
            'code' => 'validation_failed',
            'errors' => [
                'weight' => 'required',
                'pieces' => ['must be 1-99', 42],
            ],
        ], JSON_THROW_ON_ERROR)));

        $this->assertSame([
            'weight' => ['required'],
            'pieces' => ['must be 1-99'],
        ], $exception->errors);
    }

    public function testRetryAfterIsTrimmed(): void
    {
        $exception = ApiException::fromResponse(new Response(429, ['Retry-After' => ' 60 '], '{}'));

        $this->assertSame(60, $exception->retryAfter);
    }

    public function testMissingCodeFallsBackToUnknownError(): void
    {
        $exception = ApiException::fromResponse(new Response(500, [], '{"title":"Server error"}'));

        $this->assertSame('unknown_error', $exception->errorCode);
        $this->assertSame(500, $exception->status);
    }
}
