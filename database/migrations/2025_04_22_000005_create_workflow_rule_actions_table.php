<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workflow_rule_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_rule_id')->constrained('workflow_rules')->cascadeOnDelete();
            $table->string('from_state');
            $table->string('to_state');
            $table->string('assign_role')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_rule_actions');
    }
};
