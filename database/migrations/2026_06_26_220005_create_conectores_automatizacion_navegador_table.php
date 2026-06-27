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
        Schema::create('conectores_automatizacion_navegador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sistema_externo_id')->constrained('sistemas_externos')->restrictOnDelete();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->boolean('activo')->default(false);
            $table->foreignId('autorizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('autorizado_en')->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conectores_automatizacion_navegador');
    }
};
