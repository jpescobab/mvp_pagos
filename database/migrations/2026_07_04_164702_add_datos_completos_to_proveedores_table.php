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
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('giro')->nullable()->after('nombre');
            $table->string('tipo_contribuyente')->nullable()->after('giro');
            $table->json('rubros')->nullable()->after('tipo_contribuyente');
            $table->string('contacto_cargo')->nullable()->after('contacto');
            $table->string('contacto_telefono')->nullable()->after('contacto_cargo');
            $table->string('region')->nullable()->after('direccion');
            $table->string('comuna')->nullable()->after('region');
            $table->string('banco')->nullable()->after('comuna');
            $table->string('tipo_cuenta')->nullable()->after('banco');
            $table->string('numero_cuenta')->nullable()->after('tipo_cuenta');
            $table->string('condicion_pago')->default('dias_30')->after('numero_cuenta');
            $table->string('moneda')->default('clp')->after('condicion_pago');
            $table->string('correo_pago')->nullable()->after('moneda');
            $table->string('documento_respaldo_path')->nullable()->after('correo_pago');
            $table->text('notas_internas')->nullable()->after('documento_respaldo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn([
                'giro',
                'tipo_contribuyente',
                'rubros',
                'contacto_cargo',
                'contacto_telefono',
                'region',
                'comuna',
                'banco',
                'tipo_cuenta',
                'numero_cuenta',
                'condicion_pago',
                'moneda',
                'correo_pago',
                'documento_respaldo_path',
                'notas_internas',
            ]);
        });
    }
};
