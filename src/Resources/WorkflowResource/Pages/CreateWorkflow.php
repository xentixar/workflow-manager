<?php

namespace Xentixar\WorkflowManager\Resources\WorkflowResource\Pages;

use Exception;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Relations\Relation;
use Xentixar\WorkflowManager\Models\Workflow;
use Xentixar\WorkflowManager\Models\WorkflowState;
use Xentixar\WorkflowManager\Resources\WorkflowResource;
use Xentixar\WorkflowManager\Support\Helper;

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
        /** @var Workflow $record */
        $record = $this->record;

        try {
            $enumClass = ($record->model_class)::getStates() ?: ''; // @phpstan-ignore-line
            $states = Helper::getStatesFromEnum($enumClass);
            foreach ($states as $key => $state) {
                WorkflowState::query()
                    ->create([
                        'workflow_id' => $record->id,
                        'state' => $key,
                        'label' => $state,
                    ]);
            }
        } catch (Exception $exception) {
            Notification::make('error')
                ->title('Error')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
