<?php

namespace App\Enums;

enum ShippingType: string
{
    case Fixed = 'fixed';
    case WeightBased = 'weight_based';
    case ZoneBased = 'zone_based';
    case Free = 'free';
}
