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
            $table->string('sgf_numero_traspaso')->nullable()->after('numero');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('casos_pago_proveedor', function (Blueprint $table) {
            $table->dropColumn('sgf_numero_traspaso');
        });
    }
};
