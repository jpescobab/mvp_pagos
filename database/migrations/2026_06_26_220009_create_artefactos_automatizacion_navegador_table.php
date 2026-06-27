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
        Schema::create('artefactos_automatizacion_navegador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ejecucion_automatizacion_navegador_id')->constrained('ejecuciones_automatizacion_navegador')->cascadeOnDelete();
            $table->foreignId('paso_automatizacion_navegador_id')->nullable()->constrained('pasos_automatizacion_navegador')->nullOnDelete();
            $table->string('tipo');
            $table->string('ruta_almacenamiento');
            $table->string('hash');
            $table->timestamp('capturado_en')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artefactos_automatizacion_navegador');
    }
};
