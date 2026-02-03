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
        'workflow_transition_id',
        'order',
        'field',
        'operator',
        'value',
        'value_type',
        'logical_group',
        'base_field',
    ];

    /**
     * Get the transition this condition belongs to.
     */
    public function workflowTransition(): BelongsTo
    {
        return $this->belongsTo(WorkflowTransition::class);
    }
}
