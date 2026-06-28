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
            $table->foreignId('proceso_adquisicion_id')->nullable()->after('sgf_id')->constrained('procesos_adquisicion')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('casos_pago_proveedor', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proceso_adquisicion_id');
        });
    }
};
