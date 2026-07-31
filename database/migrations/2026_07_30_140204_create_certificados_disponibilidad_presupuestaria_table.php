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
        Schema::create('certificados_disponibilidad_presupuestaria', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->foreignId('presupuesto_id')->constrained('presupuestos')->restrictOnDelete();
            $table->foreignId('cfinanciero_id')->constrained('cfinancieros')->restrictOnDelete();
            $table->string('denominacion');
            $table->string('unidad_ejecutora');
            $table->string('n_ue');
            $table->string('subtitulo');
            $table->string('tipo_gasto');
            $table->string('codigo_iniciativa')->nullable();
            $table->text('nombre');
            $table->string('nombre_iniciativa')->nullable();
            $table->string('programa_presupuestario')->default('100');
            $table->string('caracter_gasto');
            $table->string('medio_solicitud')->nullable();
            $table->date('fecha_solicitud')->nullable();
            $table->string('moneda_compra')->default('CLP');
            $table->decimal('total_moneda_compra', 14, 4);
            $table->date('fecha_paridad')->nullable();
            $table->decimal('paridad', 14, 4)->nullable();
            $table->decimal('monto', 14, 2);
            $table->unsignedSmallInteger('anio_validez');
            $table->string('requerimiento_numero')->nullable();
            $table->string('mercado_publico_tipo')->nullable();
            $table->unsignedBigInteger('mercado_publico_id')->nullable();
            $table->foreignId('proceso_adquisicion_id')->nullable()->constrained('procesos_adquisicion')->nullOnDelete();
            $table->decimal('saldo_disponible_al_emitir', 14, 2)->nullable();
            $table->boolean('hubo_sobregiro_al_emitir')->default(false);
            $table->foreignId('cdp_original_id')->nullable()->constrained('certificados_disponibilidad_presupuestaria')->nullOnDelete();
            $table->foreignId('firmado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('firmado_en')->nullable();
            $table->timestamps();

            $table->index(['mercado_publico_tipo', 'mercado_publico_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificados_disponibilidad_presupuestaria');
    }
};
