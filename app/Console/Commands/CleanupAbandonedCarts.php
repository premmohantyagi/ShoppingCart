<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Cart;
use Illuminate\Console\Command;

class CleanupAbandonedCarts extends Command
{
    protected $signature = 'cart:cleanup-abandoned {--days=30 : Days after which to clean up}';
    protected $description = 'Clean up abandoned guest carts older than specified days';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $count = Cart::where('status', 'active')
            ->whereNull('user_id')
            ->where('updated_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Cleaned up {$count} abandoned cart(s).");

        return self::SUCCESS;
    }
}
