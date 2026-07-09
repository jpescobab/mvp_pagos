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
        Schema::table('casos_pago_proveedor', function (Blueprint $table) {
            $table->string('periodo')->nullable()->after('sgf_current_group_raw');
            $table->text('observacion')->nullable()->after('periodo');
            $table->string('folio_egreso')->nullable()->after('observacion');
            $table->string('numero')->nullable()->after('folio_egreso');
            $table->date('fecha_sii')->nullable()->after('numero');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('casos_pago_proveedor', function (Blueprint $table) {
            $table->dropColumn(['periodo', 'observacion', 'folio_egreso', 'numero', 'fecha_sii']);
        });
    }
};
