<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tipos_proceso_pago', function (Blueprint $table) {
            $table->boolean('requiere_traspaso_cgu')->default(true)->after('activo');
        });

        DB::table('tipos_proceso_pago')->where('codigo', 'REMESA')->update(['requiere_traspaso_cgu' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tipos_proceso_pago', function (Blueprint $table) {
            $table->dropColumn('requiere_traspaso_cgu');
        });
    }
};
