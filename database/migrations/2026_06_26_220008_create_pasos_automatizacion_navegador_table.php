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
        Schema::create('pasos_automatizacion_navegador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ejecucion_automatizacion_navegador_id')->constrained('ejecuciones_automatizacion_navegador')->cascadeOnDelete();
            $table->unsignedInteger('orden');
            $table->string('accion');
            $table->json('detalle')->nullable();
            $table->string('estado');
            $table->text('error')->nullable();
            $table->timestamp('ejecutado_en')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pasos_automatizacion_navegador');
    }
};
