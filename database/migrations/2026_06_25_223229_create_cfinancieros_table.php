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
        Schema::create('cfinancieros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurisdiccion_id')->constrained('jurisdicciones')->restrictOnDelete();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfinancieros');
    }
};
