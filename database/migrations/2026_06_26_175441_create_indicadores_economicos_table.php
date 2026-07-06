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
            $table->string('codigo');
            $table->string('nombre');
            $table->string('tipo');
            $table->date('fecha_valor')->nullable();
            $table->string('periodo')->nullable();
            $table->decimal('valor', 15, 4);
            $table->string('periodicidad_valor');
            $table->string('periodicidad_publicacion')->nullable();
            $table->date('vigente_desde')->nullable();
            $table->date('vigente_hasta')->nullable();
            $table->string('unidad_medida');
            $table->string('moneda_base');
            $table->string('fuente');
            $table->string('endpoint')->nullable();
            $table->string('source_url')->nullable();
            $table->string('source_hash')->nullable();
            $table->json('source_payload')->nullable();
            $table->timestamp('capturado_en')->nullable();
            $table->foreignId('capturado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('capturado_por_job')->nullable();
            $table->boolean('requiere_dia_habil')->default(false);
            $table->boolean('es_proyectado')->default(false);
            $table->boolean('es_oficial')->default(true);
            $table->boolean('activo')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Dos índices en vez de uno solo de 5 columnas: en SQL un NULL
            // nunca colisiona con otro NULL dentro de un índice único, y
            // fecha_valor/periodo son mutuamente excluyentes (uno de los dos
            // siempre es NULL) — un único índice de 5 columnas no
            // protegería nada, porque toda fila tendría al menos un NULL.
            $table->unique(['codigo', 'fecha_valor', 'fuente', 'es_proyectado']);
            $table->unique(['codigo', 'periodo', 'fuente', 'es_proyectado']);
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
