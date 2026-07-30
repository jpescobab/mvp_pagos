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
        Schema::create('importaciones_presupuesto', function (Blueprint $table) {
            $table->id();
            $table->string('nro_version');
            $table->unsignedSmallInteger('anio');
            $table->string('estado')->default('pending');
            $table->unsignedInteger('total_recibidos')->default(0);
            $table->unsignedInteger('total_creados')->default(0);
            $table->unsignedInteger('total_actualizados')->default(0);
            $table->unsignedInteger('total_omitidos')->default(0);
            $table->unsignedInteger('total_fallidos')->default(0);
            $table->json('errores')->nullable();
            $table->json('advertencias')->nullable();
            $table->timestamp('iniciado_en')->nullable();
            $table->timestamp('finalizado_en')->nullable();
            $table->foreignId('creado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('snapshot_datos_externo_id')->nullable()->constrained('snapshots_datos_externos')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('importaciones_presupuesto');
    }
};
