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
        Schema::create('snapshots_datos_externos_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_datos_externo_id')->constrained('snapshots_datos_externos')->cascadeOnDelete();
            $table->foreignId('documento_id')->constrained('documentos')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('snapshots_datos_externos_documentos');
    }
};
