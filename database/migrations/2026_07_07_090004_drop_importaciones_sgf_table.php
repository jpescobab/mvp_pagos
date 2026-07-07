<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Reemplazada por `trabajos_integracion` (capa transversal de integraciones).
     */
    public function up(): void
    {
        Schema::dropIfExists('importaciones_sgf');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('importaciones_sgf', function (Blueprint $table) {
            $table->id();
            $table->string('fuente');
            $table->foreignId('iniciado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('iniciado_en')->useCurrent();
            $table->timestamp('finalizado_en')->nullable();
            $table->unsignedInteger('total_filas')->default(0);
            $table->string('estado')->default('en_progreso');
            $table->timestamps();
        });
    }
};
