<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\DTOs\Pickup;

final readonly class PickupCreated
{
    public function __construct(
        public ?int $pickupId,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $pickupId = $data['pickupId'] ?? null;

        return new self(
            pickupId: is_int($pickupId) || (is_string($pickupId) && ctype_digit($pickupId)) ? (int) $pickupId : null,
        );
    }
}
