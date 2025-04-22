<?php

return [
    /**
     * --------------------------------------------------------------------------
     * Navigation
     * --------------------------------------------------------------------------
     * These are the navigation settings for the workflow manager.
     */
    'navigation' => [
        'label' => 'Status Workflow',
        'group' => 'Settings',
        'sort' => "1",
        'icon' => 'heroicon-o-arrows-right-left',
    ],

    /**
     * --------------------------------------------------------------------------
     * Permissions
     * --------------------------------------------------------------------------
     * These are the permissions that are used in the policy.
     */
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
];
