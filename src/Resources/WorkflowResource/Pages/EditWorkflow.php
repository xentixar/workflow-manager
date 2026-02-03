<?php

namespace Xentixar\WorkflowManager\Resources\WorkflowResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Xentixar\WorkflowManager\Resources\WorkflowResource;

class EditWorkflow extends EditRecord
{
    protected static string $resource = WorkflowResource::class;

    protected static ?string $navigationLabel = 'Edit';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
