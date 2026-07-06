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
        Schema::create('licitacion_mercado_publico_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licitacion_mercado_publico_id')->constrained('licitaciones_mercado_publico')->cascadeOnDelete();
            $table->unsignedInteger('correlativo')->nullable();
            $table->string('codigo_producto')->nullable();
            $table->string('categoria')->nullable();
            $table->string('nombre_producto')->nullable();
            $table->text('descripcion');
            $table->string('unidad_medida')->nullable();
            $table->decimal('cantidad', 14, 2);
            $table->json('adjudicacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licitacion_mercado_publico_items');
    }
};
