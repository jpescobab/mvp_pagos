<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_compra_mercado_publico', function (Blueprint $table) {
            $table->foreignId('contrato_id')->nullable()->after('proceso_adquisicion_id')->constrained('contratos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_compra_mercado_publico', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contrato_id');
        });
    }
};
