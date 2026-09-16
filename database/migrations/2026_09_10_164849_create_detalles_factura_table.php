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
        Schema::create('detalles_factura', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->unique()->constrained('facturas')->cascadeOnDelete();
            $table->foreignId('tipo_compra_id')->constrained('tipos_compra')->restrictOnDelete();
            $table->foreignId('ccosto_id')->constrained('ccostos')->restrictOnDelete();
            $table->decimal('cantidad', 14, 2);
            $table->string('unidad_medida')->nullable();
            $table->decimal('monto', 14, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_factura');
    }
};
