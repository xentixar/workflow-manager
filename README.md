# Workflow Manager for Laravel Filament

![banner](./banner.svg)

A workflow management package for Laravel Filament that lets you define and manage state transitions for your models using PHP enums, with optional per-transition conditions and interactive diagrams.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Transition Conditions](#transition-conditions)
- [Workflow Diagram](#workflow-diagram)
- [Advanced](#advanced)
- [Examples](#examples)
- [Troubleshooting](#troubleshooting)

## Requirements

- **PHP** 8.1+
- **Laravel** 11.0+
- **Filament** 5.0+

## Installation

```bash
composer require xentixar/workflow-manager
```

Publish config and migrations:

```bash
php artisan vendor:publish --tag=workflow-manager-config
php artisan vendor:publish --tag=workflow-manager-migrations
```

Run migrations:

```bash
php artisan migrate
```

## Configuration

After publishing, edit `config/workflow-manager.php`. All options are described below.

### `roles`

Roles used to bind workflows to users. Each workflow is tied to one role; the StateSelect uses the same role to resolve the workflow.

```php
'roles' => [
    'admin' => 'Admin',
    'user' => 'User',
    'manager' => 'Manager',
],
```

Use the **keys** (e.g. `'admin'`) when calling `StateSelect::make('status')->setRole('admin')` and when creating workflows in the admin.

---

### `include_parent`

When **true**, reverse transitions are allowed: users can move back to the previous state(s). When **false**, only forward transitions (from state → to state) are allowed.

```php
'include_parent' => true,
```

- **true**: Back arrows in the diagram; parent states appear in the state select.
- **false**: Strict one-way flow.

---

### `enable_policy`

When **true**, the package uses Laravel’s authorization (policy) for workflow management pages. When **false**, access is not gated by the policy.

```php
'enable_policy' => true,
```

If enabled, ensure your auth setup (e.g. gates, Spatie Permission) grants the permissions listed under `permissions`.

---

### `navigation`

How the Workflow Manager appears in the Filament sidebar.

```php
'navigation' => [
    'label' => 'State Workflows',   // Sidebar label
    'group' => 'Settings',          // Group name
    'sort' => "1",                   // Order within group
    'icon' => 'heroicon-o-arrows-right-left',
    'slug' => 'workflows',           // URL slug
],
```

---

### `permissions`

Permission names used by the workflow policy when `enable_policy` is true. Must match the names you register in your app (e.g. Gates or Spatie).

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

---

### `ignored_actions`

Filament actions during which workflow validation is **skipped**: all state options are shown regardless of transitions and conditions. Useful for create (no current state) or replicate.

```php
'ignored_actions' => [
    'create',
],
```

You can add more (e.g. `'replicate'`) or override per component with `setIgnoredActions()`.

---

### `rules_enabled`

When **true**, per-transition conditions are evaluated: only transitions whose conditions pass are allowed, and the state select disables states that don’t pass. When **false**, conditions are ignored and all defined transitions are allowed.

```php
'rules_enabled' => true,
```

Set to **false** to temporarily disable conditional logic without removing conditions.

---

## Usage

### 1. Model setup

Implement `WorkflowsContract` and use `HasWorkflows`. Return the enum class that defines states from `getStates()`.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Xentixar\WorkflowManager\Contracts\WorkflowsContract;
use Xentixar\WorkflowManager\Traits\HasWorkflows;

class Document extends Model implements WorkflowsContract
{
    use HasWorkflows;

    public static function getStates(): string
    {
        return DocumentStatusEnum::class;
    }
}
```

The enum must be a **backed enum** (e.g. `string`) and should implement `getLabel()` for display.

### 2. State enum

Define states and labels:

```php
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
```

Cast the state attribute to this enum in your model:

```php
protected function casts(): array
{
    return [
        'status' => DocumentStatusEnum::class,
    ];
}
```

### 3. Register the plugin

In your Filament panel provider:

```php
use Xentixar\WorkflowManager\WorkflowManager;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugins([
            WorkflowManager::make(),
        ]);
}
```

### 4. Create workflows in the admin

1. Open **State Workflows** (or your configured label) in Filament.
2. Create a workflow: choose **model**, **workflow name**, and **role**.
3. Open **States** and ensure states match your enum (they can be auto-filled).
4. Open **Transitions**: add transitions (From → To). Optionally add **conditions** (see [Transition Conditions](#transition-conditions)).

### 5. StateSelect in forms

Use the component for the state field so options respect workflow and conditions:

```php
use Xentixar\WorkflowManager\Forms\Components\StateSelect;

StateSelect::make('status')
    ->setWorkflowForModel(Document::class)
    ->setRole('admin')
    ->required()
```

- **setWorkflowForModel($class)** – Model that implements `WorkflowsContract`.
- **setRole($role)** – Role key from config (e.g. `'admin'`). Determines which workflow is used.

**Ignored actions (per component):**

```php
// Merge with config: also ignore 'replicate'
StateSelect::make('status')
    ->setWorkflowForModel(Document::class)
    ->setRole('admin')
    ->setIgnoredActions(['replicate'])
    ->required()

// Replace config list entirely
StateSelect::make('status')
    ->setWorkflowForModel(Document::class)
    ->setRole('admin')
    ->setIgnoredActions(['create', 'replicate'], override: true)
    ->required()
```

Behavior:

- Only **allowed** transitions (and current state) are offered.
- If **conditions** are enabled, only states whose transition conditions pass are offered.
- Selecting the **current state** (no change) is always allowed.
- On **ignored_actions** (e.g. create), all states are offered.

---

## Transition Conditions

Conditions are **per transition**: they decide when a given transition (e.g. Pending → In progress) is allowed. They are configured in **Manage Transitions** (wizard Step 2 or “Edit conditions” on a row).

### Value types

- **Static** – Compare the field to a literal value you type (e.g. `status` is equal to `pending`).
- **Dynamic** – Compare the field to another attribute/relation (e.g. `description` is not equal to **value of** `status`). The “compare with” field supports dot notation (e.g. `user.role`).

### Operators

| Operator | Description | Example (static) | Example (dynamic) |
|--------|-------------|------------------|--------------------|
| `=` | Equals | status is equal to pending | amount is equal to value of total |
| `!=` | Not equals | status is not equal to rejected | description is not equal to value of status |
| `>`, `<`, `>=`, `<=` | Numeric/date comparison | due_date is greater than or equal to 2025-01-01 | count is greater than value of limit |
| `in` | Value in list | status is one of pending,approved | role is one of (value from another field) |
| `like` | SQL-style pattern (`%` = any, `_` = one char), case-insensitive | title matches pattern %hello% | — |
| `regex` | PCRE pattern | code matches regex ^[A-Z]{2}-\d+$ | — |

For **equality** (`=`, `!=`), enum and string are normalized (e.g. `Status::Pending` and `"pending"` compare equal). For **dynamic** conditions, if the “compare with” field is null or blank, the condition fails and that transition is not allowed.

### Logical groups (AND / OR)

When a transition has **multiple** conditions:

- The **first** condition’s result is used as the initial value.
- Each **next** condition is combined with the previous result using that row’s **Logical group** (AND or OR).

Example: Condition 1 AND Condition 2 OR Condition 3 → `(C1 AND C2) OR C3`. Order of rows matters; you can reorder by editing and saving.

### Field paths

Condition **field** and **compare with** (dynamic) support dot notation for relations and nested attributes, e.g. `user.department`, `invoice.total`. The package uses these to read from the current model instance when evaluating (e.g. in StateSelect).

### Managing conditions

- **Add/Edit transition** – Use the wizard; Step 2 is “Conditions” (repeater).
- **Edit conditions only** – Use the **Edit conditions** action on a transition row to edit only the conditions for that transition.

---

## Workflow Diagram

From the workflow list, use **View workflow** (or open a workflow and the diagram) to see the **Cytoscape.js** diagram.

- **Layout**: Deterministic, flowchart-style.
- **Nodes**:
  - **Start** – Green ellipse.
  - **States** – Blue rounded rectangles.
  - **Conditions** – Orange rectangles (when a transition has conditions). Labels are plain English (e.g. “title matches pattern %hello%”, “description is not equal to value of status”).
- **Edges**:
  - **Solid** – Direct transition (no conditions).
  - **Dashed** – Transition with conditions.
- **AND/OR** – Shown on edges between condition nodes when there are multiple conditions.

The diagram updates when you change transitions or conditions. Use **Fit** / **Reset zoom** in the toolbar if needed.

---

## Advanced

### Permissions

With `enable_policy` true, the package uses Laravel’s `Gate`/policy and the `permissions` config. Implement your permissions (e.g. Spatie Laravel Permission) so the configured names (e.g. `view_any_workflow`) are assigned to roles/users as needed.

### Role-based workflows

Different roles can have different workflows for the same model (e.g. admin vs user). Create separate workflows with different **role** values; StateSelect uses the role you pass to `setRole()` to pick the workflow.

### Model discovery

The admin lists models that implement `WorkflowsContract`. The package discovers them from your app; ensure `getStates()` returns a valid enum class name.

### Validation

StateSelect enforces that the chosen value is either the current state or an allowed transition (and that transition’s conditions pass when `rules_enabled` is true). No extra validation is required for basic workflow enforcement.

---

## Examples

### Full example: Document workflow

**Enum**

```php
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
```

**Model**

```php
class Document extends Model implements WorkflowsContract
{
    use HasWorkflows;

    protected $fillable = ['title', 'content', 'status'];

    protected function casts(): array
    {
        return ['status' => DocumentStatusEnum::class];
    }

    public static function getStates(): string
    {
        return DocumentStatusEnum::class;
    }
}
```

**Filament resource form**

```php
use Xentixar\WorkflowManager\Forms\Components\StateSelect;

StateSelect::make('status')
    ->setWorkflowForModel(Document::class)
    ->setRole('admin')
    ->setIgnoredActions(['create'])
    ->required()
```

Then in the admin: create a workflow for `Document` and role `admin`, define transitions (and optionally conditions), and the state select will respect them.

---

## Troubleshooting

| Issue | What to check |
|-------|----------------|
| Workflows not in sidebar | Plugin registered in panel; config `navigation`; permissions if `enable_policy` is true. |
| State options empty or wrong | Correct `setWorkflowForModel` and `setRole`; workflow has transitions from current state; enum and `getStates()` are correct. |
| All states available when they shouldn’t be | Current action may be in `ignored_actions` or `setIgnoredActions`. Remove it if you want validation. |
| Condition never allows transition | `rules_enabled` true; condition logic (AND/OR); dynamic “compare with” not null/blank; enum vs string (equality is normalized). |
| Diagram not loading | Assets published; Cytoscape registered by the package; no JS errors in console. |
| Reverse transitions not available | `include_parent` set to `true` in config. |
| “Call to undefined method” / missing relation | Ensure you’re on a version that uses transitions (and conditions) only; no leftover `rules()` usage. |

---

## License

MIT.

## Credits

- [Xentixar](mailto:xentixar@gmail.com)
- Built for [Laravel Filament](https://filamentphp.com)
