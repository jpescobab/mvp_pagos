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
        Schema::create('checklists_documentales_proceso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proceso_id')->unique()->constrained('procesos')->cascadeOnDelete();
            $table->foreignId('conjunto_requisitos_documentales_id')->constrained('conjuntos_requisitos_documentales')->restrictOnDelete();
            $table->timestamp('generado_en')->useCurrent();
            $table->foreignId('generado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklists_documentales_proceso');
    }
};
