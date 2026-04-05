<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\ViewComposers\FrontLayoutComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('layouts.front', FrontLayoutComposer::class);
    }
}
