<?php

namespace Init\Commerce\Cart;

use Illuminate\Support\Facades\Route;
use Init\Commerce\Cart\Database\Seeders\RootSeeder;
use Init\Commerce\Cart\Filament\RootPlugin;
use Init\Core\Database\SeederRegistry;
use Init\Core\Filament\FilamentPluginRegistry;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RootServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('commerce_cart')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        if (! $this->app->environment('production')) {
            SeederRegistry::registerIfNotExists('init/commerce-cart', [
                RootSeeder::class,
            ]);
        }

        if (config('commerce_cart.filament.enabled', true)) {
            FilamentPluginRegistry::registerPlugin(
                RootPlugin::make(),
                config('commerce_cart.filament.panel', 'admin'),
            );
        }

        if (class_exists(\Init\Documentation\Support\DocumentationRegistry::class)) {
            \Init\Documentation\Support\DocumentationRegistry::registerPath(
                package: 'init/commerce-cart',
                slug: 'commerce/cart',
                title: 'Commerce Cart',
                path: dirname(__DIR__) . '/README.md',
                group: 'Commerce Foundation',
                sort: 20,
                summary: 'Guest and authenticated carts with visitor-session support.',
            );
        }
    }

    public function packageBooted(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if (config('commerce_cart.api.enabled', true)) {
            Route::prefix('api')
                ->middleware(config('commerce_cart.api.middleware', ['api']))
                ->as(config('commerce_cart.api.name_prefix', 'commerce.cart.api.'))
                ->group(__DIR__ . '/../routes/api.php');
        }
    }
}
