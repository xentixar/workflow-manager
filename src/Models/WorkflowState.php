<?php

namespace Xentixar\WorkflowManager\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowState extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'state',
        'workflow_id',
        'label',
    ];

    /**
     * Get the workflow that owns the state.
     */
    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Get the transitions for the state.
     */
    public function transitions()
    {
        return $this->hasMany(WorkflowTransition::class, 'from_state', 'state');
    }
}