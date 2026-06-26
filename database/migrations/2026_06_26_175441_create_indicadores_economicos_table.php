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
        Schema::create('indicadores_economicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('importacion_id')->constrained('indicadores_economicos_importaciones')->restrictOnDelete();
            $table->string('tipo');
            $table->date('fecha_valor')->nullable();
            $table->string('periodo')->nullable();
            $table->decimal('valor', 15, 4);
            $table->string('periodicidad_valor');
            $table->string('periodicidad_publicacion')->nullable();
            $table->date('vigente_desde')->nullable();
            $table->date('vigente_hasta')->nullable();
            $table->string('fuente');
            $table->string('source_url')->nullable();
            $table->string('source_hash')->nullable();
            $table->json('source_payload')->nullable();
            $table->json('advertencias')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['tipo', 'fecha_valor']);
            $table->unique(['tipo', 'periodo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicadores_economicos');
    }
};
