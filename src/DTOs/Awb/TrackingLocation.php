<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\DTOs\Awb;

final readonly class TrackingLocation
{
    public function __construct(
        public string $name,
        public ?string $code = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: is_scalar($data['name'] ?? null) ? (string) $data['name'] : '',
            code: is_scalar($data['code'] ?? null) ? (string) $data['code'] : null,
        );
    }
}
