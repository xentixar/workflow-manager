# Changelog

All notable changes to the Workflow Manager package will be documented in this file.

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