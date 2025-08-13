<?php

namespace Sowailem\Ownable\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PrepareUninstallCommand extends Command
{
    protected $signature = 'ownable:prepare-uninstall';
    protected $description = 'Prepare the application for safe removal of the Ownable package';

    public function handle()
    {
        $this->info('Preparing to safely remove Ownable package...');

        // Clear all relevant caches
        $this->call('config:clear');
        $this->call('cache:clear');
        $this->call('route:clear');
        $this->call('view:clear');

        $this->info('✅ Caches cleared successfully.');
        $this->info('✅ You can now safely run: composer remove sowailem/ownable');
        $this->warn('⚠️  Remember to run "php artisan config:clear" after removing the package.');
    }
}