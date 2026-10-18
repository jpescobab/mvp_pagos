<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios_contratados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->restrictOnDelete();
            $table->string('nombre');
            $table->string('categoria');
            $table->decimal('monto_mensual_esperado', 14, 2);
            $table->string('moneda', 3)->default('CLP');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->foreignId('ccosto_id')->nullable()->constrained('ccostos')->nullOnDelete();
            $table->foreignId('administrador_funcionario_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamp('terminado_en')->nullable();
            $table->timestamps();

            $table->index('fecha_fin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios_contratados');
    }
};
