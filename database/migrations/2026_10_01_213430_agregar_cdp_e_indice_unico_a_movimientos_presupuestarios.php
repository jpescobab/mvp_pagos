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
        Schema::table('movimientos_presupuestarios', function (Blueprint $table) {
            // Vincula la ejecución/liberación de un pago con el CDP contra el
            // que se imputó (el origen del movimiento es el caso de pago).
            // Nombre de FK explícito: el generado excede los 63 caracteres de
            // identificador de PostgreSQL.
            $table->unsignedBigInteger('certificado_disponibilidad_presupuestaria_id')->nullable()->after('presupuesto_id');
            $table->foreign('certificado_disponibilidad_presupuestaria_id', 'mov_presupuestarios_cdp_foreign')
                ->references('id')
                ->on('certificados_disponibilidad_presupuestaria')
                ->nullOnDelete();

            // A lo más un movimiento de cada tipo por origen: garantiza la
            // idempotencia del registro de ejecución bajo concurrencia.
            $table->unique(['tipo', 'origen_type', 'origen_id'], 'mov_presupuestarios_tipo_origen_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos_presupuestarios', function (Blueprint $table) {
            $table->dropUnique('mov_presupuestarios_tipo_origen_unique');
            $table->dropForeign('mov_presupuestarios_cdp_foreign');
            $table->dropColumn('certificado_disponibilidad_presupuestaria_id');
        });
    }
};
