<?php

namespace Xentixar\WorkflowManager\Resources\WorkflowManagerResource\Pages;

use Xentixar\WorkflowManager\Resources\WorkflowManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkflowManager extends EditRecord
{
    protected static string $resource = WorkflowManagerResource::class;

    protected static ?string $navigationLabel = 'Edit';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
