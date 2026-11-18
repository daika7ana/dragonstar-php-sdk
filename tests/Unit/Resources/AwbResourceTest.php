<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Unit\Resources;

use Daika7ana\DragonStar\DTOs\Awb\AwbCreateRequest;
use Daika7ana\DragonStar\DTOs\Awb\AwbQuoteRequest;
use Daika7ana\DragonStar\Enums\CodPaymentType;
use Daika7ana\DragonStar\Enums\LabelFormat;
use Daika7ana\DragonStar\Enums\ShipmentStatus;
use Daika7ana\DragonStar\Enums\ShipmentType;
use Daika7ana\DragonStar\Exceptions\ApiException;
use Daika7ana\DragonStar\Support\JsonHelper;
use Daika7ana\DragonStar\Tests\Support\ResponseFixtureFactory;
use Daika7ana\DragonStar\Tests\Support\TestClientFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class AwbResourceTest extends TestCase
{
    public function testCreateReturnsAwbCreatedAndSendsPayload(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(201, [], json_encode(
                ResponseFixtureFactory::awbCreated(),
                JSON_THROW_ON_ERROR,
            )),
        );

        $created = $client->awb()->create(new AwbCreateRequest(
            shipmentType: ShipmentType::Parcel,
            recipientName: 'Ion Popescu',
            recipientAddress: 'Str. Memorandumului nr. 28',
            recipientCounty: 'Cluj',
            recipientCity: 'Cluj-Napoca',
            weight: 5,
            pieces: 1,
            codAmount: 150,
            codPaymentType: CodPaymentType::Cash,
            notes: 'Fragil',
        ));

        $this->assertSame(94348148, $created->awb);
        $this->assertSame(9.83, $created->totalAmount);
        $this->assertSame(1, $created->pieces);
        $this->assertNull($created->codPaymentType);

        $request = $httpClient->lastRequest;
        $this->assertNotNull($request);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/v1/awb', $request->getUri()->getPath());
        $this->assertSame('test-api-key', $request->getHeaderLine('X-Api-Key'));
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));

        $body = JsonHelper::decode((string) $request->getBody());
        $this->assertSame('parcel', $body['shipmentType']);
        $this->assertSame('Ion Popescu', $body['recipientName']);
        $this->assertSame('cash', $body['codPaymentType']);
        $this->assertSame(5, $body['weight']);
        $this->assertArrayNotHasKey('insuredValue', $body);
        $this->assertFalse($body['saturdayDelivery']);
    }

    public function testCreateForBranchAddsPcId(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(201, [], json_encode(
                ResponseFixtureFactory::awbCreated(94348149),
                JSON_THROW_ON_ERROR,
            )),
        );

        $created = $client->awb()->createForBranch(454, [
            'shipmentType' => 'envelope',
            'recipientName' => 'Ion Popescu',
            'recipientAddress' => 'Str. Memorandumului nr. 28',
            'recipientCounty' => 'Cluj',
            'recipientCity' => 'Cluj-Napoca',
        ]);

        $this->assertSame(94348149, $created->awb);

        $body = JsonHelper::decode((string) $httpClient->lastRequest?->getBody());
        $this->assertSame(454, $body['pcId']);
        $this->assertSame('/v1/awb/branch', $httpClient->lastRequest?->getUri()->getPath());
    }

    public function testQuoteReturnsTypedCosts(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(200, [], json_encode([
                'baseRate' => 15.23,
                'distanceCost' => 0,
                'weightCost' => 5.27,
                'insuranceCost' => 0.5,
                'isListRate' => true,
                'netAmount' => 21,
                'vatAmount' => 4.41,
                'totalAmount' => 25.41,
            ], JSON_THROW_ON_ERROR)),
        );

        $quote = $client->awb()->quote(new AwbQuoteRequest(
            shipmentType: ShipmentType::Parcel,
            recipientCounty: 'Cluj',
            recipientCity: 'Cluj-Napoca',
            weight: 5,
        ));

        $this->assertSame(15.23, $quote->baseRate);
        $this->assertSame(25.41, $quote->totalAmount);
        $this->assertTrue($quote->isListRate);
        $this->assertSame('/v1/awb/quote', $httpClient->lastRequest?->getUri()->getPath());

        $body = JsonHelper::decode((string) $httpClient->lastRequest?->getBody());
        $this->assertSame('parcel', $body['shipmentType']);
        $this->assertSame('Cluj', $body['recipientCounty']);
        $this->assertSame('Cluj-Napoca', $body['recipientCity']);
        $this->assertSame(5, $body['weight']);
        $this->assertArrayNotHasKey('pieces', $body);
        $this->assertArrayNotHasKey('codPaymentType', $body);
    }

    public function testQuoteForBranchAddsPcId(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(200, [], json_encode([
                'baseRate' => 15.23,
                'distanceCost' => 0,
                'weightCost' => 5.27,
                'insuranceCost' => 0,
                'isListRate' => false,
                'netAmount' => 21,
                'vatAmount' => 4,
                'totalAmount' => 25,
            ], JSON_THROW_ON_ERROR)),
        );

        $client->awb()->quoteForBranch(454, ['shipmentType' => 'parcel', 'recipientCounty' => 'Cluj', 'recipientCity' => 'Cluj-Napoca']);

        $body = JsonHelper::decode((string) $httpClient->lastRequest?->getBody());
        $this->assertSame(454, $body['pcId']);
        $this->assertSame('/v1/awb/quote/branch', $httpClient->lastRequest?->getUri()->getPath());
    }

    public function testTrackReturnsStatusAndEvents(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(200, [], json_encode([
                'status' => [
                    'statusCode' => 3,
                    'description' => 'Delivered',
                    'descriptionRo' => 'Livrat',
                    'timestamp' => '2026-09-07T15:25:28+03:00',
                ],
                'events' => [
                    [
                        'statusCode' => 3,
                        'eventCode' => 'DEL',
                        'description' => 'Livrat',
                        'isException' => false,
                        'timestamp' => '2026-09-07T15:25:28+03:00',
                        'location' => ['name' => 'CLUJ', 'code' => 'CJ'],
                    ],
                    'invalid',
                ],
            ], JSON_THROW_ON_ERROR)),
        );

        $tracking = $client->awb()->track(94348148);

        $this->assertSame(ShipmentStatus::Delivered, $tracking->status?->statusCode);
        $this->assertSame('Livrat', $tracking->status?->descriptionRo);
        $this->assertCount(1, $tracking->events);
        $this->assertSame('DEL', $tracking->events[0]->eventCode);
        $this->assertSame('CLUJ', $tracking->events[0]->location?->name);
        $this->assertSame('/v1/awb/94348148', $httpClient->lastRequest?->getUri()->getPath());
        $this->assertSame('', $httpClient->lastRequest?->getUri()->getQuery());
    }

    public function testTrackCanOmitEvents(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(200, [], json_encode([
                'status' => ['statusCode' => 6, 'description' => 'In transit'],
            ], JSON_THROW_ON_ERROR)),
        );

        $tracking = $client->awb()->track(94348148, includeEvents: false);

        $this->assertSame(ShipmentStatus::InTransit, $tracking->status?->statusCode);
        $this->assertSame([], $tracking->events);
        $this->assertSame('events=false', $httpClient->lastRequest?->getUri()->getQuery());
    }

    public function testTrackManySendsAwbsAndMapsNotFound(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(200, [], json_encode([
                'count' => 1,
                'data' => [
                    [
                        'awb' => 94348148,
                        'status' => ['statusCode' => 3, 'description' => 'Delivered'],
                    ],
                ],
                'notFound' => [94000001],
            ], JSON_THROW_ON_ERROR)),
        );

        $batch = $client->awb()->trackMany([94348148, 94000001]);

        $this->assertSame(1, $batch->count);
        $this->assertSame([94000001], $batch->notFound);
        $this->assertCount(1, $batch->results);
        $this->assertSame(94348148, $batch->results->first()?->awb);
        $this->assertSame('{"awbs":[94348148,94000001]}', (string) $httpClient->lastRequest?->getBody());
        $this->assertSame('/v1/awb/tracking', $httpClient->lastRequest?->getUri()->getPath());
    }

    public function testTrackManyCanIncludeEvents(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(200, [], '{"count":0,"data":[],"notFound":[]}'),
        );

        $client->awb()->trackMany([94348148], includeEvents: true);

        $this->assertSame('events=true', $httpClient->lastRequest?->getUri()->getQuery());
    }

    public function testDeleteAcceptsNoContent(): void
    {
        [$client, $httpClient] = TestClientFactory::create(static fn() => new Response(204));

        $client->awb()->delete(94348148);

        $this->assertSame('DELETE', $httpClient->lastRequest?->getMethod());
        $this->assertSame('/v1/awb/94348148', $httpClient->lastRequest?->getUri()->getPath());
    }

    public function testReturnLegsReturnsTypedCollection(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(200, [], json_encode([
                'count' => 1,
                'data' => [
                    [
                        'awb' => 94348150,
                        'type' => 'Retur NT',
                        'typeCode' => null,
                        'status' => ['statusCode' => 5, 'description' => 'Picked up'],
                        'lastEvent' => null,
                    ],
                ],
            ], JSON_THROW_ON_ERROR)),
        );

        $legs = $client->awb()->returnLegs(94348148);

        $this->assertCount(1, $legs);
        $this->assertSame(94348150, $legs->first()?->awb);
        $this->assertSame('Retur NT', $legs->first()?->type);
        $this->assertSame(ShipmentStatus::PickedUp, $legs->first()?->status?->statusCode);
        $this->assertNull($legs->first()?->lastEvent);
        $this->assertSame('/v1/awb/94348148/return', $httpClient->lastRequest?->getUri()->getPath());
    }

    public function testLabelReturnsDocumentInConfiguredFormat(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="awb-94348148.pdf"',
            ], '%PDF-binary%'),
        );

        $document = $client->awb()->label(94348148);

        $this->assertSame('application/pdf', $document->contentType);
        $this->assertSame('awb-94348148.pdf', $document->getFilename());
        $this->assertSame('%PDF-binary%', $document->getContents());
        $this->assertSame('/v1/awb/94348148/label', $httpClient->lastRequest?->getUri()->getPath());
        $this->assertSame('application/pdf', $httpClient->lastRequest?->getHeaderLine('Accept'));
    }

    public function testLabelAsUsesTheRequestedPaperFormat(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(200, ['Content-Type' => 'application/pdf'], '%PDF%'),
        );

        $client->awb()->labelAs(94348148, LabelFormat::A6);

        $this->assertSame('/v1/awb/94348148/label/A6', $httpClient->lastRequest?->getUri()->getPath());
    }

    public function testZplLabelUsesPieceNumber(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(200, ['Content-Type' => 'application/octet-stream'], '^XA^XZ'),
        );

        $document = $client->awb()->zplLabel(94348148, 2);

        $this->assertSame('^XA^XZ', $document->getContents());
        $this->assertSame('/v1/awb/94348148/label/zpl/2', $httpClient->lastRequest?->getUri()->getPath());
        $this->assertSame('application/octet-stream', $httpClient->lastRequest?->getHeaderLine('Accept'));
    }

    public function testValidationFailureExposesFieldErrors(): void
    {
        [$client, ] = TestClientFactory::create(
            static fn() => new Response(422, [], json_encode(
                ResponseFixtureFactory::validationError([
                    'weight' => ['required if shipmentType = parcel or pallet'],
                ]),
                JSON_THROW_ON_ERROR,
            )),
        );

        try {
            $client->awb()->create(['shipmentType' => 'parcel']);
            $this->fail('Expected ApiException to be thrown.');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->status);
            $this->assertSame('validation_failed', $exception->errorCode);
            $this->assertSame([
                'weight' => ['required if shipmentType = parcel or pallet'],
            ], $exception->errors);
            $this->assertStringContainsString('invalid fields', $exception->getMessage());
        }
    }

    public function testNotFoundErrorExposesStableCode(): void
    {
        [$client, ] = TestClientFactory::create(
            static fn() => new Response(404, [], json_encode(
                ResponseFixtureFactory::problem('awb_not_found', 404, 'Resource not found', 'AWB 94348148 does not exist.'),
                JSON_THROW_ON_ERROR,
            )),
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('AWB 94348148 does not exist.');

        $client->awb()->track(94348148);
    }

    public function testCreateRejectsASuccessBodyWithoutTheAwb(): void
    {
        [$client, ] = TestClientFactory::create(static fn() => new Response(201, [], '{}'));

        try {
            $client->awb()->create(['shipmentType' => 'envelope']);
            $this->fail('Expected ApiException to be thrown.');
        } catch (ApiException $exception) {
            $this->assertSame('invalid_response', $exception->errorCode);
            $this->assertStringContainsString('missing the required field "awb"', $exception->getMessage());
        }
    }

    public function testQuoteRejectsASuccessBodyWithoutTheTotal(): void
    {
        [$client, ] = TestClientFactory::create(static fn() => new Response(200, [], '{"baseRate":10}'));

        try {
            $client->awb()->quote(['shipmentType' => 'envelope', 'recipientCounty' => 'Cluj', 'recipientCity' => 'Cluj-Napoca']);
            $this->fail('Expected ApiException to be thrown.');
        } catch (ApiException $exception) {
            $this->assertSame('invalid_response', $exception->errorCode);
            $this->assertStringContainsString('"totalAmount"', $exception->getMessage());
        }
    }
}
