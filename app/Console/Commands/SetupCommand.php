<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupCommand extends Command
{
    protected $signature = 'app:setup {--fresh : Run fresh migration} {--demo : Include demo data}';

    protected $description = 'Set up the ShoppingCart application';

    public function handle(): int
    {
        $this->info('Setting up ShoppingCart...');

        // Migrate
        if ($this->option('fresh')) {
            $this->call('migrate:fresh');
        } else {
            $this->call('migrate');
        }

        // Seed
        $this->call('db:seed');

        if ($this->option('demo')) {
            $this->info('Seeding demo data...');
            $this->call('db:seed', ['--class' => 'Database\\Seeders\\DemoSeeder']);
        }

        // Storage link
        $this->call('storage:link', ['--quiet' => true]);

        // Clear caches
        $this->call('config:clear');
        $this->call('cache:clear');
        $this->call('view:clear');

        $this->newLine();
        $this->info('ShoppingCart is ready!');
        $this->newLine();
        $this->table(['Role', 'Email', 'Password'], [
            ['Admin', 'admin@shoppingcart.com', 'password'],
            ['Customer', 'customer@shoppingcart.com', 'password (demo only)'],
            ['Vendor', 'vendor@shoppingcart.com', 'password (demo only)'],
        ]);
        $this->newLine();
        $this->info('Run `composer dev` to start the development server.');

        return self::SUCCESS;
    }
}
