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
        Schema::create('transiciones_workflow', function (Blueprint $table) {
            $table->id();
            $table->foreignId('definicion_workflow_id')->constrained('definiciones_workflow')->restrictOnDelete();
            $table->foreignId('estado_origen_id')->constrained('estados_workflow')->restrictOnDelete();
            $table->foreignId('estado_destino_id')->constrained('estados_workflow')->restrictOnDelete();
            $table->string('codigo');
            $table->string('nombre');
            $table->string('permiso_requerido')->nullable();
            $table->json('documentos_requeridos')->nullable();
            $table->boolean('requiere_comentario')->default(false);
            $table->timestamps();

            $table->unique(['definicion_workflow_id', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transiciones_workflow');
    }
};
