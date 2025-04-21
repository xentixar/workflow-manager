<?php

namespace Xentixar\WorkflowManager;

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
            ->hasConfigFile()
            ->hasMigrations([
                'create_workflows_table',
                'create_workflow_transitions_table',
            ]);
    }

    public function boot(): void
    {
        parent::boot();
    }
}
