<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrato_cuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();
            $table->unsignedInteger('numero_cuota');
            $table->date('fecha_vencimiento');
            $table->decimal('monto', 14, 2);
            $table->string('moneda')->default('CLP');
            $table->string('estado')->default('pendiente');
            $table->foreignId('caso_pago_proveedor_id')->nullable()->constrained('casos_pago_proveedor')->nullOnDelete();
            $table->timestamps();

            $table->unique(['contrato_id', 'numero_cuota']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_cuotas');
    }
};
