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
        Schema::create('registros_pago_bancario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caso_pago_proveedor_id')->constrained('casos_pago_proveedor')->cascadeOnDelete();
            $table->string('numero_operacion');
            $table->date('fecha_pago');
            $table->decimal('monto', 14, 2)->nullable();
            $table->string('banco')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_pago_bancario');
    }
};
