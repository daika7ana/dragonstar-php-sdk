<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\DTOs\Pickup;

/**
 * Payload for POST /v1/pickups.
 *
 * `parcels` or `pallets`, and `weight`, are validated by the API.
 */
final readonly class PickupCreateRequest
{
    public function __construct(
        public string $pickupDate,
        public int $pickupWindowStart,
        public int $pickupWindowEnd,
        public float $weight,
        public ?string $pickupLocation = null,
        public ?string $pickupAddress = null,
        public ?string $pickupCounty = null,
        public ?string $pickupCity = null,
        public ?string $pickupContact = null,
        public ?string $pickupPhone = null,
        public ?string $pickupEmail = null,
        public ?int $parcels = null,
        public ?int $pallets = null,
        public ?float $volume = null,
        public ?string $notes = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'pickupDate' => $this->pickupDate,
            'pickupWindowStart' => $this->pickupWindowStart,
            'pickupWindowEnd' => $this->pickupWindowEnd,
            'weight' => $this->weight,
            'pickupLocation' => $this->pickupLocation,
            'pickupAddress' => $this->pickupAddress,
            'pickupCounty' => $this->pickupCounty,
            'pickupCity' => $this->pickupCity,
            'pickupContact' => $this->pickupContact,
            'pickupPhone' => $this->pickupPhone,
            'pickupEmail' => $this->pickupEmail,
            'parcels' => $this->parcels,
            'pallets' => $this->pallets,
            'volume' => $this->volume,
            'notes' => $this->notes,
        ];

        return array_filter($data, static fn(mixed $value): bool => $value !== null);
    }
}
