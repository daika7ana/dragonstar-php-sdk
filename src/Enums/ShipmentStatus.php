<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Enums;

enum ShipmentStatus: int
{
    case Generated = 1;
    case DeliveryException = 2;
    case Delivered = 3;
    case Notified = 4;
    case PickedUp = 5;
    case InTransit = 6;
    case Returned = 7;
    case OutForDelivery = 18;
    case Redirected = 19;
    case DestinationCenter = 21;
}
