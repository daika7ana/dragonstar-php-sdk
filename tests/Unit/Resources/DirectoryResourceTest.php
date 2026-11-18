<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Unit\Resources;

use Daika7ana\DragonStar\Tests\Support\TestClientFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class DirectoryResourceTest extends TestCase
{
    public function testCountiesReturnsCodeToNameMap(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(200, [], '{"count":2,"data":{"B":"Bucuresti","CJ":"Cluj"}}'),
        );

        $counties = $client->directory()->counties();

        $this->assertCount(2, $counties);
        $this->assertSame('Cluj', $counties->get('CJ'));
        $this->assertSame('Bucuresti', $counties->get('B'));
        $this->assertSame('/v1/counties', $httpClient->lastRequest?->getUri()->getPath());
    }

    public function testLocalitiesEncodesTheCountyName(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(200, [], '{"count":2,"data":["Bistrita","Nasaud"]}'),
        );

        $localities = $client->directory()->localities('Bistrița-Năsăud');

        $this->assertSame(['Bistrita', 'Nasaud'], $localities->all());
        $this->assertSame(
            '/v1/counties/Bistri%C8%9Ba-N%C4%83s%C4%83ud/localities',
            $httpClient->lastRequest?->getUri()->getPath(),
        );
    }

    public function testLocalitiesWithDistancesParsesTheSuffix(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(200, [], '{"count":2,"data":["Apahida:14","Cluj-Napoca:0"]}'),
        );

        $localities = $client->directory()->localitiesWithDistances('Cluj');

        $this->assertSame('Apahida', $localities->first()?->name);
        $this->assertSame(14, $localities->first()?->km);
        $this->assertSame(0, $localities->last()?->km);
        $this->assertSame('/v1/counties/Cluj/localities-km', $httpClient->lastRequest?->getUri()->getPath());
    }

    public function testBranchesReturnsTheMasterAndTypedBranches(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => new Response(200, [], json_encode([
                'masterId' => 454,
                'count' => 1,
                'data' => [
                    [
                        'branchId' => 455,
                        'name' => 'Magazin Cluj',
                        'county' => 'Cluj',
                        'city' => 'Cluj-Napoca',
                        'address' => 'Str. Fabricii nr. 10',
                    ],
                ],
            ], JSON_THROW_ON_ERROR)),
        );

        $branches = $client->directory()->branches();

        $this->assertSame(454, $branches->masterId);
        $this->assertCount(1, $branches->branches);
        $this->assertSame(455, $branches->branches->first()?->branchId);
        $this->assertSame('Magazin Cluj', $branches->branches->first()?->name);
        $this->assertSame('/v1/branches', $httpClient->lastRequest?->getUri()->getPath());
    }
}
