<?php

namespace Xentixar\WorkflowManager\Resources\WorkflowResource\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Xentixar\WorkflowManager\Resources\WorkflowResource;

class ManageWorkflowStates extends ManageRelatedRecords
{
    protected static string $relationship = 'states';

    protected static string $recordTitleAttribute = 'state';

    protected static ?string $title = 'Manage States';

    protected static string $resource = WorkflowResource::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('state')
                    ->searchable()
                    ->badge(),
                TextColumn::make('label')
                    ->searchable()
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add State')
                    ->icon('heroicon-o-plus')
                    ->createAnother(false)
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('state')
                    ->unique(
                        modifyRuleUsing: fn($rule) => $rule->where('workflow_id', $this->getOwnerRecord()->id),
                        ignoreRecord: true
                    )
                    ->required()
                    ->label('State'),
                TextInput::make('label')
                    ->required()
                    ->label('Label'),
            ])->columns(1);
    }
}
