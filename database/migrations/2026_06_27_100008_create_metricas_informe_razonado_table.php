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
        Schema::create('metricas_informe_razonado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ejecucion_informe_razonado_id')->constrained('ejecuciones_informe_razonado')->cascadeOnDelete();
            $table->foreignId('seccion_informe_razonado_id')->nullable()->constrained('secciones_informe_razonado')->cascadeOnDelete();
            $table->string('codigo');
            $table->string('etiqueta');
            $table->decimal('valor', 15, 4)->nullable();
            $table->string('unidad')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metricas_informe_razonado');
    }
};
