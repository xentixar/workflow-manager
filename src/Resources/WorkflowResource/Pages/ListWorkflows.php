<?php

namespace Xentixar\WorkflowManager\Resources\WorkflowResource\Pages;

use Xentixar\WorkflowManager\Resources\WorkflowResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkflows extends ListRecords
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
        ];
    }
}
