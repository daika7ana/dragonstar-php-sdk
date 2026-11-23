<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Resources;

use Daika7ana\DragonStar\Client;
use Daika7ana\DragonStar\DTOs\Pickup\PickupCreated;
use Daika7ana\DragonStar\DTOs\Pickup\PickupCreateRequest;
use Daika7ana\DragonStar\DTOs\Common\Errors\DragonStarError;
use Daika7ana\DragonStar\Exceptions\AuthenticationException;
use Daika7ana\DragonStar\Exceptions\UnexpectedStatusException;
use Daika7ana\DragonStar\Exceptions\ValidationException;
use Daika7ana\DragonStar\Support\ApiResponseMapper;
use Daika7ana\DragonStar\Support\JsonHelper;
use JsonException;

/**
 * Pickup requests: creation and cancellation.
 */
class PickupResource
{
    public function __construct(
        private Client $client,
    ) {}

    /**
     * Order a pickup.
     *
     * @param PickupCreateRequest|array<string, mixed> $request
     *
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws UnexpectedStatusException
     */
    public function create(PickupCreateRequest|array $request): PickupCreated
    {
        $data = $this->sendJson('/v1/pickups', $request, 201);

        return PickupCreated::fromArray($data);
    }
    /**
     * Order a pickup on behalf of a branch (punct de lucru).
     *
     * @param PickupCreateRequest|array<string, mixed> $request
     *
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws UnexpectedStatusException
     */
    public function createForBranch(int $branchId, PickupCreateRequest|array $request): PickupCreated
    {
        $data = $request instanceof PickupCreateRequest ? $request->toArray() : $request;

        return PickupCreated::fromArray(
            $this->sendJson('/v1/pickups/branch', ['pcId' => $branchId] + $data, 201, allowEmpty: true),
        );
    }

    /**
     * Cancel a pickup, including one already sent to a courier.
     *
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws UnexpectedStatusException
     */
    public function cancel(int $pickupId): void
    {
        $request = $this->client->createRequest('DELETE', sprintf('/v1/pickups/%d', $pickupId));
        $response = $this->client->send($request);

        ApiResponseMapper::assertStatus($response, 204);
    }

    /**
     * @param PickupCreateRequest|array<string, mixed> $payload
     *
     * @return array<string, mixed>
     *
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws UnexpectedStatusException
     */
    private function sendJson(
        string $path,
        PickupCreateRequest|array $payload,
        int $expectedStatus,
        bool $allowEmpty = false,
    ): array {
        $data = $payload instanceof PickupCreateRequest ? $payload->toArray() : $payload;

        try {
            $body = JsonHelper::encode($data);
        } catch (JsonException $e) {
            throw new UnexpectedStatusException(
                null,
                'Request body is not encodable: ' . $e->getMessage(),
                error: DragonStarError::fromArray([
                    'status' => 0,
                    'code' => 'invalid_request',
                    'title' => 'Request body is not encodable',
                    'detail' => $e->getMessage(),
                ]),
            );
        }

        $request = $this->client->createRequest('POST', $path)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->client->streamFactory()->createStream($body));

        $response = $this->client->send($request);

        if ($allowEmpty) {
            return ApiResponseMapper::decodeJsonOrNull($response, $expectedStatus) ?? [];
        }

        $decoded = ApiResponseMapper::decodeJson($response, $expectedStatus);

        if (!array_key_exists('pickupId', $decoded)) {
            throw ApiResponseMapper::invalidBody(
                $response,
                'Response is missing the required field "pickupId".',
            );
        }

        return $decoded;
    }
}
