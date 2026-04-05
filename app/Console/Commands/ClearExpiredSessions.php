<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearExpiredSessions extends Command
{
    protected $signature = 'session:clear-expired';
    protected $description = 'Clear expired sessions from the database';

    public function handle(): int
    {
        $lifetime = (int) config('session.lifetime');

        $count = DB::table('sessions')
            ->where('last_activity', '<', now()->subMinutes($lifetime)->getTimestamp())
            ->delete();

        $this->info("Cleared {$count} expired session(s).");

        return self::SUCCESS;
    }
}
