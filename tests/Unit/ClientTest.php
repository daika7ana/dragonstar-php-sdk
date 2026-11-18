<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Unit;

use Daika7ana\DragonStar\Client;
use Daika7ana\DragonStar\Config\ClientConfig;
use Daika7ana\DragonStar\Exceptions\ApiException;
use Daika7ana\DragonStar\Tests\Support\FakeHttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testCreateUsesProductionBaseUrlAndApiKeyShortcut(): void
    {
        $client = Client::create(apiKey: 'secret-key');

        $this->assertSame(ClientConfig::BASE_URL_PRODUCTION, $client->getConfig()->baseUrl);
        $this->assertSame('https://api.curierdragonstar.ro', $client->getConfig()->baseUrl);
        $this->assertSame('secret-key', $client->getConfig()->apiKey);
    }

    public function testSetApiKeyUpdatesTheConfig(): void
    {
        $client = Client::create();
        $client->setApiKey('new-key');

        $this->assertSame('new-key', $client->getConfig()->apiKey);
    }

    public function testRequestCarriesApiKeyAndAcceptHeaders(): void
    {
        $httpClient = new FakeHttpClient(static fn() => new Response(200, [], '{}'));
        $factory = new HttpFactory();

        $client = Client::create(
            httpClient: $httpClient,
            requestFactory: $factory,
            streamFactory: $factory,
            config: new ClientConfig(apiKey: 'key-abc'),
        );

        $client->send($client->createRequest('GET', '/v1/counties'));

        $request = $httpClient->lastRequest;
        $this->assertNotNull($request);
        $this->assertSame('key-abc', $request->getHeaderLine('X-Api-Key'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertSame('/v1/counties', $request->getUri()->getPath());
    }

    public function testNoApiKeyHeaderIsSentWhenTheKeyIsEmpty(): void
    {
        $httpClient = new FakeHttpClient(static fn() => new Response(200, [], '{}'));
        $factory = new HttpFactory();

        $client = Client::create(
            httpClient: $httpClient,
            requestFactory: $factory,
            streamFactory: $factory,
            config: new ClientConfig(),
        );

        $client->send($client->createRequest('GET', '/v1/counties'));

        $this->assertFalse($httpClient->lastRequest?->hasHeader('X-Api-Key'));
    }

    public function testDefaultTimeoutIsThirtySeconds(): void
    {
        $httpClient = new FakeHttpClient(static fn() => new Response(200, [], '{}'));

        $client = Client::create(httpClient: $httpClient);
        $client->send($client->createRequest('GET', '/v1/counties'));

        $this->assertSame(30.0, $httpClient->lastTimeout);
    }

    public function testGlobalTimeoutIsForwardedToTheHttpClient(): void
    {
        $httpClient = new FakeHttpClient(static fn() => new Response(200, [], '{}'));
        $factory = new HttpFactory();

        $client = Client::create(
            httpClient: $httpClient,
            requestFactory: $factory,
            streamFactory: $factory,
            config: new ClientConfig(timeout: 15.0),
        );

        $client->send($client->createRequest('GET', '/v1/counties'));

        $this->assertSame(15.0, $httpClient->lastTimeout);
    }

    public function testPerRequestTimeoutOverridesTheGlobalTimeout(): void
    {
        $httpClient = new FakeHttpClient(static fn() => new Response(200, [], '{}'));
        $factory = new HttpFactory();

        $client = Client::create(
            httpClient: $httpClient,
            requestFactory: $factory,
            streamFactory: $factory,
            config: new ClientConfig(timeout: 15.0),
        );

        $client->send($client->createRequest('GET', '/v1/counties'), 5.0);

        $this->assertSame(5.0, $httpClient->lastTimeout);
    }

    public function testResourcesAreLazySingletons(): void
    {
        $client = Client::create();

        $this->assertSame($client->awb(), $client->awb());
        $this->assertSame($client->pickups(), $client->pickups());
        $this->assertSame($client->manifests(), $client->manifests());
        $this->assertSame($client->directory(), $client->directory());
    }

    public function testInvalidJsonSuccessBodyThrowsApiExceptionWithResponse(): void
    {
        [$client, ] = \Daika7ana\DragonStar\Tests\Support\TestClientFactory::create(
            static fn() => new Response(200, [], '<html>not json</html>'),
        );

        try {
            $client->awb()->track(94348148);
            $this->fail('Expected ApiException to be thrown.');
        } catch (ApiException $exception) {
            $this->assertSame(200, $exception->status);
            $this->assertSame('invalid_response', $exception->errorCode);
            $this->assertNotNull($exception->response);
        }
    }

    public function testNonJsonErrorBodyFallsBackToHttpStatus(): void
    {
        [$client, ] = \Daika7ana\DragonStar\Tests\Support\TestClientFactory::create(
            static fn() => new Response(503, ['Retry-After' => '60'], 'Service Unavailable'),
        );

        try {
            $client->directory()->counties();
            $this->fail('Expected ApiException to be thrown.');
        } catch (ApiException $exception) {
            $this->assertSame(503, $exception->status);
            $this->assertSame('unknown_error', $exception->errorCode);
            $this->assertSame(60, $exception->retryAfter);
            $this->assertStringContainsString('HTTP 503', $exception->getMessage());
        }
    }
}
