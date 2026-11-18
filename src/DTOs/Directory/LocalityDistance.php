<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\DTOs\Directory;

/**
 * A locality returned by /v1/counties/{county}/localities-km, in the form
 * "Cluj-Napoca:0".
 */
final readonly class LocalityDistance
{
    public function __construct(
        public string $name,
        public ?int $km = null,
    ) {}

    public static function fromString(string $value): self
    {
        $separator = strrpos($value, ':');

        if ($separator === false) {
            return new self(name: $value);
        }

        $km = substr($value, $separator + 1);

        return new self(
            name: substr($value, 0, $separator),
            km: ctype_digit($km) ? (int) $km : null,
        );
    }
}
