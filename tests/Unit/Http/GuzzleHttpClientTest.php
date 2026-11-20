<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Unit\Http;

use Daika7ana\DragonStar\Config\ClientConfig;
use Daika7ana\DragonStar\Exceptions\TransportException;
use Daika7ana\DragonStar\Http\GuzzleHttpClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class GuzzleHttpClientTest extends TestCase
{
    public function testDefaultConstructionConfiguresTransportTimeouts(): void
    {
        $client = new GuzzleHttpClient();

        $property = new \ReflectionProperty(GuzzleHttpClient::class, 'guzzleClient');
        $guzzle = $property->getValue($client);

        $this->assertSame(30.0, $guzzle->getConfig('timeout'));
        $this->assertSame(10.0, $guzzle->getConfig('connect_timeout'));
    }

    public function testConstructorTimeoutsOverrideTheTransportDefaults(): void
    {
        $client = new GuzzleHttpClient(null, 5.0, 2.0);

        $property = new \ReflectionProperty(GuzzleHttpClient::class, 'guzzleClient');
        $guzzle = $property->getValue($client);

        $this->assertSame(5.0, $guzzle->getConfig('timeout'));
        $this->assertSame(2.0, $guzzle->getConfig('connect_timeout'));
    }

    public function testTransportErrorsAreMappedToTransportException(): void
    {
        $request = new Request('GET', ClientConfig::BASE_URL_PRODUCTION . '/v1/counties');

        $mock = new MockHandler([
            new ConnectException('Network down', $request),
        ]);

        $guzzle = new GuzzleClient([
            'handler' => HandlerStack::create($mock),
        ]);

        $client = new GuzzleHttpClient($guzzle);

        $this->expectException(TransportException::class);
        $client->sendRequest($request);
    }

    public function testTimeoutIsPassedToGuzzleWhenProvided(): void
    {
        $request = new Request('GET', ClientConfig::BASE_URL_PRODUCTION . '/v1/counties');

        $guzzle = $this->createMock(GuzzleClient::class);
        $guzzle->expects($this->once())
            ->method('send')
            ->with($request, [
                'timeout' => 30.0,
                'http_errors' => false,
                'allow_redirects' => false,
            ])
            ->willReturn(new Response(200));

        $client = new GuzzleHttpClient($guzzle);
        $response = $client->sendRequest($request, 30.0);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testTimeoutPathReturnsErrorResponsesInsteadOfThrowing(): void
    {
        $request = new Request('POST', ClientConfig::BASE_URL_PRODUCTION . '/v1/awb');

        $mock = new MockHandler([
            new Response(422, [], '{"message":"Invalid input"}'),
        ]);

        $guzzle = new GuzzleClient([
            'handler' => HandlerStack::create($mock),
        ]);

        $client = new GuzzleHttpClient($guzzle);
        $response = $client->sendRequest($request, 30.0);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testOptionsArePassedWithoutTimeout(): void
    {
        $request = new Request('POST', ClientConfig::BASE_URL_PRODUCTION . '/v1/awb');

        $guzzle = $this->createMock(GuzzleClient::class);
        $guzzle->expects($this->once())
            ->method('send')
            ->with($request, [
                'http_errors' => false,
                'allow_redirects' => false,
            ])
            ->willReturn(new Response(422));

        $client = new GuzzleHttpClient($guzzle);
        $response = $client->sendRequest($request);

        $this->assertSame(422, $response->getStatusCode());
    }
}
