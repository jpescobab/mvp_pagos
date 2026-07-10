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
        Schema::create('egresos_cgu', function (Blueprint $table) {
            $table->id();
            $table->string('numero_egreso')->unique();
            $table->date('fecha');
            $table->decimal('monto_total', 14, 2)->nullable();
            $table->string('periodo')->nullable();
            $table->foreignId('cfinanciero_id')->nullable()->constrained('cfinancieros')->nullOnDelete();
            $table->boolean('generado_automaticamente')->default(false);
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['periodo', 'cfinanciero_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('egresos_cgu');
    }
};
