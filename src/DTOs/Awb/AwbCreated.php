<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\DTOs\Awb;

use Daika7ana\DragonStar\Enums\CodPaymentType;

final readonly class AwbCreated
{
    public function __construct(
        public int $awb,
        public float $totalAmount,
        public ?float $weight = null,
        public ?int $pieces = null,
        public ?float $codAmount = null,
        public ?CodPaymentType $codPaymentType = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $codPaymentType = $data['codPaymentType'] ?? null;

        return new self(
            awb: (int) ($data['awb'] ?? 0),
            totalAmount: (float) ($data['totalAmount'] ?? 0),
            weight: self::floatOrNull($data['weight'] ?? null),
            pieces: self::intOrNull($data['pieces'] ?? null),
            codAmount: self::floatOrNull($data['codAmount'] ?? null),
            codPaymentType: is_string($codPaymentType) ? CodPaymentType::tryFrom($codPaymentType) : null,
        );
    }

    private static function floatOrNull(mixed $value): ?float
    {
        return is_int($value) || is_float($value) || (is_string($value) && is_numeric($value)) ? (float) $value : null;
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_int($value) || (is_string($value) && ctype_digit($value)) ? (int) $value : null;
    }
}
