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
     * If true, the policy will be enabled
     * If false, the policy will be disabled
     * 
     * @note: You should have to install and configure the spatie/laravel-permission package to use this feature.
     */
    'enable_policy' => true,
];