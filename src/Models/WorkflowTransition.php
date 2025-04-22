<?php

namespace Xentixar\WorkflowManager\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowTransition extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'workflow_id',
        'state',
        'parent_id',
    ];

    /**
     * Get the workflow that owns the transition.
     */
    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Get the parent transition of the current transition.
     */
    public function parent()
    {
        return $this->belongsTo(WorkflowTransition::class, 'parent_id');
    }

    /**
     * Get the child transitions of the current transition.
     */
    public function children()
    {
        return $this->hasMany(WorkflowTransition::class, 'parent_id');
    }
}
