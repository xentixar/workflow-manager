<?php

namespace Xentixar\WorkflowManager\Resources\WorkflowManagerResource\Pages;

use Xentixar\WorkflowManager\Resources\WorkflowManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkflowManagers extends ListRecords
{
    protected static string $resource = WorkflowManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
