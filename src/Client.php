<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar;

use Daika7ana\DragonStar\Config\ClientConfig;
use Daika7ana\DragonStar\Http\GuzzleHttpClient;
use Daika7ana\DragonStar\Http\HttpClientInterface;
use Daika7ana\DragonStar\Http\Psr18HttpClientAdapter;
use Daika7ana\DragonStar\Resources\AwbResource;
use Daika7ana\DragonStar\Resources\DirectoryResource;
use Daika7ana\DragonStar\Resources\ManifestResource;
use Daika7ana\DragonStar\Resources\PickupResource;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Client
{
    private HttpClientInterface $httpClient;

    private ClientConfig $config;

    private ?AwbResource $awbResource = null;

    private ?PickupResource $pickupResource = null;

    private ?ManifestResource $manifestResource = null;

    private ?DirectoryResource $directoryResource = null;

    public function __construct(
        ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        ?ClientConfig $config = null,
    ) {
        if ($config?->timeout !== null && !$httpClient instanceof HttpClientInterface) {
            throw new \InvalidArgumentException('ClientConfig::timeout cannot be applied to a foreign PSR-18 client; configure the timeout on that client or leave it null.');
        }

        $this->httpClient = $httpClient instanceof HttpClientInterface
            ? $httpClient
            : new Psr18HttpClientAdapter($httpClient);
        $this->config = $config ?? new ClientConfig();
    }

    public function getConfig(): ClientConfig
    {
        return $this->config;
    }

    /**
     * A client sharing this client's transport, PSR-17 factories and API key,
     * but with a different request timeout budget.
     */
    public function withTimeout(?float $timeout): self
    {
        if ($timeout !== null && $this->httpClient instanceof Psr18HttpClientAdapter) {
            throw new \InvalidArgumentException('ClientConfig::timeout cannot be applied to a foreign PSR-18 client; configure the timeout on that client or leave it null.');
        }

        return new self(
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
            $this->config->withTimeout($timeout),
        );
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
        ?ClientInterface $httpClient = null,
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
