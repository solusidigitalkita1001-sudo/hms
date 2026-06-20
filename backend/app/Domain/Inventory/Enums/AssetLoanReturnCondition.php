<?php

namespace App\Domain\Inventory\Enums;

enum AssetLoanReturnCondition: string
{
    case Good = 'good';
    case Damaged = 'damaged';
    case Lost = 'lost';
}
