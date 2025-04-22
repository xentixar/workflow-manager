<?php

namespace Xentixar\WorkflowManager;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Xentixar\WorkflowManager\Resources\WorkflowResource;

class WorkflowManager implements Plugin
{
    public static function make()
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'workflow-manager';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            WorkflowResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
