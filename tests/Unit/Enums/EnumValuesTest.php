<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Unit\Enums;

use Daika7ana\DragonStar\Enums\CodPaymentType;
use Daika7ana\DragonStar\Enums\LabelFormat;
use Daika7ana\DragonStar\Enums\ShipmentStatus;
use Daika7ana\DragonStar\Enums\ShipmentType;
use PHPUnit\Framework\TestCase;

class EnumValuesTest extends TestCase
{
    public function testWireValuesAreStable(): void
    {
        $this->assertSame(['cash', 'bankOrder', 'cheque', 'account'], array_column(CodPaymentType::cases(), 'value'));
        $this->assertSame(['A4', 'A6'], array_column(LabelFormat::cases(), 'value'));
        $this->assertSame(['envelope', 'parcel', 'pallet'], array_column(ShipmentType::cases(), 'value'));
        $this->assertSame(
            [1, 2, 3, 4, 5, 6, 7, 18, 19, 21],
            array_column(ShipmentStatus::cases(), 'value'),
        );
    }

    public function testFromMapsWireValues(): void
    {
        $this->assertSame(CodPaymentType::BankOrder, CodPaymentType::from('bankOrder'));
        $this->assertSame(LabelFormat::A6, LabelFormat::from('A6'));
        $this->assertSame(ShipmentType::Pallet, ShipmentType::from('pallet'));
        $this->assertSame(ShipmentStatus::OutForDelivery, ShipmentStatus::from(18));
    }
}
