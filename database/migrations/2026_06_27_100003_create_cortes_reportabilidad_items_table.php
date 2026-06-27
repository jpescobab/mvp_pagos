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
        Schema::create('cortes_reportabilidad_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corte_reportabilidad_id')->constrained('cortes_reportabilidad')->cascadeOnDelete();
            $table->string('vinculable_type');
            $table->unsignedBigInteger('vinculable_id');
            $table->string('etiqueta');
            $table->timestamp('incluido_en')->useCurrent();

            $table->index(['vinculable_type', 'vinculable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cortes_reportabilidad_items');
    }
};
