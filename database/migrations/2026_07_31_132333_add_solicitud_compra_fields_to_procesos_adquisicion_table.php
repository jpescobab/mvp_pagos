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
            $table->date('fecha_inicio')->nullable()->after('codigo');
            $table->string('nombre')->nullable()->after('fecha_inicio');
            $table->string('id_requerimiento')->nullable()->after('nombre');
            $table->foreignId('funcionario_requirente_id')->nullable()->after('ccosto_id')->constrained('funcionarios')->nullOnDelete();
            $table->text('caracteristicas')->nullable()->after('objeto');
            $table->text('motivo_contratacion')->nullable()->after('caracteristicas');
            $table->boolean('en_plan_compras')->nullable()->after('motivo_contratacion');
            $table->string('id_pac')->nullable()->after('en_plan_compras');
            $table->string('codigo_bip')->nullable()->after('id_pac');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procesos_adquisicion', function (Blueprint $table) {
            $table->dropConstrainedForeignId('funcionario_requirente_id');
            $table->dropColumn([
                'fecha_inicio',
                'nombre',
                'id_requerimiento',
                'caracteristicas',
                'motivo_contratacion',
                'en_plan_compras',
                'id_pac',
                'codigo_bip',
            ]);
        });
    }
};
