<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Resources;

use Daika7ana\DragonStar\Client;
use Daika7ana\SimpleCollections\Collection;
use Daika7ana\DragonStar\DTOs\Directory\BranchList;
use Daika7ana\DragonStar\DTOs\Directory\LocalityDistance;
use Daika7ana\DragonStar\Exceptions\ApiException;
use Daika7ana\DragonStar\Support\ApiResponseMapper;

/**
 * Counties, localities and branches.
 */
class DirectoryResource
{
    public function __construct(
        private Client $client,
    ) {}

    /**
     * Counties keyed by code, e.g. "CJ" => "Cluj".
     *
     * Everywhere else in the API the value (the name) is used.
     *
     * @return Collection<string, string>
     *
     * @throws ApiException
     */
    public function counties(): Collection
    {
        $data = $this->getJson('/v1/counties');
        $counties = [];

        foreach (ApiResponseMapper::arrayOrEmpty($data['data'] ?? null) as $code => $name) {
            if (is_string($code) && is_scalar($name)) {
                $counties[$code] = (string) $name;
            }
        }

        return new Collection($counties);
    }

    /**
     * Localities of a county. The county is the name from counties(), not the code.
     *
     * @return Collection<int, string>
     *
     * @throws ApiException
     */
    public function localities(string $county): Collection
    {
        $data = $this->getJson(sprintf('/v1/counties/%s/localities', rawurlencode($county)));

        return new Collection(ApiResponseMapper::listOfStrings($data['data'] ?? null));
    }

    /**
     * Localities of a county with their distance in km.
     *
     * @return Collection<int, LocalityDistance>
     *
     * @throws ApiException
     */
    public function localitiesWithDistances(string $county): Collection
    {
        $data = $this->getJson(sprintf('/v1/counties/%s/localities-km', rawurlencode($county)));

        $localities = array_map(
            static fn(string $value): LocalityDistance => LocalityDistance::fromString($value),
            ApiResponseMapper::listOfStrings($data['data'] ?? null),
        );

        return new Collection($localities);
    }

    /**
     * Branches the master account may ship for.
     *
     * @throws ApiException
     */
    public function branches(): BranchList
    {
        return BranchList::fromArray($this->getJson('/v1/branches'));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    private function getJson(string $path): array
    {
        $response = $this->client->send($this->client->createRequest('GET', $path));

        return ApiResponseMapper::decodeJson($response);
    }
}
