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
        Schema::create('ejecuciones_automatizacion_navegador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conector_automatizacion_navegador_id')->constrained('conectores_automatizacion_navegador')->restrictOnDelete();
            $table->foreignId('perfil_autenticacion_navegador_id')->nullable()->constrained('perfiles_autenticacion_navegador')->nullOnDelete();
            $table->foreignId('trabajo_integracion_id')->nullable()->constrained('trabajos_integracion')->nullOnDelete();
            $table->foreignId('iniciado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('estado')->default('en_progreso');
            $table->timestamp('iniciado_en')->useCurrent();
            $table->timestamp('finalizado_en')->nullable();
            $table->text('resumen_resultado')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('conector_automatizacion_navegador_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ejecuciones_automatizacion_navegador');
    }
};
