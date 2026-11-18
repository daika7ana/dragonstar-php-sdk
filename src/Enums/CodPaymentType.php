<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Enums;

enum CodPaymentType: string
{
    case Cash = 'cash';
    case BankOrder = 'bankOrder';
    case Cheque = 'cheque';
    case Account = 'account';
}
