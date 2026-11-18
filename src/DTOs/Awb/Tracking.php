<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\DTOs\Awb;

/**
 * A single tracking record. `awb` is set on batch responses.
 */
final readonly class Tracking
{
    /**
     * @param list<TrackingEvent> $events
     */
    public function __construct(
        public ?TrackingStatus $status = null,
        public array $events = [],
        public ?int $awb = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $status = $data['status'] ?? null;
        $awb = $data['awb'] ?? null;

        return new self(
            status: is_array($status) ? TrackingStatus::fromArray($status) : null,
            events: TrackingEvent::listFromArray($data['events'] ?? null),
            awb: is_int($awb) || (is_string($awb) && ctype_digit($awb)) ? (int) $awb : null,
        );
    }
}
