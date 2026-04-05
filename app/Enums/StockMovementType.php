<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Opening = 'opening';
    case Purchase = 'purchase';
    case ManualIn = 'manual_in';
    case ManualOut = 'manual_out';
    case Reserve = 'reserve';
    case Release = 'release';
    case Sold = 'sold';
    case ReturnIn = 'return_in';
    case DamageOut = 'damage_out';
    case Adjustment = 'adjustment';
}
