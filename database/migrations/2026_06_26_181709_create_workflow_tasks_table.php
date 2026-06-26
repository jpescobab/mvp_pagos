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
        Schema::create('workflow_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained('processes')->restrictOnDelete();
            $table->foreignId('workflow_transition_id')->nullable()->constrained('workflow_transitions')->nullOnDelete();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('estado')->default('pendiente');
            $table->date('vence_en')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_tasks');
    }
};
