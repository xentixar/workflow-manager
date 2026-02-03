<?php

namespace Xentixar\WorkflowManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowRule extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workflow_id',
        'workflow_transition_id',
        'name',
        'model_class',
        'priority',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the workflow that owns the rule.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Get the transition this rule is bound to (when set, rule applies only to this transition).
     */
    public function workflowTransition(): BelongsTo
    {
        return $this->belongsTo(WorkflowTransition::class);
    }

    /**
     * Get the conditions for the rule.
     */
    public function conditions(): HasMany
    {
        return $this->hasMany(WorkflowRuleCondition::class);
    }

    /**
     * Get the actions for the rule.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowRuleAction::class);
    }
}
