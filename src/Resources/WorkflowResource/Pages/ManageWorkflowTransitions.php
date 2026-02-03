<?php

namespace Xentixar\WorkflowManager\Resources\WorkflowResource\Pages;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Xentixar\WorkflowManager\Models\WorkflowTransition;
use Xentixar\WorkflowManager\Resources\WorkflowResource;
use Xentixar\WorkflowManager\Support\Helper;

class ManageWorkflowTransitions extends ManageRelatedRecords
{
    protected static string $resource = WorkflowResource::class;

    protected static string $relationship = 'transitions';

    protected static ?string $title = 'Manage Transitions';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fromState.label')
                    ->searchable()
                    ->badge(true)
                    ->color(fn($state) => $state !== '*' ? 'success' : 'info')
                    ->default('*')
                    ->label('From State'),
                TextColumn::make('toState.label')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->label('To State'),
            ])
            ->modifyQueryUsing(fn($query) => $query->with('conditions'))
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('view')
                    ->label('View Workflow')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->modal()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalHeading('View Workflow')
                    ->modalContent(fn() => view('workflow-manager::components.flowchart-diagram', [
                        'workflow' => $this->getOwnerRecord(),
                    ])),
                CreateAction::make()
                    ->label('Add Transition')
                    ->icon('heroicon-o-plus')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalHeading('Add Transition')
                    ->modalDescription('Add a new transition to the workflow. Optionally add conditions so this transition is only allowed when they pass.')
                    ->createAnother(false)
                    ->using(function (array $data) {
                        $workflow = $this->getOwnerRecord();
                        $transition = $workflow->transitions()->create(Arr::only($data, ['from_state_id', 'to_state_id']));
                        foreach (Arr::get($data, 'conditions', []) as $i => $condition) {
                            $transition->conditions()->create(array_merge(
                                Arr::only($condition, ['field', 'operator', 'value', 'value_type', 'logical_group', 'base_field']),
                                ['order' => $i]
                            ));
                        }
                        return $transition;
                    })
            ])
            ->recordActions([
                Action::make('editConditions')
                    ->label('Conditions')
                    ->icon('heroicon-o-list-bullet')
                    ->color('success')
                    ->modalHeading('Edit conditions')
                    ->modalDescription('Update when this transition is allowed. Order matters: each condition combines with the previous using its Logical Group (AND/OR).')
                    ->modalWidth(Width::FiveExtraLarge)
                    ->schema(fn(): array => [
                        Repeater::make('conditions')
                            ->label('Conditions')
                            ->schema($this->conditionSchema($this->getOwnerRecord()->model_class ?? ''))
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn(array $state) => ($state['field'] ?? '') . ' ' . ($state['operator'] ?? '') . ' ' . ($state['value'] ?? ''))
                            ->addActionLabel('Add condition'),
                    ])
                    ->fillForm(function ($record): array {
                        $record->load('conditions');
                        return [
                            'conditions' => $record->conditions->map(fn($c) => $c->only(['field', 'operator', 'value', 'value_type', 'logical_group', 'base_field']))->toArray(),
                        ];
                    })
                    ->action(function ($record, array $data): void {
                        $this->syncTransitionConditions($record, Arr::get($data, 'conditions', []));
                    })
                    ->successNotificationTitle('Conditions updated'),
                EditAction::make()
                    ->modalHeading('Edit Transition')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->fillForm(function ($record) {
                        $record->load('conditions');
                        return array_merge(
                            $record->only(['from_state_id', 'to_state_id']),
                            ['conditions' => $record->conditions->map(fn($c) => $c->only(['field', 'operator', 'value', 'value_type', 'logical_group', 'base_field']))->toArray()]
                        );
                    })
                    ->after(function ($record, array $data) {
                        $this->syncTransitionConditions($record, Arr::get($data, 'conditions', []));
                    }),
                DeleteAction::make()
            ])
            ->recordAction('edit')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function form(Schema $form): Schema
    {
        $workflow = $this->getOwnerRecord();
        $modelClass = $workflow->model_class ?? '';

        return $form
            ->components([
                Wizard::make([
                    Step::make('Transition')
                        ->label('Transition')
                        ->description('Choose source and target states')
                        ->schema([
                            Select::make('from_state_id')
                                ->label('From State')
                                ->options($workflow->states->pluck('label', 'id'))
                                ->placeholder('Select parent state')
                                ->searchable()
                                ->preload()
                                ->hint('Don\'t select anything if this is the first state')
                                ->live(),

                            Select::make('to_state_id')
                                ->label('To State')
                                ->options($workflow->states->pluck('label', 'id'))
                                ->searchable()
                                ->required()
                                ->rules([
                                    fn(Get $get, $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                        $fromStateId = $get('from_state_id') ?? null;
                                        $toStateId = $value;
                                        $workflow = $this->getOwnerRecord();

                                        if ($fromStateId !== null && $fromStateId == $toStateId) {
                                            $fail('A state cannot have transition to itself.');
                                        }

                                        if ($fromStateId === null) {
                                            $rootExists = $workflow->transitions()->whereNull('from_state_id')->exists();
                                            if ($rootExists) {
                                                $fail('Only one root transition is allowed.');
                                            }
                                        }

                                        $exists = $workflow->transitions()
                                            ->where('from_state_id', $fromStateId)
                                            ->where('to_state_id', $toStateId)
                                            ->where('id', '!=', $record?->id)
                                            ->exists();

                                        if ($exists) {
                                            $fail('This transition already exists.');
                                        }
                                    },
                                ]),
                        ]),

                    Step::make('Conditions')
                        ->label('Conditions')
                        ->description('Optionally add conditions for this transition')
                        ->schema([
                            Repeater::make('conditions')
                                ->label('Conditions')
                                ->schema($this->conditionSchema($modelClass))
                                ->columns(2)
                                ->collapsible()
                                ->itemLabel(fn(array $state) => ($state['field'] ?? '') . ' ' . ($state['operator'] ?? '') . ' ' . ($state['value'] ?? ''))
                                ->addActionLabel('Add condition'),
                        ]),
                ]),
            ])->columns(1);
    }

    /**
     * Sync conditions for a transition: update existing by index, create new, delete removed.
     *
     * @param  array<int, array<string, mixed>>  $conditionsData
     */
    protected function syncTransitionConditions(WorkflowTransition $transition, array $conditionsData): void
    {
        $existing = $transition->conditions()->orderBy('order')->get()->values();
        $keepIds = [];

        foreach ($conditionsData as $i => $condition) {
            $payload = array_merge(
                Arr::only($condition, ['field', 'operator', 'value', 'value_type', 'logical_group', 'base_field']),
                ['order' => $i]
            );
            if ($existing->has($i)) {
                $existing[$i]->update($payload);
                $keepIds[] = $existing[$i]->id;
            } else {
                $new = $transition->conditions()->create($payload);
                $keepIds[] = $new->id;
            }
        }

        $transition->conditions()->whereNotIn('id', $keepIds)->delete();
    }

    /**
     * Shared condition fields for transition and global rule forms.
     *
     * @return array<int, mixed>
     */
    protected function conditionSchema(string $modelClass): array
    {
        return [
            Select::make('field')
                ->required()
                ->label('Field')
                ->options(fn() => Helper::getFillableFieldsForModel($modelClass))
                ->searchable(),
            Select::make('operator')
                ->required()
                ->label('Operator')
                ->options([
                    '>' => '>',
                    '<' => '<',
                    '>=' => '>=',
                    '<=' => '<=',
                    '=' => '=',
                    '!=' => '!=',
                    'in' => 'in',
                ]),
            TextInput::make('value')
                ->required()
                ->label('Value'),
            Select::make('value_type')
                ->live()
                ->default('static')
                ->label('Value Type')
                ->options(['static' => 'Static', 'percentage' => 'Percentage']),
            Select::make('logical_group')
                ->default('AND')
                ->label('Logical Group')
                ->options(['AND' => 'AND', 'OR' => 'OR'])
                ->columnSpanFull()
                ->hint('How this condition combines with the previous result (AND / OR). Ignored for the first condition.'),
            Select::make('base_field')
                ->label('Base Field (for percentage)')
                ->options(fn() => Helper::getFillableFieldsForModel($modelClass))
                ->searchable()
                ->columnSpanFull()
                ->visible(fn($get) => $get('value_type') === 'percentage'),
        ];
    }
}
