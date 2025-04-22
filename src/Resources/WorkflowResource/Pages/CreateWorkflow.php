<?php

namespace Xentixar\WorkflowManager\Resources\WorkflowResource\Pages;

use Xentixar\WorkflowManager\Resources\WorkflowResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkflow extends CreateRecord
{
    protected static string $resource = WorkflowResource::class;

    protected function getRedirectUrl(): string
    {
        return WorkflowResource::getUrl('transitions', [
            'record' => $this->record,
        ]);
    }
}
