<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\DTOs\Directory;

final readonly class Branch
{
    public function __construct(
        public int $branchId,
        public string $name,
        public ?string $county = null,
        public ?string $city = null,
        public ?string $address = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            branchId: (int) ($data['branchId'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            county: isset($data['county']) ? (string) $data['county'] : null,
            city: isset($data['city']) ? (string) $data['city'] : null,
            address: isset($data['address']) ? (string) $data['address'] : null,
        );
    }
}
