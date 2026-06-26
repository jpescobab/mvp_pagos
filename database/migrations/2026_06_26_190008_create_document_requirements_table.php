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
        Schema::create('document_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_requirement_set_id')->constrained('document_requirement_sets')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->restrictOnDelete();
            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions')->restrictOnDelete();
            $table->foreignId('modalidad_id')->nullable()->constrained('procurement_modalities')->restrictOnDelete();
            $table->foreignId('workflow_state_id')->nullable()->constrained('workflow_states')->restrictOnDelete();
            $table->decimal('monto_desde', 14, 2)->nullable();
            $table->decimal('monto_hasta', 14, 2)->nullable();
            $table->string('tipo_requisito');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_requirements');
    }
};
