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
        Schema::create('perfiles_autenticacion_navegador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conector_automatizacion_navegador_id')->constrained('conectores_automatizacion_navegador')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('almacen_secreto');
            $table->string('referencia_secreto');
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perfiles_autenticacion_navegador');
    }
};
