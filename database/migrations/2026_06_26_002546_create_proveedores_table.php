<?php

use App\Models\Proveedor;
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
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();

            // Identificación
            $table->string('rutproveedor')->unique();
            $table->string('nombre');
            $table->string('giro')->nullable();
            $table->string('tipo_contribuyente')->nullable();
            $table->json('rubros')->nullable();

            // Contacto comercial
            $table->string('correo')->nullable();
            $table->string('contacto')->nullable();
            $table->string('contacto_cargo')->nullable();
            $table->string('contacto_telefono')->nullable();

            // Domicilio
            $table->string('direccion')->nullable();
            $table->string('region')->nullable();
            $table->string('comuna')->nullable();

            // Datos bancarios
            $table->string('banco')->nullable();
            $table->string('tipo_cuenta')->nullable();
            $table->string('numero_cuenta')->nullable();
            $table->string('condicion_pago')->default('dias_30');
            $table->string('moneda')->default('clp');
            $table->string('correo_pago')->nullable();
            $table->string('documento_respaldo_path')->nullable();

            $table->string('imagen')->nullable();
            $table->text('notas_internas')->nullable();

            // borrador | activo | inactivo. Solo los activos se ofrecen donde se
            // elige un proveedor para operar; el catálogo muestra los tres. El
            // default es `activo` para que un proveedor creado por fuera del
            // formulario (importación SGF, Mercado Público) nazca operable.
            $table->string('estado')->default(Proveedor::ESTADO_ACTIVO)->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
