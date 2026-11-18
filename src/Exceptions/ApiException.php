<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Exceptions;

use Daika7ana\DragonStar\Support\JsonHelper;
use JsonException;
use Psr\Http\Message\ResponseInterface;

/**
 * An error response from the API, following RFC 9457.
 *
 * The `errorCode` is stable and is the field to branch on; `detail` is
 * human-readable and may change.
 */
class ApiException extends DragonStarException
{
    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode = 'unknown_error',
        public readonly string $type = '',
        public readonly string $title = '',
        public readonly string $detail = '',
        public readonly array $errors = [],
        public readonly ?int $retryAfter = null,
        public readonly ?ResponseInterface $response = null,
        string $message = '',
    ) {
        parent::__construct(
            $message !== '' ? $message : self::buildMessage($title, $detail, $status, $errorCode),
            $status,
        );
    }

    public static function fromResponse(ResponseInterface $response): self
    {
        $status = $response->getStatusCode();
        $retryAfter = self::intHeader($response, 'Retry-After');

        try {
            $payload = JsonHelper::decode((string) $response->getBody());
        } catch (JsonException) {
            return new self(
                status: $status,
                title: $response->getReasonPhrase(),
                response: $response,
                retryAfter: $retryAfter,
            );
        }

        return new self(
            status: $status,
            errorCode: self::stringField($payload, 'code', 'unknown_error'),
            type: self::stringField($payload, 'type'),
            title: self::stringField($payload, 'title'),
            detail: self::stringField($payload, 'detail'),
            errors: self::errorsField($payload['errors'] ?? null),
            retryAfter: $retryAfter,
            response: $response,
        );
    }

    /**
     * The HTTP status is 200, but the body is not a JSON object.
     */
    public static function invalidBody(ResponseInterface $response, string $reason): self
    {
        return new self(
            status: $response->getStatusCode(),
            errorCode: 'invalid_response',
            title: 'Invalid response body',
            detail: $reason,
            response: $response,
        );
    }

    private static function buildMessage(string $title, string $detail, int $status, string $errorCode): string
    {
        $text = $detail !== '' ? $detail : ($title !== '' ? $title : 'HTTP error');

        return sprintf('%s (HTTP %d, %s)', $text, $status, $errorCode);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function stringField(array $payload, string $key, string $default = ''): string
    {
        $value = $payload[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    /**
     * @return array<string, list<string>>
     */
    private static function errorsField(mixed $errors): array
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

    private static function intHeader(ResponseInterface $response, string $name): ?int
    {
        $value = $response->getHeaderLine($name);

        return ctype_digit(trim($value)) ? (int) trim($value) : null;
    }
}
