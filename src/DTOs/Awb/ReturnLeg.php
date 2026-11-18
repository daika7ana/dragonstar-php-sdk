<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\DTOs\Awb;

final readonly class ReturnLeg
{
    public function __construct(
        public int $awb,
        public string $type,
        public ?int $typeCode = null,
        public ?TrackingStatus $status = null,
        public ?TrackingEvent $lastEvent = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $typeCode = $data['typeCode'] ?? null;
        $status = $data['status'] ?? null;
        $lastEvent = $data['lastEvent'] ?? null;

        return new self(
            awb: (int) ($data['awb'] ?? 0),
            type: (string) ($data['type'] ?? ''),
            typeCode: is_int($typeCode) || (is_string($typeCode) && ctype_digit($typeCode)) ? (int) $typeCode : null,
            status: is_array($status) ? TrackingStatus::fromArray($status) : null,
            lastEvent: is_array($lastEvent) ? TrackingEvent::fromArray($lastEvent) : null,
        );
    }
}
