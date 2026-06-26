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
        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions')->restrictOnDelete();
            $table->foreignId('from_state_id')->constrained('workflow_states')->restrictOnDelete();
            $table->foreignId('to_state_id')->constrained('workflow_states')->restrictOnDelete();
            $table->string('codigo');
            $table->string('nombre');
            $table->string('permiso_requerido')->nullable();
            $table->json('documentos_requeridos')->nullable();
            $table->boolean('requiere_comentario')->default(false);
            $table->timestamps();

            $table->unique(['workflow_definition_id', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_transitions');
    }
};
