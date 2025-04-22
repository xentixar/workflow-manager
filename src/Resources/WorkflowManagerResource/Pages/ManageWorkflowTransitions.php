<?php

namespace Xentixar\WorkflowManager\Resources\WorkflowManagerResource\Pages;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Xentixar\WorkflowManager\Models\WorkflowTransition;
use Xentixar\WorkflowManager\Resources\WorkflowManagerResource;

class ManageWorkflowTransitions extends ManageRelatedRecords
{
    protected static string $resource = WorkflowManagerResource::class;

    protected static string $relationship = 'transitions';

    protected static ?string $recordTitleAttribute = 'state';

    protected static ?string $title = 'Manage Transitions';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-start-on-rectangle';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('parent.state')
                    ->searchable()
                    ->badge(true)
                    ->color(fn($state) => $state !== '*' ? 'success' : 'info')
                    ->default('*')
                    ->label('From State'),
                TextColumn::make('state')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->label('To State'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
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
                Select::make('parent_id')
                    ->label('Parent State')
                    ->placeholder('Select parent state')
                    ->relationship('parent', 'state')
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        $states = [];

                        while ($record) {
                            $states[] = $record->state === '*' ? 'Any' : $record->state;
                            $record = $record->parent;
                        }

                        return implode(' -> ', array_reverse($states));
                    })
                    ->searchable()
                    ->preload()
                    ->hint('Don\'t select anything if this is the first state')
                    ->live(),

                Select::make('state')
                    ->label('To State')
                    ->options(function () {
                        return array_combine(
                            $states = ($this->getOwnerRecord()->model_class)::getStates(),
                            $states
                        );
                    })
                    ->searchable()
                    ->required()
                    ->rules([
                        fn(Get $get, string $operation): Closure => function (string $attribute, $value, Closure $fail) use ($get, $operation) {
                            $parentId = $get('parent_id') ?? null;

                            if ($parentId !== null) {
                                $parentState = WorkflowTransition::query()
                                    ->where('id', $parentId)
                                    ->value('state');

                                if ($parentState === $value) {
                                    $fail('A state cannot have a transition to itself.');
                                }
                            }

                            if ($parentId === null) {
                                $rootExists = $this->getOwnerRecord()->transitions()
                                    ->whereNull('parent_id')
                                    ->exists();

                                if ($rootExists) {
                                    $fail('Only one root state is allowed.');
                                }
                            }

                            $exists = $this->getOwnerRecord()->transitions()
                                ->where('parent_id', $parentId)
                                ->where('state', $value)
                                ->exists();
                                
                            if ($exists) {
                                $fail('This transition already exists.');
                            }
                        },
                    ])
            ])->columns(1);
    }
}
