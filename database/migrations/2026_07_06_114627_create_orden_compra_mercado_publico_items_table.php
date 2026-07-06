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
        Schema::create('orden_compra_mercado_publico_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_mercado_publico_id')->constrained('ordenes_compra_mercado_publico')->cascadeOnDelete();
            $table->string('codigo_producto')->nullable();
            $table->string('descripcion');
            $table->decimal('cantidad', 14, 2);
            $table->decimal('precio_unitario', 14, 2);
            $table->decimal('monto_total', 14, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_compra_mercado_publico_items');
    }
};
