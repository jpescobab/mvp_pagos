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
        Schema::create('procesos_adquisicion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->foreignId('modalidad_id')->constrained('modalidades_adquisicion');
            $table->foreignId('ccosto_id')->constrained('ccostos');
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->decimal('monto', 14, 2)->nullable();
            $table->text('objeto');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procesos_adquisicion');
    }
};
