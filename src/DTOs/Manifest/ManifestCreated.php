<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\DTOs\Manifest;

/**
 * Result of closing shipments into a new manifest. `manifestId` is null when
 * there was nothing to close (HTTP 200).
 */
final readonly class ManifestCreated
{
    public function __construct(
        public ?int $manifestId,
        public ?int $shipmentCount = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $manifestId = $data['manifestId'] ?? null;
        $shipmentCount = $data['shipmentCount'] ?? null;

        return new self(
            manifestId: is_int($manifestId) || (is_string($manifestId) && ctype_digit($manifestId)) ? (int) $manifestId : null,
            shipmentCount: is_int($shipmentCount) || (is_string($shipmentCount) && ctype_digit($shipmentCount)) ? (int) $shipmentCount : null,
        );
    }
}
