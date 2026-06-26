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
        Schema::create('snapshots_sgf', function (Blueprint $table) {
            $table->id();
            $table->foreignId('importacion_sgf_id')->constrained('importaciones_sgf')->cascadeOnDelete();
            $table->string('sgf_id');
            $table->json('payload_crudo');
            $table->json('payload_normalizado');
            $table->string('hash');
            $table->timestamp('capturado_en')->useCurrent();

            $table->unique(['importacion_sgf_id', 'sgf_id']);
            $table->index('sgf_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('snapshots_sgf');
    }
};
