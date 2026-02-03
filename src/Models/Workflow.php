<?php

namespace Xentixar\WorkflowManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'model_class',
        'workflow_name',
        'role',
    ];

    /**
     * Get the states for the workflow.
     */
    public function states(): HasMany
    {
        return $this->hasMany(WorkflowState::class);
    }

    /**
     * Get the transitions for the workflow.
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class);
    }
}
