<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Support;

use Daika7ana\DragonStar\Exceptions\ApiException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

final class ApiResponseMapper
{
    /**
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    public static function decodeJson(ResponseInterface $response, int ...$expectedStatus): array
    {
        self::assertStatus($response, ...$expectedStatus);

        try {
            return JsonHelper::decode((string) $response->getBody());
        } catch (JsonException $e) {
            throw ApiException::invalidBody($response, $e->getMessage());
        }
    }

    /**
     * Decode a success response whose body may be empty.
     *
     * @return array<string, mixed>|null
     *
     * @throws ApiException
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
            throw ApiException::invalidBody($response, $e->getMessage());
        }
    }

    /**
     * @throws ApiException
     */
    public static function assertStatus(ResponseInterface $response, int ...$expectedStatus): void
    {
        $expectedStatuses = $expectedStatus === [] ? [200] : $expectedStatus;

        if (!in_array($response->getStatusCode(), $expectedStatuses, true)) {
            throw ApiException::fromResponse($response);
        }
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
}
