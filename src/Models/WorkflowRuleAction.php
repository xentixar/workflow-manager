<?php

namespace Xentixar\WorkflowManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowRuleAction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workflow_rule_id',
        'from_state',
        'to_state',
        'assign_role',
    ];

    /**
     * Get the workflow rule that owns the action.
     */
    public function workflowRule(): BelongsTo
    {
        return $this->belongsTo(WorkflowRule::class);
    }
}
