<?php

namespace Xentixar\WorkflowManager\Resources\WorkflowResource\Pages;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Xentixar\WorkflowManager\Resources\WorkflowResource;

class ManageWorkflowStates extends ManageRelatedRecords
{
    protected static string $relationship = 'states';

    protected static string $recordTitleAttribute = 'state';

    protected static ?string $title = 'Manage States';

    protected static string $resource = WorkflowResource::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

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
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])
            ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
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
