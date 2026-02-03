<?php

namespace Xentixar\WorkflowManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowRuleCondition extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workflow_rule_id',
        'workflow_transition_id',
        'field',
        'operator',
        'value',
        'value_type',
        'logical_group',
        'base_field',
    ];

    /**
     * Get the workflow rule that owns the condition (global rules only).
     */
    public function workflowRule(): BelongsTo
    {
        return $this->belongsTo(WorkflowRule::class);
    }

    /**
     * Get the transition this condition belongs to (transition-based conditions).
     */
    public function workflowTransition(): BelongsTo
    {
        return $this->belongsTo(WorkflowTransition::class);
    }
}
