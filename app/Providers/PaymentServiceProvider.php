<?php

declare(strict_types=1);

namespace App\Providers;

use App\Gateways\BankTransferGateway;
use App\Gateways\CODGateway;
use App\Services\PaymentService;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentService::class, function () {
            $service = new PaymentService();
            $service->registerGateway('cod', new CODGateway());
            $service->registerGateway('bank_transfer', new BankTransferGateway());
            return $service;
        });
    }
}
