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
];