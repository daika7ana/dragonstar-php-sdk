<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\DTOs\Directory;

use Daika7ana\SimpleCollections\Collection;
use Daika7ana\DragonStar\Support\ApiResponseMapper;

final readonly class BranchList
{
    /**
     * @param Collection<int, Branch> $branches
     */
    public function __construct(
        public Collection $branches,
        public ?int $masterId = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $branches = array_map(
            static fn(array $item): Branch => Branch::fromArray($item),
            ApiResponseMapper::listOfArrays($data['data'] ?? null),
        );

        $masterId = $data['masterId'] ?? null;

        return new self(
            branches: new Collection($branches),
            masterId: is_int($masterId) || (is_string($masterId) && ctype_digit($masterId)) ? (int) $masterId : null,
        );
    }
}
