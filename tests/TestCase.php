<?php

namespace ErlandMuchasaj\LaravelGzip\Tests;

use ErlandMuchasaj\LaravelGzip\GzipServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('laravel-gzip.enabled', true);
        $app['config']->set('laravel-gzip.level', 5);
    }

    protected function getPackageProviders($app)
    {
        return [
            GzipServiceProvider::class,
        ];
    }
}
