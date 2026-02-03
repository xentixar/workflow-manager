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
        'from_state_id',
        'to_state_id',
    ];

    /**
     * Get the workflow that owns the transition.
     */
    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Get the from state that owns the transition.
     */
    public function fromState()
    {
        return $this->belongsTo(WorkflowState::class, 'from_state_id', 'id');
    }

    /**
     * Get the to state that owns the transition.
     */
    public function toState()
    {
        return $this->belongsTo(WorkflowState::class, 'to_state_id', 'id');
    }

    /**
     * Get the conditions for this transition (evaluated when taking this transition).
     */
    public function conditions()
    {
        return $this->hasMany(WorkflowRuleCondition::class, 'workflow_transition_id')->orderBy('order');
    }
}
