<?php

namespace Xentixar\WorkflowManager\Resources\WorkflowResource\Pages;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Xentixar\WorkflowManager\Resources\WorkflowResource;
use Xentixar\WorkflowManager\Support\Helper;

class ManageWorkflowRules extends ManageRelatedRecords
{
    protected static string $resource = WorkflowResource::class;

    protected static string $relationship = 'rules';

    protected static ?string $title = 'Global Rules';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->label('Name'),
                TextColumn::make('priority')
                    ->sortable()
                    ->label('Priority'),
                TextColumn::make('model_class')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-')
                    ->label('Model'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->defaultSort('priority')
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Global Rule')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Add Global Rule')
                    ->modalDescription('Define conditions; when they pass, the actions below decide which state(s) are allowed. Use for workflow-level routing.')
                    ->createAnother(false),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit Rule')
                    ->modalWidth('4xl'),
                DeleteAction::make(),
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
        $statesOptions = $workflow->states->pluck('label', 'state')->toArray();
        $rolesOptions = config('workflow-manager.roles', []);
        $modelClassOptions = Helper::getAvailableModels();
        $modelClass = $workflow->model_class ?? '';

        return $form
            ->components([
                TextInput::make('name')
                    ->required()
                    ->label('Rule Name')
                    ->placeholder('e.g. Expense Approval Rules'),

                Select::make('model_class')
                    ->required()
                    ->label('Model Class')
                    ->options($modelClassOptions)
                    ->default($workflow->model_class)
                    ->searchable(),

                TextInput::make('priority')
                    ->numeric()
                    ->default(0)
                    ->label('Priority')
                    ->hint('Lower number = evaluated first'),

                Toggle::make('is_active')
                    ->default(true)
                    ->label('Active'),

                Repeater::make('conditions')
                    ->relationship()
                    ->schema($this->conditionSchema($modelClass))
                    ->columns(2)
                    ->collapsible()
                    ->itemLabel(fn (array $state) => ($state['field'] ?? '') . ' ' . ($state['operator'] ?? '') . ' ' . ($state['value'] ?? ''))
                    ->label('Conditions')
                    ->hint('When these pass (first matching rule), the actions below define allowed next state(s).'),

                Repeater::make('actions')
                    ->relationship()
                    ->schema([
                        Select::make('from_state')
                            ->required()
                            ->label('From State')
                            ->options($statesOptions)
                            ->searchable(),
                        Select::make('to_state')
                            ->required()
                            ->label('To State')
                            ->options($statesOptions)
                            ->searchable(),
                        Select::make('assign_role')
                            ->label('Assign Role')
                            ->options($rolesOptions)
                            ->searchable(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->itemLabel(fn (array $state) => ($state['from_state'] ?? '') . ' → ' . ($state['to_state'] ?? ''))
                    ->label('Actions')
                    ->hint('From state → To state: which transitions are allowed when conditions match.'),
            ])
            ->columns(1);
    }

    /**
     * Condition fields for global rule form (same as transition conditions).
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
