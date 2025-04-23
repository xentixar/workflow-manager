<?php

namespace Xentixar\WorkflowManager\Resources\WorkflowResource\Pages;

use Xentixar\WorkflowManager\Resources\WorkflowResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Xentixar\WorkflowManager\Models\WorkflowState;

class CreateWorkflow extends CreateRecord
{
    protected static string $resource = WorkflowResource::class;

    protected function getRedirectUrl(): string
    {
        return WorkflowResource::getUrl('transitions', [
            'record' => $this->record,
        ]);
    }

    protected function afterCreate(): void
    {
        $states = (new $this->record->model_class())->getStates() ?: [];
        foreach ($states as $key => $state) {
            WorkflowState::query()
                ->create([
                    'workflow_id' => $this->record->id,
                    'state' => $key,
                    'label' => $state ?? null,
                ]);
        }
    }
}
