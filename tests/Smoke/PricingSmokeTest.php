<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Smoke;

use Daika7ana\DragonStar\DTOs\Awb\AwbQuoteRequest;
use Daika7ana\DragonStar\Enums\ShipmentType;
use PHPUnit\Framework\Attributes\Group;

#[Group('smoke')]
class PricingSmokeTest extends SmokeTestCase
{
    public function testQuoteReturnsAmounts(): void
    {
        $quote = $this->client()->awb()->quote($this->quoteRequest());

        $this->assertGreaterThan(0.0, $quote->totalAmount);
        $this->assertEqualsWithDelta($quote->netAmount + $quote->vatAmount, $quote->totalAmount, 0.01);
    }

    public function testQuoteForBranchReturnsAmounts(): void
    {
        $branch = $this->client()->directory()->branches()->branches->first();

        if ($branch === null) {
            $this->markTestSkipped('The account has no branches to quote for.');
        }

        $quote = $this->client()->awb()->quoteForBranch($branch->branchId, $this->quoteRequest());

        $this->assertGreaterThan(0.0, $quote->totalAmount);
    }

    private function quoteRequest(): AwbQuoteRequest
    {
        return new AwbQuoteRequest(
            shipmentType: ShipmentType::Parcel,
            recipientCounty: 'Cluj',
            recipientCity: 'Cluj-Napoca',
            weight: 1.0,
        );
    }
}
