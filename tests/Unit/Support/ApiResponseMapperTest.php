<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Unit\Support;

use Daika7ana\DragonStar\Exceptions\AuthenticationException;
use Daika7ana\DragonStar\Exceptions\UnexpectedStatusException;
use Daika7ana\DragonStar\Exceptions\ValidationException;
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

    public function testUnauthorizedResponseThrowsAuthenticationException(): void
    {
        $response = new Response(401, [], '{"title":"Unauthorized","status":401,"code":"unauthorized","detail":"Missing API key."}');

        try {
            ApiResponseMapper::decodeJson($response);
            $this->fail('Expected AuthenticationException to be thrown.');
        } catch (AuthenticationException $exception) {
            $this->assertSame($response, $exception->response);
            $this->assertSame(401, $exception->getCode());
            $this->assertSame('Missing API key.', $exception->getMessage());
            $this->assertTrue($exception->error?->isAuthenticationRelated() ?? false);
            $this->assertSame('unauthorized', $exception->error?->raw['code']);
        }
    }

    public function testValidationResponseThrowsValidationExceptionWithFieldErrors(): void
    {
        $response = new Response(422, [], json_encode([
            'title' => 'Validation failed',
            'status' => 422,
            'code' => 'validation_failed',
            'detail' => 'The request has invalid fields.',
            'errors' => [
                'weight' => 'required',
                'pieces' => ['must be 1-99', 42],
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            ApiResponseMapper::decodeJson($response);
            $this->fail('Expected ValidationException to be thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame($response, $exception->response);
            $this->assertSame(422, $exception->getCode());
            $this->assertSame('The request has invalid fields.', $exception->getMessage());
            $this->assertSame([
                'weight' => ['required'],
                'pieces' => ['must be 1-99'],
            ], $exception->errors);
            $this->assertSame(422, $exception->error?->code);
            $this->assertTrue($exception->error?->isValidationRelated() ?? false);
        }
    }

    public function testBadRequestIsTreatedAsValidationShaped(): void
    {
        $this->expectException(ValidationException::class);

        ApiResponseMapper::decodeJson(new Response(400, [], '{"detail":"Body is not JSON."}'));
    }

    public function testNonEmptyErrorsMapOnNonValidationStatusIsUnexpected(): void
    {
        $response = new Response(404, [], '{"code":"awb_not_found","errors":{"awb":["invalid"]}}');

        try {
            ApiResponseMapper::decodeJson($response);
            $this->fail('Expected UnexpectedStatusException to be thrown.');
        } catch (UnexpectedStatusException $exception) {
            $this->assertSame(404, $exception->getCode());
            $this->assertSame(['awb' => ['invalid']], $exception->error?->raw['errors']);
            $this->assertTrue($exception->error?->isValidationRelated() ?? false);
        }
    }

    public function testUnexpectedStatusThrowsUnexpectedStatusException(): void
    {
        $response = new Response(503, ['Retry-After' => ' 60 '], '{"title":"Service unavailable","status":503,"code":"service_unavailable"}');

        try {
            ApiResponseMapper::assertStatus($response, 200);
            $this->fail('Expected UnexpectedStatusException to be thrown.');
        } catch (UnexpectedStatusException $exception) {
            $this->assertSame($response, $exception->response);
            $this->assertSame(503, $exception->getCode());
            $this->assertSame('Service unavailable', $exception->getMessage());
            $this->assertSame(60, $exception->retryAfter);
            $this->assertSame(503, $exception->error?->code);
            $this->assertSame('service_unavailable', $exception->error?->raw['code']);
        }
    }

    public function testNonJsonErrorBodyFallsBackToHttpStatus(): void
    {
        try {
            ApiResponseMapper::decodeJson(new Response(503, [], 'Service Unavailable'));
            $this->fail('Expected UnexpectedStatusException to be thrown.');
        } catch (UnexpectedStatusException $exception) {
            $this->assertSame(503, $exception->error?->code);
            $this->assertSame([], $exception->error?->raw);
        }
    }

    public function testInvalidJsonSuccessBodyThrowsUnexpectedStatusException(): void
    {
        try {
            ApiResponseMapper::decodeJson(new Response(200, [], '<html>not json</html>'));
            $this->fail('Expected UnexpectedStatusException to be thrown.');
        } catch (UnexpectedStatusException $exception) {
            $this->assertSame(200, $exception->response?->getStatusCode());
            $this->assertSame('invalid_response', $exception->error?->raw['code']);
            $this->assertStringContainsString('Invalid response body', $exception->getMessage());
        }
    }

    public function testInvalidBodyHelperCarriesInvalidResponseCode(): void
    {
        $exception = ApiResponseMapper::invalidBody(new Response(200), 'missing "awb"');

        $this->assertSame(200, $exception->response?->getStatusCode());
        $this->assertSame('invalid_response', $exception->error?->raw['code']);
        $this->assertStringContainsString('missing "awb"', $exception->getMessage());
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
