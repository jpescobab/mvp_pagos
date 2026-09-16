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
        Schema::create('consumos_basicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_medidor_id')->constrained('clientes_medidores')->restrictOnDelete();
            $table->foreignId('caso_pago_proveedor_id')->unique()->constrained('casos_pago_proveedor')->cascadeOnDelete();
            $table->string('numero_documento');
            $table->string('numero_medidor');
            $table->date('fecha_inicio_lectura');
            $table->date('fecha_fin_lectura');
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->decimal('consumo', 12, 2)->nullable();
            $table->string('tarifa')->nullable();
            $table->boolean('lectura_estimada')->default(false);
            $table->decimal('monto_neto', 14, 2);
            $table->decimal('iva', 14, 2);
            $table->decimal('monto_exento', 14, 2)->default(0);
            $table->decimal('saldo_anterior', 14, 2)->default(0);
            $table->decimal('monto_total', 14, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumos_basicos');
    }
};
