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
        Schema::create('egresos_cgu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('egreso_cgu_id')->constrained('egresos_cgu')->cascadeOnDelete();
            $table->foreignId('caso_pago_proveedor_id')->constrained('casos_pago_proveedor')->restrictOnDelete();
            $table->decimal('monto', 14, 2)->nullable();
            $table->timestamps();

            $table->unique(['egreso_cgu_id', 'caso_pago_proveedor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('egresos_cgu_items');
    }
};
