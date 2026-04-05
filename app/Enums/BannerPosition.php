<?php

namespace App\Enums;

enum BannerPosition: string
{
    case Hero = 'hero';
    case Sidebar = 'sidebar';
    case CategoryTop = 'category_top';
    case HomepageSection = 'homepage_section';
}
