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
        Schema::create('movimientos_presupuestarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presupuesto_id')->constrained('presupuestos')->restrictOnDelete();
            $table->string('tipo');
            $table->decimal('monto', 14, 2);
            $table->string('origen_type');
            $table->unsignedBigInteger('origen_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index(['origen_type', 'origen_id']);
            $table->index(['presupuesto_id', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_presupuestarios');
    }
};
