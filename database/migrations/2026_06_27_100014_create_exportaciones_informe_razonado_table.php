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
        Schema::create('exportaciones_informe_razonado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ejecucion_informe_razonado_id')->constrained('ejecuciones_informe_razonado')->cascadeOnDelete();
            $table->string('formato');
            $table->string('ruta_archivo');
            $table->foreignId('generado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generado_en')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exportaciones_informe_razonado');
    }
};
