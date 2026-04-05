<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\InventoryService;
use Illuminate\Console\Command;

class ReleaseExpiredStockReservations extends Command
{
    protected $signature = 'inventory:release-expired';
    protected $description = 'Release stock reservations that have expired';

    public function handle(InventoryService $inventoryService): int
    {
        $count = $inventoryService->releaseExpiredReservations();
        $this->info("Released {$count} expired stock reservation(s).");
        return self::SUCCESS;
    }
}
