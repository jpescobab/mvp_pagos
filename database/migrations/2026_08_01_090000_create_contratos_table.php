<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('modalidad_compra');
            $table->string('id_proceso_mp')->nullable();
            $table->string('tipo_contrato');
            $table->text('referencia');
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia');
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->string('materia')->nullable();
            $table->string('submateria')->nullable();
            $table->boolean('tiene_convenio_precio')->default(false);
            $table->boolean('tiene_calendario_pago')->default(false);
            $table->string('periodicidad_pago')->nullable();
            $table->decimal('monto_total', 14, 2)->nullable();
            $table->foreignId('proceso_adquisicion_id')->nullable()->constrained('procesos_adquisicion')->nullOnDelete();
            $table->foreignId('licitacion_mercado_publico_id')->nullable()->constrained('licitaciones_mercado_publico')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
