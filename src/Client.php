<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar;

use Daika7ana\DragonStar\Config\ClientConfig;
use Daika7ana\DragonStar\Http\GuzzleHttpClient;
use Daika7ana\DragonStar\Http\HttpClientInterface;
use Daika7ana\DragonStar\Resources\AwbResource;
use Daika7ana\DragonStar\Resources\DirectoryResource;
use Daika7ana\DragonStar\Resources\ManifestResource;
use Daika7ana\DragonStar\Resources\PickupResource;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Client
{
    private ClientConfig $config;

    private ?AwbResource $awbResource = null;

    private ?PickupResource $pickupResource = null;

    private ?ManifestResource $manifestResource = null;

    private ?DirectoryResource $directoryResource = null;

    public function __construct(
        private HttpClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        ?ClientConfig $config = null,
    ) {
        $this->config = $config ?? new ClientConfig();
    }

    public function getConfig(): ClientConfig
    {
        return $this->config;
    }

    public function setApiKey(string $apiKey): self
    {
        $this->config = $this->config->withApiKey($apiKey);

        return $this;
    }

    public function createRequest(string $method, string $path): RequestInterface
    {
        $uri = rtrim($this->config->baseUrl, '/') . $path;
        $request = $this->requestFactory
            ->createRequest($method, $uri)
            ->withHeader('Accept', 'application/json');

        if ($this->config->apiKey !== '') {
            $request = $request->withHeader('X-Api-Key', $this->config->apiKey);
        }

        return $request;
    }

    public function send(RequestInterface $request, ?float $timeout = null): ResponseInterface
    {
        return $this->httpClient->sendRequest($request, $timeout ?? $this->config->timeout);
    }

    public function streamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    public function awb(): AwbResource
    {
        $this->awbResource ??= new AwbResource($this);

        return $this->awbResource;
    }

    public function pickups(): PickupResource
    {
        $this->pickupResource ??= new PickupResource($this);

        return $this->pickupResource;
    }

    public function manifests(): ManifestResource
    {
        $this->manifestResource ??= new ManifestResource($this);

        return $this->manifestResource;
    }

    public function directory(): DirectoryResource
    {
        $this->directoryResource ??= new DirectoryResource($this);

        return $this->directoryResource;
    }

    public static function create(
        ?HttpClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?ClientConfig $config = null,
        ?string $apiKey = null,
    ): self {
        $httpClient ??= new GuzzleHttpClient();
        $config ??= new ClientConfig();

        if ($apiKey !== null) {
            $config = $config->withApiKey($apiKey);
        }

        if ($requestFactory === null || $streamFactory === null) {
            $psr17Factory = new \GuzzleHttp\Psr7\HttpFactory();
            $requestFactory ??= $psr17Factory;
            $streamFactory ??= $psr17Factory;
        }

        return new self($httpClient, $requestFactory, $streamFactory, $config);
    }
}
