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
        Schema::create('checklist_documental_proceso_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_documental_proceso_id')->constrained('checklists_documentales_proceso')->cascadeOnDelete();
            $table->foreignId('requisito_documental_id')->constrained('requisitos_documentales')->restrictOnDelete();
            $table->foreignId('tipo_documento_id')->constrained('tipos_documento')->restrictOnDelete();
            $table->string('tipo_requisito');
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->nullOnDelete();
            $table->string('estado_cumplimiento')->default('pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_documental_proceso_items');
    }
};
