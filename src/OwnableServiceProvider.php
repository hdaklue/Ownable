<?php

namespace Sowailem\Ownable;

use Illuminate\Support\ServiceProvider;
use Sowailem\Ownable\Console\Commands\PrepareUninstallCommand;

/**
 * Service provider for the Ownable package.
 * 
 * This service provider handles the registration and bootstrapping of the
 * Ownable package, including configuration merging, service binding,
 * migration loading, and asset publishing.
 */
class OwnableServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     * 
     * This method merges the package configuration and binds the Owner
     * service as a singleton in the service container.
     * 
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ownable.php', 'ownable');

        $this->app->singleton('owner', function ($app) {
            return new Owner();
        });
    }

    /**
     * Bootstrap the service provider.
     * 
     * This method loads migrations and publishes configuration and
     * migration files for the package.
     * 
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                PrepareUninstallCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/ownable.php' => config_path('ownable.php'),
        ], 'ownable-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'ownable-migrations');
    }
}