<?php

namespace Xentixar\WorkflowManager\Resources\WorkflowResource\Pages;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Xentixar\WorkflowManager\Models\WorkflowTransition;
use Xentixar\WorkflowManager\Resources\WorkflowResource;

class ManageWorkflowTransitions extends ManageRelatedRecords
{
    protected static string $resource = WorkflowResource::class;

    protected static string $relationship = 'transitions';

    protected static ?string $title = 'Manage Transitions';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-start-on-rectangle';

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
            ->actions([
                DeleteAction::make()
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                                $fail('A state cannot transition to itself.');
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
