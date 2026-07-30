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
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfinanciero_id')->constrained('cfinancieros')->restrictOnDelete();
            $table->foreignId('catalogo_id')->constrained('catalogos')->restrictOnDelete();
            $table->foreignId('plan_tarea_id')->constrained('planes_tarea')->restrictOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->decimal('monto_asignado', 14, 2);
            $table->foreignId('importacion_presupuesto_id')->nullable()->constrained('importaciones_presupuesto')->nullOnDelete();
            $table->timestamps();
            $table->unique(['cfinanciero_id', 'catalogo_id', 'plan_tarea_id', 'anio'], 'presupuestos_identidad_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presupuestos');
    }
};
