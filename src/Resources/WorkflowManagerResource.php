<?php

namespace Xentixar\WorkflowManager\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Xentixar\WorkflowManager\Resources\WorkflowManagerResource\Pages;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Xentixar\WorkflowManager\Models\Workflow;
use Xentixar\WorkflowManager\Support\Helper;

class WorkflowManagerResource extends Resource
{
    protected static ?string $model = Workflow::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('workflow_name')
                    ->required()
                    ->label('Workflow Name')
                    ->placeholder('Enter workflow name'),
                Select::make('model_class')
                    ->required()
                    ->searchable()
                    ->options(Helper::getAvailableModels()),
                Select::make('role')
                    ->searchable()
                    ->options(config('workflow-manager.roles'))
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('workflow_name')
                    ->searchable()
                    ->label('Name'),
                TextColumn::make('model_class')
                    ->badge()
                    ->searchable()
                    ->label('Model'),
                TextColumn::make('role')
                    ->badge()
                    ->color('success')
                    ->searchable()
                    ->label('Role'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkflowManagers::route('/'),
            'create' => Pages\CreateWorkflowManager::route('/create'),
            'edit' => Pages\EditWorkflowManager::route('/{record}/edit'),
        ];
    }
}
