<?php

namespace ErlandMuchasaj\LaravelGzip;

use Illuminate\Support\ServiceProvider;

class GzipServiceProvider extends ServiceProvider
{
    /**
     * Package name.
     * Abstract type to bind FileUploader as in the Service Container.
     */
    public static string $abstract = 'laravel-gzip';

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/laravel-gzip.php',
            static::$abstract
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/laravel-gzip.php' => config_path(static::$abstract.'.php'),
            ], 'config');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [static::$abstract];
    }
}
