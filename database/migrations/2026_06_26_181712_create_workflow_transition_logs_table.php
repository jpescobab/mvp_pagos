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
        Schema::create('workflow_transition_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained('processes')->restrictOnDelete();
            $table->foreignId('workflow_transition_id')->nullable()->constrained('workflow_transitions')->nullOnDelete();
            $table->foreignId('from_state_id')->constrained('workflow_states')->restrictOnDelete();
            $table->foreignId('to_state_id')->constrained('workflow_states')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comentario')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_transition_logs');
    }
};
