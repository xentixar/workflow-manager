# Workflow Manager for Laravel Filament

![banner](./banner.svg)

A powerful workflow management package for Laravel Filament that allows you to define and manage state transitions for your Laravel models using PHP enums.

## Overview

Workflow Manager provides a simple yet flexible way to define and manage workflows in your Laravel Filament application. It allows you to:

- Define workflow states for your models using PHP enums
- Configure transitions between states with validation
- Visualize workflows with an intuitive admin interface
- Control access to state transitions based on user roles
- Add workflow management to any Laravel model
- Support for role-based workflow management
- Include parent state transitions (configurable)

## Requirements

- PHP 8.1 or higher
- Laravel 11.0 or higher
- Filament 4.0 or higher

## Installation

You can install the package via composer:

```bash
composer require xentixar/workflow-manager
```

After installing the package, you can publish the assets separately:

```bash
# Publish configuration (includes navigation and permission settings)
php artisan vendor:publish --tag=workflow-manager-config

# Publish migrations
php artisan vendor:publish --tag=workflow-manager-migrations
```

> **Note**: Starting from v2.0, translations have been moved to the configuration file for easier management. You no longer need to publish separate translation files.

Then run the migrations:

```bash
php artisan migrate
```

## Configuration

After publishing the configuration file, you can find it at `config/workflow-manager.php`. Here are the available configuration options:

### Roles

Define the roles that will be used to bind workflows to users:

```php
'roles' => [
    'admin' => 'Admin',
    'user' => 'User',
    // Add your custom roles here
],
```

### Include Parent

Determine whether parent states should be included with child states in select options. When enabled, users can transition back to previous states:

```php
'include_parent' => true,
```

### Enable Policy

Enable or disable the workflow policy. When enabled, it uses Laravel's built-in authorization system:

```php
'enable_policy' => true,
```

### Ignored Actions

Specify which Filament actions should ignore workflow validation. During these actions, all state options will be available:

```php
'ignored_actions' => [
    'create',
],
```

### Navigation

Customize how the Workflow Manager appears in the Filament navigation:

```php
'navigation' => [
    'label' => 'State Workflows',
    'group' => 'Settings',
    'sort' => "1",
    'icon' => 'heroicon-o-arrows-right-left',
    'slug' => 'workflows',
],
```

### Permissions

Define the permission names used for authorization when policies are enabled:

```php
'permissions' => [
    'view_any' => 'view_any_workflow',
    'view' => 'view_workflow',
    'create' => 'create_workflow',
    'update' => 'update_workflow',
    'delete' => 'delete_workflow',
    'restore' => 'restore_workflow',
    'force_delete' => 'force_delete_workflow',
    'reorder' => 'reorder_workflow',
    'replicate' => 'replicate_workflow',
],
```

## Configuration Customization

All customization options are now consolidated in the configuration file for easier management. After publishing the configuration file, you can modify:

## Usage

### Setting Up Your Models

To use Workflow Manager with your models, implement the `WorkflowsContract` interface and use the `HasWorkflows` trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Xentixar\WorkflowManager\Contracts\WorkflowsContract;
use Xentixar\WorkflowManager\Traits\HasWorkflows;

class YourModel extends Model implements WorkflowsContract
{
    use HasWorkflows;

    /**
     * Get the enum class representing available states.
     *
     * @return string
     */
    public static function getStates(): string
    {
        return YourModelStatusEnum::class;
    }
}
```

### Creating State Enums

Create a PHP enum that defines your workflow states:

```php
<?php

namespace App\Enums;

enum YourModelStatusEnum: string
{
    case DRAFT = 'draft';
    case REVIEW = 'review';
    case APPROVED = 'approved';
    case PUBLISHED = 'published';
    case REJECTED = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::REVIEW => 'Under Review',
            self::APPROVED => 'Approved',
            self::PUBLISHED => 'Published',
            self::REJECTED => 'Rejected',
        };
    }
}
```

The `getStates()` method should return the fully qualified class name of your enum. The enum must be a backed enum with string values and should include a `getLabel()` method for human-readable labels.

### Registering the Plugin with Filament

Register the Workflow Manager plugin in your Filament panel provider:

```php
use Xentixar\WorkflowManager\WorkflowManager;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...other configuration
        ->plugins([
            WorkflowManager::make(),
        ]);
}
```

### Creating Workflows

Once the package is installed and configured, you can create and manage workflows through the Filament admin panel. Navigate to the "Workflows" section in your Filament admin panel to:

1. Create a new workflow by selecting the target model
2. Define the workflow name
3. Assign a role that can manage this workflow
4. Set up transitions between states

### Workflow State Selection in Forms

You can use the `StateSelect` component in your Filament forms:

```php
use Xentixar\WorkflowManager\Forms\Components\StateSelect;

