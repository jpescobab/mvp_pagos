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
        Schema::create('snapshots_datos_externos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sistema_externo_id')->constrained('sistemas_externos')->restrictOnDelete();
            $table->foreignId('trabajo_integracion_id')->nullable()->constrained('trabajos_integracion')->nullOnDelete();
            $table->foreignId('solicitud_api_externa_id')->nullable()->constrained('solicitudes_api_externas')->nullOnDelete();
            $table->string('metodo_captura');
            $table->string('referencia_externa')->nullable();
            $table->json('payload_crudo');
            $table->json('payload_normalizado')->nullable();
            $table->string('hash');
            $table->timestamp('capturado_en')->useCurrent();
            $table->foreignId('capturado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('vinculable_type')->nullable();
            $table->unsignedBigInteger('vinculable_id')->nullable();

            $table->index(['sistema_externo_id', 'referencia_externa']);
            $table->index(['vinculable_type', 'vinculable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('snapshots_datos_externos');
    }
};
