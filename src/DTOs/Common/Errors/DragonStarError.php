<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\DTOs\Common\Errors;

final readonly class DragonStarError
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $code,
        public string $message,
        public string $id,
        public array $raw,
        public ?string $context = null,
        public ?string $component = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, int $fallbackCode = 0): self
    {
        $message = $data['detail'] ?? $data['title'] ?? 'Dragon Star API error.';

        return new self(
            code: (int) ($data['status'] ?? $fallbackCode),
            message: is_string($message) ? $message : 'Dragon Star API error.',
            id: (string) ($data['id'] ?? ''),
            raw: $data,
            context: isset($data['context']) ? (string) $data['context'] : null,
            component: isset($data['component']) ? (string) $data['component'] : null,
        );
    }

    public function isValidationRelated(): bool
    {
        if ($this->code === 400 || $this->code === 422) {
            return true;
        }

        return isset($this->raw['errors']) && is_array($this->raw['errors']) && $this->raw['errors'] !== [];
    }

    public function isAuthenticationRelated(): bool
    {
        return $this->code === 401 || $this->code === 403;
    }
}
