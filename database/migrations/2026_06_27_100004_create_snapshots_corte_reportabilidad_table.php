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
        Schema::create('snapshots_corte_reportabilidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corte_reportabilidad_id')->constrained('cortes_reportabilidad')->cascadeOnDelete();
            $table->foreignId('corte_reportabilidad_item_id')->nullable()->constrained('cortes_reportabilidad_items')->cascadeOnDelete();
            $table->json('payload_crudo');
            $table->string('hash');
            $table->timestamp('capturado_en')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('snapshots_corte_reportabilidad');
    }
};
