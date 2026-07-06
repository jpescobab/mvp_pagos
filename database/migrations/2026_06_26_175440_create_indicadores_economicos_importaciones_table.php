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
        Schema::create('indicadores_economicos_importaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_importacion');
            $table->string('estado')->default('pending');
            $table->json('indicadores_solicitados')->nullable();
            $table->string('fuente_principal')->nullable();
            $table->string('fuente_fallback')->nullable();
            $table->date('fecha_programada')->nullable();
            $table->string('periodo')->nullable();
            $table->date('fecha_desde')->nullable();
            $table->date('fecha_hasta')->nullable();
            $table->timestamp('iniciado_en')->nullable();
            $table->timestamp('finalizado_en')->nullable();
            $table->foreignId('creado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ejecutado_por_job')->nullable();
            $table->unsignedInteger('total_recibidos')->default(0);
            $table->unsignedInteger('total_creados')->default(0);
            $table->unsignedInteger('total_omitidos')->default(0);
            $table->unsignedInteger('total_fallidos')->default(0);
            $table->json('errores')->nullable();
            $table->json('advertencias')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicadores_economicos_importaciones');
    }
};
