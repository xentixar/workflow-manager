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
        'from_state',
        'to_state',
    ];

    /**
     * Get the workflow that owns the transition.
     */
    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
}