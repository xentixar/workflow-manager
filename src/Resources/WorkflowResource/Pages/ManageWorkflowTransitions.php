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
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
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
            ->modifyQueryUsing(fn ($query) => $query->with('conditions'))
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('view')
                    ->label('View Workflow')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->modal()
                    ->modalSubmitAction(false)
                    ->modalHeading('View Workflow')
                    ->modalContent(fn() => view('workflow-manager::components.flowchart-diagram', [
                        'workflow' => $this->getOwnerRecord(),
                    ])),
                CreateAction::make()
                    ->label('Add Transition')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Add Transition')
                    ->modalDescription('Add a new transition to the workflow. Optionally add conditions so this transition is only allowed when they pass.')
                    ->createAnother(false)
                    ->using(function (array $data) {
                        $workflow = $this->getOwnerRecord();
                        $transition = $workflow->transitions()->create(Arr::only($data, ['from_state_id', 'to_state_id']));
                        foreach (Arr::get($data, 'conditions', []) as $condition) {
                            $transition->conditions()->create(Arr::only($condition, ['field', 'operator', 'value', 'value_type', 'logical_group', 'base_field']));
                        }
                        return $transition;
                    })
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit Transition')
                    ->modalWidth('4xl')
                    ->fillForm(function ($record) {
                        $record->load('conditions');
                        return array_merge(
                            $record->only(['from_state_id', 'to_state_id']),
                            ['conditions' => $record->conditions->map(fn ($c) => $c->only(['field', 'operator', 'value', 'value_type', 'logical_group', 'base_field']))->toArray()]
                        );
                    })
                    ->after(function ($record, array $data) {
                        $record->conditions()->delete();
                        foreach (Arr::get($data, 'conditions', []) as $condition) {
                            $record->conditions()->create(Arr::only($condition, ['field', 'operator', 'value', 'value_type', 'logical_group', 'base_field']));
                        }
                    }),
                DeleteAction::make()
            ])
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
                        fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
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
                                ->exists();

                            if ($exists) {
                                $fail('This transition already exists.');
                            }
                        },
                    ]),

                Repeater::make('conditions')
                    ->label('Conditions')
                    ->schema($this->conditionSchema($modelClass))
                    ->columns(2)
                    ->collapsible()
                    ->itemLabel(fn (array $state) => ($state['field'] ?? '') . ' ' . ($state['operator'] ?? '') . ' ' . ($state['value'] ?? ''))
                    ->addActionLabel('Add condition')
                    ->hint('When set, this transition is only allowed when all (or any, per group) conditions pass on the record.'),
            ])->columns(1);
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
                ->options(fn () => Helper::getFillableFieldsForModel($modelClass))
                ->searchable(),
            Select::make('operator')
                ->required()
                ->label('Operator')
                ->options([
                    '>' => '>', '<' => '<', '>=' => '>=', '<=' => '<=',
                    '=' => '=', '!=' => '!=', 'in' => 'in',
                ]),
            TextInput::make('value')
                ->required()
                ->label('Value'),
            Select::make('value_type')
                ->default('static')
                ->label('Value Type')
                ->options(['static' => 'Static', 'percentage' => 'Percentage']),
            Select::make('logical_group')
                ->default('AND')
                ->label('Logical Group')
                ->options(['AND' => 'AND', 'OR' => 'OR']),
            Select::make('base_field')
                ->label('Base Field (for percentage)')
                ->options(fn () => Helper::getFillableFieldsForModel($modelClass))
                ->searchable()
                ->visible(fn ($get) => $get('value_type') === 'percentage'),
        ];
    }
}
