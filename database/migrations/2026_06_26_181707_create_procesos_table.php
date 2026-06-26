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
        Schema::create('procesos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('definicion_workflow_id')->constrained('definiciones_workflow')->restrictOnDelete();
            $table->foreignId('estado_actual_id')->constrained('estados_workflow')->restrictOnDelete();
            $table->string('sujeto_type');
            $table->unsignedBigInteger('sujeto_id');
            // Sin FK: modalidades_adquisicion se crea en una migración posterior.
            $table->unsignedBigInteger('modalidad_id')->nullable();
            $table->decimal('monto', 14, 2)->nullable();
            $table->foreignId('iniciado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cerrado_en')->nullable();
            $table->timestamps();

            $table->index(['sujeto_type', 'sujeto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procesos');
    }
};
