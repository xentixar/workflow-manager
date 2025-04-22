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
        'view_any' => 'view_any_workflow::manager',
        'view' => 'view_workflow::manager',
        'create' => 'create_workflow::manager',
        'update' => 'update_workflow::manager',
        'delete' => 'delete_workflow::manager',
        'restore' => 'restore_workflow::manager',
        'force_delete' => 'force_delete_workflow::manager',
    ],
];
