<?php

namespace Sowailem\Ownable;

use Illuminate\Support\ServiceProvider;
use Sowailem\Ownable\Contracts\Ownable;

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
    public function register(): void
    {
        if (!class_exists(Ownable::class)) {
            return;
        }

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
    public function boot(): void
    {
        if (!class_exists(Ownable::class)) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/ownable.php' => config_path('ownable.php'),
        ], 'ownable-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'ownable-migrations');
    }
}