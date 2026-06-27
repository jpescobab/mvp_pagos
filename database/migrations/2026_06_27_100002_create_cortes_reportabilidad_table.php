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
        Schema::create('cortes_reportabilidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_reportabilidad_id')->constrained('periodos_reportabilidad')->restrictOnDelete();
            $table->timestamp('fecha_corte')->useCurrent();
            $table->string('estado')->default('borrador');
            $table->foreignId('publicado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('publicado_en')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cortes_reportabilidad');
    }
};
