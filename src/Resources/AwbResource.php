<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Resources;

use Daika7ana\DragonStar\Client;
use Daika7ana\DragonStar\DTOs\Awb\AwbCreated;
use Daika7ana\DragonStar\DTOs\Awb\AwbCreateRequest;
use Daika7ana\DragonStar\DTOs\Awb\AwbQuoteRequest;
use Daika7ana\DragonStar\DTOs\Awb\Quote;
use Daika7ana\DragonStar\DTOs\Awb\ReturnLeg;
use Daika7ana\DragonStar\DTOs\Awb\Tracking;
use Daika7ana\DragonStar\DTOs\Awb\TrackingBatch;
use Daika7ana\SimpleCollections\Collection;
use Daika7ana\DragonStar\DTOs\Common\Document;
use Daika7ana\DragonStar\Enums\LabelFormat;
use Daika7ana\DragonStar\Exceptions\ApiException;
use Daika7ana\DragonStar\Support\ApiResponseMapper;
use Daika7ana\DragonStar\Support\JsonHelper;
use JsonException;

/**
 * Shipments: creation, quoting, tracking, labels and deletion.
 */
class AwbResource
{
    public function __construct(
        private Client $client,
    ) {}

    /**
     * Create an AWB.
     *
     * @param AwbCreateRequest|array<string, mixed> $request
     *
     * @throws ApiException
     */
    public function create(AwbCreateRequest|array $request): AwbCreated
    {
        $data = $this->sendJson('/v1/awb', $request, 201, ['awb', 'totalAmount']);

        return AwbCreated::fromArray($data);
    }

    /**
     * Create an AWB on behalf of a branch (punct de lucru).
     *
     * @param AwbCreateRequest|array<string, mixed> $request
     *
     * @throws ApiException
     */
    public function createForBranch(int $branchId, AwbCreateRequest|array $request): AwbCreated
    {
        $data = $this->sendJson('/v1/awb/branch', $this->withBranchId($branchId, $request), 201, ['awb', 'totalAmount']);

        return AwbCreated::fromArray($data);
    }

    /**
     * Estimate the cost of a shipment without creating anything.
     *
     * @param AwbQuoteRequest|array<string, mixed> $request
     *
     * @throws ApiException
     */
    public function quote(AwbQuoteRequest|array $request): Quote
    {
        return Quote::fromArray($this->sendJson('/v1/awb/quote', $request, 200, ['totalAmount']));
    }

    /**
     * Estimate the cost of a shipment for a branch.
     *
     * @param AwbQuoteRequest|array<string, mixed> $request
     *
     * @throws ApiException
     */
    public function quoteForBranch(int $branchId, AwbQuoteRequest|array $request): Quote
    {
        return Quote::fromArray($this->sendJson('/v1/awb/quote/branch', $this->withBranchId($branchId, $request), 200, ['totalAmount']));
    }

    /**
     * Track one shipment.
     *
     * @throws ApiException
     */
    public function track(int $awb, bool $includeEvents = true): Tracking
    {
        $path = sprintf('/v1/awb/%d', $awb) . ($includeEvents ? '' : '?events=false');
        $response = $this->client->send($this->client->createRequest('GET', $path));

        return Tracking::fromArray(ApiResponseMapper::decodeJson($response));
    }

    /**
     * Track up to 100 AWBs in one request.
     *
     * @param list<int> $awbs
     *
     * @throws ApiException
     */
    public function trackMany(array $awbs, bool $includeEvents = false): TrackingBatch
    {
        $path = '/v1/awb/tracking' . ($includeEvents ? '?events=true' : '');
        $data = $this->sendJson($path, ['awbs' => array_values(array_map('intval', $awbs))], 200);

        return TrackingBatch::fromArray($data);
    }

    /**
     * Delete a shipment. Only possible before pickup, manifest or invoice.
     *
     * @throws ApiException
     */
    public function delete(int $awb): void
    {
        $request = $this->client->createRequest('DELETE', sprintf('/v1/awb/%d', $awb));
        $response = $this->client->send($request);

        ApiResponseMapper::assertStatus($response, 204);
    }

    /**
     * Return legs linked to an AWB.
     *
     * @return Collection<int, ReturnLeg>
     *
     * @throws ApiException
     */
    public function returnLegs(int $awb): Collection
    {
        $request = $this->client->createRequest('GET', sprintf('/v1/awb/%d/return', $awb));
        $response = $this->client->send($request);

        $data = ApiResponseMapper::decodeJson($response);

        $legs = array_map(
            static fn(array $item): ReturnLeg => ReturnLeg::fromArray($item),
            ApiResponseMapper::listOfArrays($data['data'] ?? null),
        );

        return new Collection($legs);
    }

    /**
     * Download the label in the account's configured format.
     *
     * @throws ApiException
     */
    public function label(int $awb): Document
    {
        $request = $this->client
            ->createRequest('GET', sprintf('/v1/awb/%d/label', $awb))
            ->withHeader('Accept', 'application/pdf');
        $response = $this->client->send($request);

        ApiResponseMapper::assertStatus($response, 200);

        return Document::fromResponse($response);
    }

    /**
     * Download the PDF label as A4 or A6.
     *
     * @throws ApiException
     */
    public function labelAs(int $awb, LabelFormat $format): Document
    {
        $request = $this->client
            ->createRequest('GET', sprintf('/v1/awb/%d/label/%s', $awb, $format->value))
            ->withHeader('Accept', 'application/pdf');
        $response = $this->client->send($request);

        ApiResponseMapper::assertStatus($response, 200);

        return Document::fromResponse($response);
    }

    /**
     * Download the ZPL label of one piece (1 = the piece with the AWB barcode).
     *
     * @throws ApiException
     */
    public function zplLabel(int $awb, int $piece = 1): Document
    {
        $request = $this->client
            ->createRequest('GET', sprintf('/v1/awb/%d/label/zpl/%d', $awb, $piece))
            ->withHeader('Accept', 'application/octet-stream');
        $response = $this->client->send($request);

        ApiResponseMapper::assertStatus($response, 200);

        return Document::fromResponse($response);
    }

    /**
     * @param AwbCreateRequest|AwbQuoteRequest|array<string, mixed> $payload
     * @param list<string> $requiredKeys
     *
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    private function sendJson(
        string $path,
        AwbCreateRequest|AwbQuoteRequest|array $payload,
        int $expectedStatus,
        array $requiredKeys = [],
    ): array {
        $data = $payload instanceof AwbCreateRequest || $payload instanceof AwbQuoteRequest
            ? $payload->toArray()
            : $payload;

        try {
            $body = JsonHelper::encode($data);
        } catch (JsonException $e) {
            throw new ApiException(
                status: 0,
                errorCode: 'invalid_request',
                title: 'Request body is not encodable',
                detail: $e->getMessage(),
            );
        }

        $request = $this->client->createRequest('POST', $path)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->client->streamFactory()->createStream($body));

        $response = $this->client->send($request);

        $decoded = ApiResponseMapper::decodeJson($response, $expectedStatus);

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $decoded)) {
                throw ApiException::invalidBody(
                    $response,
                    sprintf('Response is missing the required field "%s".', $key),
                );
            }
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function withBranchId(int $branchId, AwbCreateRequest|AwbQuoteRequest|array $payload): array
    {
        $data = $payload instanceof AwbCreateRequest || $payload instanceof AwbQuoteRequest
            ? $payload->toArray()
            : $payload;

        return ['pcId' => $branchId] + $data;
    }
}
