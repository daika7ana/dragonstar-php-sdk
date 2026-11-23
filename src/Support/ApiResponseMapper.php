<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Support;

use Daika7ana\DragonStar\DTOs\Common\Errors\DragonStarError;
use Daika7ana\DragonStar\Exceptions\AuthenticationException;
use Daika7ana\DragonStar\Exceptions\UnexpectedStatusException;
use Daika7ana\DragonStar\Exceptions\ValidationException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

final class ApiResponseMapper
{
    /**
     * @return array<string, mixed>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     */
    public static function decodeJson(ResponseInterface $response, int ...$expectedStatus): array
    {
        self::assertStatus($response, ...$expectedStatus);

        try {
            return JsonHelper::decode((string) $response->getBody());
        } catch (JsonException $e) {
            throw self::invalidBody($response, $e->getMessage());
        }
    }

    /**
     * Decode a success response whose body may be empty.
     *
     * @return array<string, mixed>|null
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     */
    public static function decodeJsonOrNull(ResponseInterface $response, int ...$expectedStatus): ?array
    {
        self::assertStatus($response, ...$expectedStatus);

        $body = (string) $response->getBody();

        if (trim($body) === '') {
            return null;
        }

        try {
            return JsonHelper::decode($body);
        } catch (JsonException $e) {
            throw self::invalidBody($response, $e->getMessage());
        }
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     */
    public static function assertStatus(ResponseInterface $response, int ...$expectedStatus): void
    {
        $expectedStatuses = $expectedStatus === [] ? [200] : $expectedStatus;
        $status = $response->getStatusCode();

        if (in_array($status, $expectedStatuses, true)) {
            return;
        }

        $payload = self::body($response);
        $error = DragonStarError::fromArray($payload, $status);
        $retryAfter = self::retryAfter($response);

        if ($status === 401) {
            throw new AuthenticationException(
                $error->message,
                response: $response,
                retryAfter: $retryAfter,
                error: $error,
            );
        }

        if ($status === 400 || $status === 422) {
            throw new ValidationException(
                errors: self::normalizeErrors($payload['errors'] ?? null),
                message: $error->message,
                response: $response,
                retryAfter: $retryAfter,
                error: $error,
            );
        }

        throw new UnexpectedStatusException(
            $response,
            $payload === [] ? '' : $error->message,
            retryAfter: $retryAfter,
            error: $error,
        );
    }

    public static function invalidBody(ResponseInterface $response, string $reason): UnexpectedStatusException
    {
        return new UnexpectedStatusException(
            $response,
            'Invalid response body: ' . $reason,
            retryAfter: self::retryAfter($response),
            error: DragonStarError::fromArray([
                'status' => $response->getStatusCode(),
                'code' => 'invalid_response',
                'title' => 'Invalid response body',
                'detail' => $reason,
            ], $response->getStatusCode()),
        );
    }

    public static function errorFromResponse(ResponseInterface $response): DragonStarError
    {
        return DragonStarError::fromArray(self::body($response), $response->getStatusCode());
    }

    public static function retryAfter(ResponseInterface $response): ?int
    {
        $value = trim($response->getHeaderLine('Retry-After'));

        return ctype_digit($value) ? (int) $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function arrayOrEmpty(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listOfArrays(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn(mixed $item): bool => is_array($item)));
    }

    /**
     * @return list<string>
     */
    public static function listOfStrings(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (is_scalar($item)) {
                $items[] = (string) $item;
            }
        }

        return $items;
    }

    /**
     * @return list<int>
     */
    public static function listOfInts(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (is_int($item) || (is_string($item) && ctype_digit($item))) {
                $items[] = (int) $item;
            }
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private static function body(ResponseInterface $response): array
    {
        try {
            return JsonHelper::decode((string) $response->getBody());
        } catch (JsonException) {
            return [];
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private static function normalizeErrors(mixed $errors): array
    {
        if (!is_array($errors)) {
            return [];
        }

        $normalized = [];

        foreach ($errors as $field => $messages) {
            if (!is_string($field)) {
                continue;
            }

            if (is_string($messages)) {
                $normalized[$field] = [$messages];

                continue;
            }

            if (!is_array($messages)) {
                continue;
            }

            $normalized[$field] = array_values(array_filter(
                $messages,
                static fn(mixed $message): bool => is_string($message),
            ));
        }

        return $normalized;
    }
}
