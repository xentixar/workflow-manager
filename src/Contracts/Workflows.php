<?php

namespace Xentixar\WorkflowManager\Contracts;

interface Workflows
{
    /**
     * Get the available states for the workflow.
     */
    public static function getStates(): array;
}