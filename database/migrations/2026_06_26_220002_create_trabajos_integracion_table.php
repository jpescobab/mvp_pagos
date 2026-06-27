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
        Schema::create('trabajos_integracion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sistema_externo_id')->constrained('sistemas_externos')->restrictOnDelete();
            $table->string('tipo');
            $table->string('mecanismo');
            $table->string('estado')->default('en_progreso');
            $table->foreignId('iniciado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('iniciado_en')->useCurrent();
            $table->timestamp('finalizado_en')->nullable();
            $table->unsignedInteger('total_elementos')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabajos_integracion');
    }
};
