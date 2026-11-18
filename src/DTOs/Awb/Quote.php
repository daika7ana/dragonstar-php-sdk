<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\DTOs\Awb;

final readonly class Quote
{
    public function __construct(
        public float $baseRate,
        public float $distanceCost,
        public float $weightCost,
        public float $insuranceCost,
        public bool $isListRate,
        public float $netAmount,
        public float $vatAmount,
        public float $totalAmount,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            baseRate: (float) ($data['baseRate'] ?? 0),
            distanceCost: (float) ($data['distanceCost'] ?? 0),
            weightCost: (float) ($data['weightCost'] ?? 0),
            insuranceCost: (float) ($data['insuranceCost'] ?? 0),
            isListRate: (bool) ($data['isListRate'] ?? false),
            netAmount: (float) ($data['netAmount'] ?? 0),
            vatAmount: (float) ($data['vatAmount'] ?? 0),
            totalAmount: (float) ($data['totalAmount'] ?? 0),
        );
    }
}
