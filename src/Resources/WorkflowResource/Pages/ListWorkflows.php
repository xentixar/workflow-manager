<?php

namespace Xentixar\WorkflowManager\Resources\WorkflowResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Xentixar\WorkflowManager\Resources\WorkflowResource;

class ListWorkflows extends ListRecords
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }
}
