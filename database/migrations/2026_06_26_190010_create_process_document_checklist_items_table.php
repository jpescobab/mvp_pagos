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
        Schema::create('process_document_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_document_checklist_id')->constrained('process_document_checklists')->cascadeOnDelete();
            $table->foreignId('document_requirement_id')->constrained('document_requirements')->restrictOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->restrictOnDelete();
            $table->string('tipo_requisito');
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->string('estado_cumplimiento')->default('pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_document_checklist_items');
    }
};
