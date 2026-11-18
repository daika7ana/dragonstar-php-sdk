<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Enums;

enum ShipmentType: string
{
    case Envelope = 'envelope';
    case Parcel = 'parcel';
    case Pallet = 'pallet';
}
