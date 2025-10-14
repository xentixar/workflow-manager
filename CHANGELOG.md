# Changelog

All notable changes to the Workflow Manager package will be documented in this file.

## v2.0.0 - 2025-10-14

### Breaking Changes
- **BREAKING**: Changed contract interface from `Workflows` to `WorkflowsContract`
- **BREAKING**: Modified `getStates()` method to return enum class name instead of array
- **BREAKING**: Renamed form component from `WorkflowStateSelect` to `StateSelect`
- **BREAKING**: Changed component method from `workflowModel()` to `setWorkflowForModel()` and `setRole()`

### Added
- **Filament 4.0 Support**: Full compatibility with Filament v4.0 and higher
- **PHP Enum Support**: Full integration with PHP 8.1+ enums for type-safe state management
- **Enhanced Configuration**: Added `ignored_actions` configuration for flexible workflow validation
- **Improved Helper Class**: Better model discovery and enum state extraction
- **Enhanced Validation**: More robust state transition validation with enum support
- **Consolidated Configuration**: Moved navigation and permission translations to config file for easier customization

### Changed
- Updated to work with Filament 4.0+
- Improved state selection component with better validation rules
- Enhanced database schema for better performance
- Updated documentation with comprehensive examples and troubleshooting

### Fixed
- Fixed interface naming inconsistencies throughout the codebase
- Improved error handling for invalid enum classes
- Better validation of model implementations
- **Clarified Authorization**: Removed incorrect Spatie package dependency - the package uses Laravel's built-in authorization system

## v1.0.1 - 2025-04-23

### Database Schema Changes
- Refactored workflow states into a dedicated `workflow_states` table for better state management
- Modified `workflow_transitions` table to use direct state references via foreign keys
- Improved state transition referencing with `from_state_id` and `to_state_id` fields

### Added
- New `WorkflowState` model to handle state management independently
- Added "States" management page in the admin interface
- Auto-population of workflow states when a new workflow is created
- Enhanced relationship methods between workflows, states, and transitions

### Changed
- Updated WorkflowStateSelect component to work with the new database schema
- Improved flowchart and transition diagrams to reflect the new state management
- Optimized state transition validation with more robust rule checking
- Enhanced diagram rendering with better labels and state representation

### Fixed
- Fixed issue with state transitions not respecting proper parent-child relationships
- Improved error handling when workflow states are used in transitions

## v1.0.0 - 2025-04-22

### Initial Release 🚀

We are excited to announce the first official release of Workflow Manager for Laravel Filament!

Workflow Manager is a powerful package that allows you to define and manage state transitions for your Laravel models through an intuitive admin interface.

#### Features

- **Visual Workflow Management**: Create and edit workflows with a user-friendly interface directly in your Filament admin panel
- **Interactive Diagram**: Visualize workflow states and transitions using Mermaid.js diagrams
- **Model Integration**: Easily add workflow capabilities to any Laravel model
- **Role-Based Access Control**: Define which user roles can manage specific workflows
- **Flexible State Transitions**: Configure allowed transitions between states with parent-child relationships
- **Form Integration**: Use the `WorkflowStateSelect` component in your Filament forms to automatically enforce workflow rules

#### Requirements

- PHP 8.0+
- Laravel 10.0+
- Filament 3.0+

#### Getting Started

Installation is straightforward:

```bash
composer require xentixar/workflow-manager
```

After installing, publish the assets:

```bash
php artisan vendor:publish --tag=workflow-manager-config
php artisan vendor:publish --tag=workflow-manager-migrations
php artisan vendor:publish --tag=workflow-manager-translations
```

Then run the migrations:

```bash
php artisan migrate
```

Refer to the [README.md](./README.md) for complete documentation.

---

Thank you for choosing Workflow Manager for your state transition needs. We welcome your feedback and contributions!