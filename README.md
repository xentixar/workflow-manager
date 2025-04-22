# Workflow Manager for Laravel Filament

![banner](./banner.svg)

A powerful workflow management package for Laravel Filament that allows you to define and manage state transitions for your Laravel models.

## Overview

Workflow Manager provides a simple yet flexible way to define and manage workflows in your Laravel Filament application. It allows you to:

- Define workflow states for your models
- Configure transitions between states
- Visualize workflows with Mermaid.js diagrams
- Control access to state transitions based on user roles
- Add workflow management to any Laravel model

## Requirements

- PHP 8.0 or higher
- Laravel 10.0 or higher
- Filament 3.0 or higher

## Installation

You can install the package via composer:

```bash
composer require xentixar/workflow-manager
```

After installing the package, you can publish the assets separately:

```bash
# Publish configuration
php artisan vendor:publish --tag=workflow-manager-config

# Publish migrations
php artisan vendor:publish --tag=workflow-manager-migrations

# Publish translations
php artisan vendor:publish --tag=workflow-manager-translations
```

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

Determine whether parent states should be included with child states in select options:

```php
'include_parent' => true,
```

### Enable Policy

Enable or disable the workflow policy. Note that you need to install and configure the `spatie/laravel-permission` package to use this feature:

```php
'enable_policy' => true,
```

## Language Files

The package includes language files that allow you to customize various text elements used throughout the workflow manager. After publishing translations, you can find the language files at `resources/lang/vendor/workflow-manager/`.

The language file includes:

```php
'navigation' => [
    'label' => 'Status Workflow',
    'group' => 'Settings',
    'sort' => "1",
    'icon' => 'heroicon-o-arrows-right-left',
],

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

You can modify these values to customize how the Workflow Manager appears in the Filament navigation and the permission names used for authorization.

## Usage

### Setting Up Your Models

To use Workflow Manager with your models, implement the `Workflows` interface and use the `HasWorkflows` trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Xentixar\WorkflowManager\Contracts\Workflows;
use Xentixar\WorkflowManager\Traits\HasWorkflows;

class YourModel extends Model implements Workflows
{
    use HasWorkflows;

    /**
     * Get the available states for the workflow.
     *
     * @return array
     */
    public static function getStates(): array
    {
        return [
            'draft' => 'Draft',
            'review' => 'Under Review',
            'approved' => 'Approved',
            'published' => 'Published',
            'rejected' => 'Rejected',
        ];
    }
}
```

The `getStates()` method should return an array of possible states for your model, with keys as state identifiers and values as human-readable labels.

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

You can use the `WorkflowStateSelect` component in your Filament forms:

```php
use Xentixar\WorkflowManager\Forms\Components\WorkflowStateSelect;

WorkflowStateSelect::make('status')
    ->workflowModel(YourModel::class, 'admin')
    ->required()
```

The `workflowModel()` method takes two parameters:
1. The model class that implements the `Workflows` interface
2. The role that should manage this workflow (must match a key in the roles configuration)

This will automatically display the appropriate state options based on the defined workflow and the current state of the model.

### Transition Rules

When defining state transitions in the admin panel, you can specify:

1. The starting state
2. The possible next states (transitions)

## Visualizing Workflows

Workflow Manager includes a visualization feature using Mermaid.js. When editing a workflow in the Filament admin panel, you can see a diagram of the workflow states and transitions.

The visualization leverages the powerful [Mermaid.js](https://mermaid-js.github.io/) library to generate interactive diagrams of your workflow states and transitions. This makes it easy to understand complex workflows at a glance and helps in designing effective state machines for your application's processes.

## Advanced Usage

### Custom Transition Logic

You can implement custom logic for state transitions by extending the base `WorkflowTransition` model or by adding event listeners.

### Integrating with Permissions

When `enable_policy` is set to `true`, the package will use Spatie's Laravel Permission package to control access to workflows. Make sure to configure your permissions accordingly.

## Examples

### A Simple Approval Workflow

```php
// Model definition
class Document extends Model implements Workflows
{
    use HasWorkflows;

    public static function getStates(): array
    {
        return [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
    }
}

// In your Filament resource
use Xentixar\WorkflowManager\Forms\Components\WorkflowStateSelect;

// Form definition
public static function form(Form $form): Form
{
    return $form
        ->schema([
            // Other fields...
            WorkflowStateSelect::make('status')
                ->workflowModel(Document::class, 'admin')
                ->required(),
        ]);
}
```

## Troubleshooting

### Common Issues

1. **Workflows not appearing**: Make sure your model correctly implements the `Workflows` interface and uses the `HasWorkflows` trait.
2. **Cannot access workflow management**: Check that the user has the appropriate permissions if you have enabled policies.
3. **State options not showing**: Verify that you have defined states in your model's `getStates()` method.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the MIT license.

## Credits

- Developed by [Xentixar](mailto:xentixar@gmail.com).
- Workflow visualization powered by [Mermaid.js](https://mermaid-js.github.io/), a JavaScript-based diagramming and charting tool that uses Markdown-inspired text definitions to create and modify diagrams dynamically.