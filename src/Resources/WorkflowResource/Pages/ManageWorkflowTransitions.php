<?php

namespace Xentixar\WorkflowManager\Resources\WorkflowResource\Pages;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Xentixar\WorkflowManager\Resources\WorkflowResource;

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
                    ->modalDescription('Add a new transition to the workflow.')
                    ->createAnother(false)
            ])
            ->recordActions([
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
        return $form
            ->components([
                Select::make('from_state_id')
                    ->label('From State')
                    ->options($this->getOwnerRecord()->states->pluck('label', 'id'))
                    ->placeholder('Select parent state')
                    ->searchable()
                    ->preload()
                    ->hint('Don\'t select anything if this is the first state')
                    ->live(),

                Select::make('to_state_id')
                    ->label('To State')
                    ->options($this->getOwnerRecord()->states->pluck('label', 'id'))
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
                    ])

            ])->columns(1);
    }
}
