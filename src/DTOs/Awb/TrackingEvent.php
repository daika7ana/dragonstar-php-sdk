<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\DTOs\Awb;

use Daika7ana\DragonStar\Enums\ShipmentStatus;
use Daika7ana\DragonStar\Support\ApiResponseMapper;

final readonly class TrackingEvent
{
    public function __construct(
        public ?ShipmentStatus $statusCode,
        public ?string $eventCode,
        public string $description,
        public bool $isException,
        public ?string $timestamp = null,
        public ?TrackingLocation $location = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $statusCode = $data['statusCode'] ?? null;
        $location = $data['location'] ?? null;

        return new self(
            statusCode: is_int($statusCode) || (is_string($statusCode) && ctype_digit($statusCode))
                ? ShipmentStatus::tryFrom((int) $statusCode)
                : null,
            eventCode: is_scalar($data['eventCode'] ?? null) ? (string) $data['eventCode'] : null,
            description: is_scalar($data['description'] ?? null) ? (string) $data['description'] : '',
            isException: (bool) ($data['isException'] ?? false),
            timestamp: is_scalar($data['timestamp'] ?? null) ? (string) $data['timestamp'] : null,
            location: is_array($location) ? TrackingLocation::fromArray($location) : null,
        );
    }

    /**
     * @return list<TrackingEvent>
     */
    public static function listFromArray(mixed $events): array
    {
        return array_map(
            static fn(array $event): TrackingEvent => self::fromArray($event),
            ApiResponseMapper::listOfArrays($events),
        );
    }
}
