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
        Schema::create('aprobaciones_informe_razonado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ejecucion_informe_razonado_id')->constrained('ejecuciones_informe_razonado')->cascadeOnDelete();
            $table->foreignId('aprobado_por')->constrained('users')->restrictOnDelete();
            $table->string('decision');
            $table->text('comentario')->nullable();
            $table->timestamp('decidido_en')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aprobaciones_informe_razonado');
    }
};
