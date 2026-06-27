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
        Schema::create('excepciones_informe_razonado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ejecucion_informe_razonado_id')->constrained('ejecuciones_informe_razonado')->cascadeOnDelete();
            $table->string('codigo');
            $table->text('descripcion');
            $table->string('severidad')->default('info');
            $table->string('vinculable_type')->nullable();
            $table->unsignedBigInteger('vinculable_id')->nullable();
            $table->timestamps();

            $table->index(['vinculable_type', 'vinculable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('excepciones_informe_razonado');
    }
};
