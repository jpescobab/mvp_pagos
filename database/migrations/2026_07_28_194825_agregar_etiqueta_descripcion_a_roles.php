<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function tablaRoles(): string
    {
        return config('permission.table_names.roles', 'roles');
    }

    public function up(): void
    {
        Schema::table($this->tablaRoles(), function (Blueprint $table): void {
            $table->string('etiqueta')->nullable()->after('name');
            $table->string('descripcion', 500)->nullable()->after('etiqueta');
        });
    }

    public function down(): void
    {
        Schema::table($this->tablaRoles(), function (Blueprint $table): void {
            $table->dropColumn(['etiqueta', 'descripcion']);
        });
    }
};
