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
        Schema::create('narrativas_informe_razonado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ejecucion_informe_razonado_id')->constrained('ejecuciones_informe_razonado')->cascadeOnDelete();
            $table->foreignId('seccion_informe_razonado_id')->nullable()->constrained('secciones_informe_razonado')->cascadeOnDelete();
            $table->text('contenido');
            $table->boolean('generado_por_ia')->default(false);
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_en')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('narrativas_informe_razonado');
    }
};
