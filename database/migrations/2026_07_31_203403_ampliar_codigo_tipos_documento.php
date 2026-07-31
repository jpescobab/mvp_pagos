<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `codigo` nació en varchar(30), pero `INFORME_JUSTIFICACION_TRATO_DIRECTO`
 * (36 caracteres) ya no entra. SQLite no aplica el largo de VARCHAR (los
 * tests, que corren en sqlite, nunca vieron este error), así que el ALTER
 * real solo hace falta en PostgreSQL.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tipos_documento ALTER COLUMN codigo TYPE VARCHAR(60)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tipos_documento ALTER COLUMN codigo TYPE VARCHAR(30)');
        }
    }
};
