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
        Schema::create('ejecuciones_informe_razonado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('definicion_informe_razonado_id')->constrained('definiciones_informe_razonado')->restrictOnDelete();
            $table->foreignId('corte_reportabilidad_id')->constrained('cortes_reportabilidad')->restrictOnDelete();
            $table->foreignId('generado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generado_en')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ejecuciones_informe_razonado');
    }
};
