<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\DTOs\Awb;

use Daika7ana\DragonStar\Enums\ShipmentStatus;

final readonly class TrackingStatus
{
    public function __construct(
        public ?ShipmentStatus $statusCode,
        public string $description,
        public ?string $descriptionRo = null,
        public ?string $timestamp = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $statusCode = $data['statusCode'] ?? null;

        return new self(
            statusCode: is_int($statusCode) || (is_string($statusCode) && ctype_digit($statusCode))
                ? ShipmentStatus::tryFrom((int) $statusCode)
                : null,
            description: is_scalar($data['description'] ?? null) ? (string) $data['description'] : '',
            descriptionRo: is_scalar($data['descriptionRo'] ?? null) ? (string) $data['descriptionRo'] : null,
            timestamp: is_scalar($data['timestamp'] ?? null) ? (string) $data['timestamp'] : null,
        );
    }
}
