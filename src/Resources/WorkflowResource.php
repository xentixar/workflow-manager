<?php

namespace Xentixar\WorkflowManager\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Xentixar\WorkflowManager\Models\Workflow;
use Xentixar\WorkflowManager\Resources\WorkflowResource\Pages;
use Xentixar\WorkflowManager\Support\Helper;

class WorkflowResource extends Resource
{
    protected static ?string $model = Workflow::class;

    public static function getNavigationIcon(): string
    {
        return config('workflow-manager.navigation.icon');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('workflow-manager.navigation.group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('workflow-manager.navigation.sort');
    }

    public static function getNavigationLabel(): string
    {
        return config('workflow-manager.navigation.label');
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return config('workflow-manager.navigation.slug');
    }

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('workflow_name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->label('Workflow Name')
                    ->placeholder('Enter workflow name'),

                Select::make('model_class')
                    ->required()
                    ->label('Model Class')
                    ->searchable()
                    ->options(Helper::getAvailableModels())
                    ->live(),

                Select::make('role')
                    ->required()
                    ->searchable()
                    ->options(config('workflow-manager.roles'))
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn ($rule, Get $get) => $rule
                            ->where('model_class', $get('model_class')),
                    )
                    ->validationMessages([
                        'unique' => 'The role has already been assigned to this model.',
                    ]),
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
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->badge()
                    ->searchable()
                    ->label('Model'),
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn ($state) => config('workflow-manager.roles')[$state] ?? $state)
                    ->color('success')
                    ->searchable()
                    ->label('Role'),
            ])
            ->filters([
                SelectFilter::make('model_class')
                    ->label('Model')
                    ->searchable()
                    ->multiple()
                    ->options(Helper::getAvailableModels()),
                SelectFilter::make('role')
                    ->label('Role')
                    ->searchable()
                    ->multiple()
                    ->options(config('workflow-manager.roles', [])),
            ])
            ->recordUrl(null)
            ->recordActions([
                Action::make('viewWorkflow')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->modal()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalHeading('View')
                    ->modalContent(fn ($record) => view('workflow-manager::components.flowchart-diagram', [
                        'workflow' => $record,
                    ])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->recordAction('viewWorkflow')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
