<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Unit\Support;

use Daika7ana\DragonStar\Exceptions\ApiException;
use Daika7ana\DragonStar\Support\ApiResponseMapper;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ApiResponseMapperTest extends TestCase
{
    public function testDecodeJsonAcceptsAnyExpectedStatus(): void
    {
        $data = ApiResponseMapper::decodeJson(new Response(201, [], '{"id":7}'), 200, 201);

        $this->assertSame(['id' => 7], $data);
    }

    public function testUnexpectedStatusThrowsApiException(): void
    {
        $this->expectException(ApiException::class);

        ApiResponseMapper::assertStatus(new Response(200), 201);
    }

    public function testEmptyBodyDecodesToNull(): void
    {
        $this->assertNull(ApiResponseMapper::decodeJsonOrNull(new Response(204), 204));
        $this->assertSame([], ApiResponseMapper::decodeJsonOrNull(new Response(200, [], '  '), 200) ?? []);
    }

    public function testListHelpersSkipMalformedValues(): void
    {
        $this->assertSame(
            [['id' => 1]],
            ApiResponseMapper::listOfArrays([['id' => 1], 'invalid', null]),
        );
        $this->assertSame(['a', '1'], ApiResponseMapper::listOfStrings(['a', 1, [], null]));
        $this->assertSame([1, 2], ApiResponseMapper::listOfInts([1, '2', 'x', [], null]));
    }
}
