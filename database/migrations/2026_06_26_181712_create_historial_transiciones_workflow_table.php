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
        Schema::create('historial_transiciones_workflow', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proceso_id')->constrained('procesos')->restrictOnDelete();
            $table->foreignId('transicion_workflow_id')->nullable()->constrained('transiciones_workflow')->nullOnDelete();
            $table->foreignId('estado_origen_id')->constrained('estados_workflow')->restrictOnDelete();
            $table->foreignId('estado_destino_id')->constrained('estados_workflow')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comentario')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_transiciones_workflow');
    }
};
