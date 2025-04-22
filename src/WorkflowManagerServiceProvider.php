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
            ->hasTranslations()
            ->hasConfigFile()
            ->hasMigrations([
                'create_workflows_table',
                'create_workflow_transitions_table',
            ]);
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'workflow-manager');

        $this->publishes([
            __DIR__ . '/../config/workflow-manager.php' => config_path('workflow-manager.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../database/migrations/create_workflows_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_workflows_table.php'),
            __DIR__ . '/../database/migrations/create_workflow_transitions_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time() + 1) . '_create_workflow_transitions_table.php'),
        ], 'migrations');

        $this->publishes([
            __DIR__ . '/../resources/lang' => resource_path('lang/vendor/workflow-manager'),
        ], 'translations');

        FilamentAsset::register([
            Js::make('mermaid', __DIR__ . '/../resources/js/mermaid.js'),
        ]);
    }
}
