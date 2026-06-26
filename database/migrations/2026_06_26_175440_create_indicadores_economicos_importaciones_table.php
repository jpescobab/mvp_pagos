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
        Schema::create('indicadores_economicos_importaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo');
            $table->string('estado')->default('ok');
            $table->string('endpoint')->nullable();
            $table->json('source_payload')->nullable();
            $table->json('errores')->nullable();
            $table->json('advertencias')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicadores_economicos_importaciones');
    }
};
