<?php

namespace Xentixar\WorkflowManager\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Xentixar\WorkflowManager\Resources\WorkflowResource\Pages;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Xentixar\WorkflowManager\Models\Workflow;
use Xentixar\WorkflowManager\Support\Helper;

class WorkflowResource extends Resource
{
    protected static ?string $model = Workflow::class;

    public static function getNavigationIcon(): string
    {
        return __('workflow-manager::workflow-manager.navigation.icon');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('workflow-manager::workflow-manager.navigation.group');
    }

    public static function getNavigationSort(): ?int
    {
        return __('workflow-manager::workflow-manager.navigation.sort');
    }

    public static function getNavigationLabel(): string
    {
        return __('workflow-manager::workflow-manager.navigation.label');
    }

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('workflow_name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->label('Workflow Name')
                    ->placeholder('Enter workflow name'),

                Select::make('model_class')
                    ->required()
                    ->searchable()
                    ->options(Helper::getAvailableModels())
                    ->live(),

                Select::make('role')
                    ->required()
                    ->searchable()
                    ->options(config('workflow-manager.roles'))
                    ->unique(
                        modifyRuleUsing: fn($rule, Get $get) => $rule
                            ->where('model_class', $get('model_class')),
                        ignoreRecord: true,
                    )
                    ->validationMessages([
                        'unique' => 'The role has already been assigned to this model.',
                    ])
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
                    ->formatStateUsing(fn($state) => config('workflow-manager.roles')[$state] ?? $state)
                    ->color('success')
                    ->searchable()
                    ->label('Role'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('transitions')
                    ->label('Transitions')
                    ->color('info')
                    ->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->url(fn(Workflow $record): string => self::getUrl('transitions', ['record' => $record])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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

    /**
     * @return array<mixed>
     */
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            'edit' => Pages\EditWorkflow::class,
            'states' => Pages\ManageWorkflowStates::class,
            'transitions' => Pages\ManageWorkflowTransitions::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkflows::route('/'),
            'create' => Pages\CreateWorkflow::route('/create'),
            'edit' => Pages\EditWorkflow::route('/{record}/edit'),
            'transitions' => Pages\ManageWorkflowTransitions::route('/{record}/transitions'),
            'states' => Pages\ManageWorkflowStates::route('/{record}/states'),
        ];
    }
}
