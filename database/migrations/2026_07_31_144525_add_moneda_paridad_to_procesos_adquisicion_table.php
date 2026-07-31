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
        Schema::table('procesos_adquisicion', function (Blueprint $table) {
            $table->string('moneda_compra')->default('CLP')->after('monto_estimado');
            $table->decimal('monto_estimado_solicitado', 14, 4)->nullable()->after('moneda_compra');
            $table->date('fecha_paridad')->nullable()->after('monto_estimado_solicitado');
            $table->decimal('paridad', 14, 4)->nullable()->after('fecha_paridad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procesos_adquisicion', function (Blueprint $table) {
            $table->dropColumn(['moneda_compra', 'monto_estimado_solicitado', 'fecha_paridad', 'paridad']);
        });
    }
};
