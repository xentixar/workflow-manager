<?php

namespace Xentixar\WorkflowManager;

use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WorkflowManagerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'workflow-manager';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->copyAndRegisterServiceProviderInApp()
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations();
            })
            ->hasConfigFile();
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'workflow-manager');

        $this->publishes([
            __DIR__ . '/../config/workflow-manager.php' => config_path('workflow-manager.php'),
        ], 'workflow-manager-config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'workflow-manager-migrations');

        FilamentAsset::register([
            Js::make('mermaid', __DIR__ . '/../resources/js/mermaid.js'),
        ]);
    }
}
