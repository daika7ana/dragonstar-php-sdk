<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Unit\DTOs\Common\Errors;

use Daika7ana\DragonStar\DTOs\Common\Errors\DragonStarError;
use PHPUnit\Framework\TestCase;

class DragonStarErrorTest extends TestCase
{
    public function testFromArrayMapsProblemDetails(): void
    {
        $payload = [
            'type' => 'https://api.curierdragonstar.ro/errors/validation-failed',
            'title' => 'Validation failed',
            'status' => 422,
            'code' => 'validation_failed',
            'detail' => 'The request has invalid fields.',
            'errors' => ['weight' => ['required']],
        ];

        $error = DragonStarError::fromArray($payload);

        $this->assertSame(422, $error->code);
        $this->assertSame('The request has invalid fields.', $error->message);
        $this->assertSame($payload, $error->raw);
        $this->assertSame('validation_failed', $error->raw['code']);
        $this->assertSame('', $error->id);
        $this->assertNull($error->context);
        $this->assertNull($error->component);
    }

    public function testFromArrayFallsBackToTitleAndFallbackCode(): void
    {
        $this->assertSame(503, DragonStarError::fromArray(['title' => 'Unavailable'], 503)->code);
        $this->assertSame('Unavailable', DragonStarError::fromArray(['title' => 'Unavailable'])->message);
        $this->assertSame('Dragon Star API error.', DragonStarError::fromArray([])->message);
        $this->assertSame('Bad request.', DragonStarError::fromArray(['detail' => 'Bad request.'])->message);
    }

    public function testValidationRelatedDetectsStatusAndFieldErrors(): void
    {
        $this->assertTrue(DragonStarError::fromArray([], 400)->isValidationRelated());
        $this->assertTrue(DragonStarError::fromArray([], 422)->isValidationRelated());
        $this->assertTrue(DragonStarError::fromArray(['errors' => ['awb' => ['required']]])->isValidationRelated());
        $this->assertFalse(DragonStarError::fromArray(['errors' => []])->isValidationRelated());
        $this->assertFalse(DragonStarError::fromArray(['detail' => 'Server error'], 500)->isValidationRelated());
    }

    public function testAuthenticationRelatedDetectsStatus(): void
    {
        $this->assertTrue(DragonStarError::fromArray([], 401)->isAuthenticationRelated());
        $this->assertTrue(DragonStarError::fromArray([], 403)->isAuthenticationRelated());
        $this->assertFalse(DragonStarError::fromArray([], 500)->isAuthenticationRelated());
    }
}
