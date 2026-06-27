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
        Schema::create('solicitudes_api_externas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sistema_externo_id')->constrained('sistemas_externos')->restrictOnDelete();
            $table->foreignId('trabajo_integracion_id')->nullable()->constrained('trabajos_integracion')->nullOnDelete();
            $table->string('metodo_http');
            $table->string('endpoint');
            $table->json('payload_enviado')->nullable();
            $table->json('payload_recibido')->nullable();
            $table->unsignedSmallInteger('codigo_respuesta_http')->nullable();
            $table->string('estado');
            $table->text('error')->nullable();
            $table->unsignedInteger('duracion_ms')->nullable();
            $table->timestamp('ejecutado_en')->useCurrent();

            $table->index('sistema_externo_id');
            $table->index('trabajo_integracion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_api_externas');
    }
};
