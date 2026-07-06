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
        Schema::create('ordenes_compra_mercado_publico', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('proceso_adquisicion_id')->nullable()->constrained('procesos_adquisicion')->nullOnDelete();
            $table->foreignId('snapshot_datos_externo_id')->nullable()->constrained('snapshots_datos_externos')->nullOnDelete();
            $table->string('estado_mercado_publico')->nullable();
            $table->string('moneda')->nullable();
            $table->string('forma_pago')->nullable();
            $table->unsignedInteger('plazo_entrega_dias')->nullable();
            $table->decimal('monto_neto', 14, 2)->nullable();
            $table->decimal('monto_total', 14, 2)->nullable();
            $table->date('fecha_emision')->nullable();
            $table->json('organismo_comprador')->nullable();
            $table->json('cronograma')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra_mercado_publico');
    }
};
