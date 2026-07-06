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
        Schema::create('licitaciones_mercado_publico', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre')->nullable();
            $table->foreignId('proceso_adquisicion_id')->nullable()->constrained('procesos_adquisicion')->nullOnDelete();
            $table->foreignId('snapshot_datos_externo_id')->nullable()->constrained('snapshots_datos_externos')->nullOnDelete();
            $table->string('estado_mercado_publico')->nullable();
            $table->unsignedInteger('codigo_estado_mercado_publico')->nullable();
            $table->string('moneda')->nullable();
            $table->decimal('monto_estimado', 15, 2)->nullable();
            $table->json('organismo_comprador')->nullable();
            $table->json('cronograma')->nullable();
            $table->json('adjudicacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licitaciones_mercado_publico');
    }
};
