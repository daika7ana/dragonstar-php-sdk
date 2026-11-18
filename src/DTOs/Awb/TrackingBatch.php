<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\DTOs\Awb;

use Daika7ana\DragonStar\DTOs\Common\Collection;
use Daika7ana\DragonStar\Support\ApiResponseMapper;

final readonly class TrackingBatch
{
    /**
     * @param Collection<int, Tracking> $results
     * @param list<int> $notFound
     */
    public function __construct(
        public Collection $results,
        public array $notFound = [],
        public int $count = 0,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $results = array_map(
            static fn(array $item): Tracking => Tracking::fromArray($item),
            ApiResponseMapper::listOfArrays($data['data'] ?? null),
        );

        return new self(
            results: new Collection($results),
            notFound: ApiResponseMapper::listOfInts($data['notFound'] ?? null),
            count: (int) ($data['count'] ?? count($results)),
        );
    }
}
