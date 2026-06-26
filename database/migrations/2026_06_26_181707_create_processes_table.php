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
        Schema::create('processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions')->restrictOnDelete();
            $table->foreignId('current_state_id')->constrained('workflow_states')->restrictOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            // Sin FK: procurement_modalities se crea en una migración posterior.
            $table->unsignedBigInteger('modalidad_id')->nullable();
            $table->decimal('monto', 14, 2)->nullable();
            $table->foreignId('iniciado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cerrado_en')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processes');
    }
};
