<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Init\VisitorSession\RootServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', env('APP_KEY'));
        $app['config']->set('database.default', env('TEST_DB_CONNECTION', 'pgsql'));
        $app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => env('TEST_DB_HOST', 'postgres_test'),
            'port' => (int) env('TEST_DB_PORT', 5432),
            'database' => env('TEST_DB_DATABASE', 'laravel_test_db'),
            'username' => env('TEST_DB_USERNAME', 'project_user'),
            'password' => env('TEST_DB_PASSWORD', 'secret'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ]);
        $app['config']->set('app.maintenance.driver', 'file');
        $app['config']->set('app.maintenance.store', 'array');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('mail.default', 'array');
        $app['config']->set('mail.mailers.array', ['transport' => 'array']);
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('session.driver', 'array');
    }

    protected function getPackageProviders($app): array
    {
        return [
            \Init\Core\Database\RootServiceProvider::class,
            \Init\Core\Support\RootServiceProvider::class,
            \Init\Spatie\MediaLibrary\RootServiceProvider::class,
            \Init\Core\FeatureManager\RootServiceProvider::class,
            \Init\Core\Filament\RootServiceProvider::class,
            RootServiceProvider::class,
            \Init\Commerce\Catalog\RootServiceProvider::class,
            \Init\Commerce\Cart\RootServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        Artisan::call('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }
}
