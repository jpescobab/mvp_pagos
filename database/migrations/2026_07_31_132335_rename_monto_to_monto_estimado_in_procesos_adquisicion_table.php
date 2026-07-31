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
            $table->renameColumn('monto', 'monto_estimado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procesos_adquisicion', function (Blueprint $table) {
            $table->renameColumn('monto_estimado', 'monto');
        });
    }
};
