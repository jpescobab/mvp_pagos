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
        Schema::create('ccostos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfinanciero_id')->constrained('cfinancieros')->restrictOnDelete();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->string('cod_edificio')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ccostos');
    }
};
