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
        Schema::create('periodos_reportabilidad', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('estado')->default('abierto');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodos_reportabilidad');
    }
};
