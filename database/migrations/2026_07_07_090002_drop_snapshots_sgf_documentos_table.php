<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Reemplazada por `snapshots_datos_externos_documentos` (capa transversal de integraciones).
     */
    public function up(): void
    {
        Schema::dropIfExists('snapshots_sgf_documentos');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('snapshots_sgf_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_sgf_id')->constrained('snapshots_sgf')->cascadeOnDelete();
            $table->foreignId('documento_id')->constrained('documentos')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }
};
