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
        Schema::create('requisitos_documentales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_requisitos_documentales_id')->constrained('conjuntos_requisitos_documentales')->cascadeOnDelete();
            $table->foreignId('tipo_documento_id')->constrained('tipos_documento')->restrictOnDelete();
            $table->foreignId('definicion_workflow_id')->constrained('definiciones_workflow')->restrictOnDelete();
            $table->foreignId('modalidad_id')->nullable()->constrained('modalidades_adquisicion')->restrictOnDelete();
            $table->foreignId('estado_workflow_id')->nullable()->constrained('estados_workflow')->restrictOnDelete();
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
        Schema::dropIfExists('requisitos_documentales');
    }
};
