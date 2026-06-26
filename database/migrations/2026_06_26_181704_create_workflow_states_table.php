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
        Schema::create('workflow_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions')->restrictOnDelete();
            $table->string('codigo');
            $table->string('nombre');
            $table->boolean('es_inicial')->default(false);
            $table->boolean('es_final')->default(false);
            $table->timestamps();

            $table->unique(['workflow_definition_id', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_states');
    }
};