StateSelect::make('status')
    ->setWorkflowForModel(YourModel::class)
    ->setRole('admin')
    ->required()
```

The component requires two method calls:
1. `setWorkflowForModel()` - The model class that implements the `WorkflowsContract` interface
2. `setRole()` - The role that should manage this workflow (must match a key in the roles configuration)

This will automatically display the appropriate state options based on the defined workflow and the current state of the model, enforcing workflow transition rules.

### Transition Rules

When defining state transitions in the admin panel, you can specify:

1. **From State**: The starting state for the transition
2. **To State**: The destination state for the transition
3. **Workflow Context**: Each transition belongs to a specific workflow and role

The package automatically validates transitions based on your defined rules:
- Users can only transition to states that have been explicitly defined as valid transitions
- If `include_parent` is enabled, users can also transition back to previous states
- During ignored actions (like 'create'), all states are available regardless of workflow rules

## Visualizing Workflows

The Workflow Manager includes an admin interface through Filament where you can:

- View all defined workflows
- Create new workflows for your models
- Manage states for each workflow
- Define transitions between states
- Visualize the workflow structure

The admin interface automatically detects models that implement the `WorkflowsContract` interface and makes them available for workflow creation.

## Advanced Usage

### Custom Validation Rules

The `StateSelect` component automatically applies validation rules based on your workflow configuration. It uses Laravel's `Rule::in()` to ensure only valid transitions are allowed.

### Role-based Workflow Management

Different roles can have different workflows for the same model. For example:
- Admins might have access to all state transitions
- Regular users might have limited transition options
- Managers might have their own workflow with different rules

### Automatic Model Discovery

The package includes a `Helper` class that automatically discovers models in your application that implement the `WorkflowsContract` interface, making them available in the admin panel.

### Enum Integration

The package is designed to work seamlessly with PHP enums, providing type safety and better code organization compared to string-based states.

### Integrating with Permissions

When `enable_policy` is set to `true`, the package will use Laravel's built-in authorization system to control access to workflows. You can implement permissions using Laravel Gates, Policies, or any authorization package like Spatie's Laravel Permission. The policy checks for permissions using Laravel's standard `$user->can()` method.

## Examples

### A Simple Approval Workflow

```php
// Enum definition
<?php

namespace App\Enums;

enum DocumentStatusEnum: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }
}

// Model definition
class Document extends Model implements WorkflowsContract
{
    use HasWorkflows;

    protected $fillable = ['title', 'content', 'status'];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatusEnum::class,
        ];
    }

    public static function getStates(): string
    {
        return DocumentStatusEnum::class;
    }
}

// In your Filament resource
use Xentixar\WorkflowManager\Forms\Components\StateSelect;

// Form definition
public static function form(Form $form): Form
{
    return $form
        ->schema([
            TextInput::make('title')->required(),
            Textarea::make('content')->required(),
            StateSelect::make('status')
                ->setWorkflowForModel(Document::class)
                ->setRole('admin')
                ->required(),
        ]);
}
```

## Troubleshooting

### Common Issues

1. **Workflows not appearing**: Make sure your model correctly implements the `WorkflowsContract` interface and uses the `HasWorkflows` trait.
2. **Cannot access workflow management**: Check that the user has the appropriate permissions if you have enabled policies.
3. **State options not showing**: Verify that you have defined states in your model's `getStates()` method and that it returns a valid enum class name.
4. **Enum not found**: Ensure your enum class exists and is properly namespaced. The `getStates()` method should return the fully qualified class name.
5. **Invalid transitions**: Check that you have defined the appropriate transitions in the workflow admin interface.
6. **Component not found**: Make sure you're importing `StateSelect` from the correct namespace: `Xentixar\WorkflowManager\Forms\Components\StateSelect`.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the MIT license.

## Credits

- Developed by [Xentixar](mailto:xentixar@gmail.com).
- Built for Laravel Filament framework.