<?php

return [
    /**
     * --------------------------------------------------------------------------
     * Roles
     * --------------------------------------------------------------------------
     * The roles that will be used to bind the workflow to a user
     */
    'roles' => [
        'admin' => 'Admin',
        'user' => 'User',
    ],

    /**
     * --------------------------------------------------------------------------
     * Include Parent
     * --------------------------------------------------------------------------
     * If true, the parent states will be included with child states in the select options
     * If false, only the child states will be included
     */
    'include_parent' => true,

    /**
     * --------------------------------------------------------------------------
     * Enable Policy
     * --------------------------------------------------------------------------
     * If true, the policy will be enabled using Laravel's authorization system
     * If false, the policy will be disabled
     */
    'enable_policy' => true,

    /**
     * --------------------------------------------------------------------------
     * Navigation
     * --------------------------------------------------------------------------
     * These are the navigation settings for the workflow manager.
     */
    'navigation' => [
        'label' => 'State Workflows',
        'group' => 'Settings',
        'sort' => "1",
        'icon' => 'heroicon-o-arrows-right-left',
        'slug' => 'workflows',
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

    /**
     * --------------------------------------------------------------------------
     * Ignore On Actions
     * --------------------------------------------------------------------------
     * The actions on which the workflow should be ignored
     * If the current action is in this array, the workflow will be ignored and all options will be enabled
     */
    'ignored_actions' => [
        'create',
    ],

    /**
     * --------------------------------------------------------------------------
     * Rules (Business rules & conditional routing)
     * --------------------------------------------------------------------------
     * When true, the optional rules layer is enabled. Workflows can define rules
     * that evaluate model data to filter or suggest the next allowed transition.
     * When false, rule evaluation is skipped and behavior is unchanged.
     */
    'rules_enabled' => true,
];
