<?php

namespace Xentixar\WorkflowManager\Models;

use Illuminate\Database\Eloquent\Model;

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
        'role'
    ];
}
