<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Conditions can belong to a transition (workflow_transition_id) or to a global rule (workflow_rule_id).
     */
    public function up(): void
    {
        Schema::create('workflow_rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_rule_id')->nullable()->constrained('workflow_rules')->cascadeOnDelete();
            $table->foreignId('workflow_transition_id')->nullable()->constrained('workflow_transitions')->cascadeOnDelete();
            $table->string('field');
            $table->string('operator');
            $table->string('value');
            $table->string('value_type')->default('static');
            $table->string('logical_group')->default('AND');
            $table->string('base_field')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_rule_conditions');
    }
};
