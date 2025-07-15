<?php

namespace ErlandMuchasaj\LaravelGzip;

use ErlandMuchasaj\LaravelGzip\Middleware\GzipEncodeResponse;
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
            __DIR__.'/../config/'.static::$abstract.'.php',
            static::$abstract
        );

        $this->app->singleton(static::$abstract, function ($app) {
            return new GzipEncodeResponse;
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/'.static::$abstract.'.php' => config_path(static::$abstract.'.php'),
            ], 'config');
        }

        $this->app['router']->aliasMiddleware('gzip', GzipEncodeResponse::class);
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
