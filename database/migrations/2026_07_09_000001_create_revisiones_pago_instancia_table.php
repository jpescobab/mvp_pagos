<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estado de revisión de un caso de pago dentro de una instancia concreta
     * (finanzas | zonal). Guarda la verificación de totales exigida antes de
     * aprobar el pago en esa instancia. La aprobación/rechazo del pago sigue
     * gobernada por el workflow (TransicionWorkflowService); esta tabla solo
     * persiste el gate de totales por instancia.
     */
    public function up(): void
    {
        Schema::create('revisiones_pago_instancia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caso_pago_proveedor_id')->constrained('casos_pago_proveedor')->cascadeOnDelete();
            $table->string('instancia');
            $table->boolean('totales_verificados')->default(false);
            $table->foreignId('verificado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verificado_en')->nullable();
            $table->timestamps();

            $table->unique(['caso_pago_proveedor_id', 'instancia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisiones_pago_instancia');
    }
};
