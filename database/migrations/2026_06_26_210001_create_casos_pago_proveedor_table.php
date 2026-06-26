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
        Schema::create('casos_pago_proveedor', function (Blueprint $table) {
            $table->id();
            $table->string('sgf_id')->unique();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->string('rut_proveedor')->nullable();
            $table->decimal('monto', 14, 2)->nullable();
            $table->string('sgf_status')->nullable();
            $table->string('sgf_current_group_raw')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('casos_pago_proveedor');
    }
};
