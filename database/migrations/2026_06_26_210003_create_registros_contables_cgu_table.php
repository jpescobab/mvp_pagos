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
        Schema::create('registros_contables_cgu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caso_pago_proveedor_id')->constrained('casos_pago_proveedor')->cascadeOnDelete();
            $table->string('numero_registro');
            $table->date('fecha_registro');
            $table->decimal('monto', 14, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_contables_cgu');
    }
};
