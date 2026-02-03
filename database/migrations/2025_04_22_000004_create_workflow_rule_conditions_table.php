<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Conditions belong to a transition; evaluated when that transition is taken.
     */
    public function up(): void
    {
        Schema::create('workflow_rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_transition_id')->constrained('workflow_transitions')->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
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
