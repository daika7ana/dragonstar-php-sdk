<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Resources;

use Daika7ana\DragonStar\Client;
use Daika7ana\DragonStar\DTOs\Common\Document;
use Daika7ana\DragonStar\DTOs\Manifest\ManifestCreated;
use Daika7ana\DragonStar\Exceptions\ApiException;
use Daika7ana\DragonStar\Support\ApiResponseMapper;

/**
 * Manifests (borderouri): closing shipments and downloading the PDF.
 */
class ManifestResource
{
    public function __construct(
        private Client $client,
    ) {}

    /**
     * Close all shipments that are not in a manifest yet.
     *
     * `manifestId` is null when there was nothing to close.
     *
     * @throws ApiException
     */
    public function create(): ManifestCreated
    {
        $request = $this->client->createRequest('POST', '/v1/manifests');
        $response = $this->client->send($request);

        return ManifestCreated::fromArray(
            ApiResponseMapper::decodeJsonOrNull($response, 200, 201) ?? [],
        );
    }

    /**
     * Download the manifest PDF.
     *
     * @throws ApiException
     */
    public function pdf(int $manifestId): Document
    {
        $request = $this->client
            ->createRequest('GET', sprintf('/v1/manifests/%d/pdf', $manifestId))
            ->withHeader('Accept', 'application/pdf');
        $response = $this->client->send($request);

        ApiResponseMapper::assertStatus($response, 200);

        return Document::fromResponse($response);
    }
}
