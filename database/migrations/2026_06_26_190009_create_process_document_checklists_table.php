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
        Schema::create('process_document_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->unique()->constrained('processes')->cascadeOnDelete();
            $table->foreignId('document_requirement_set_id')->constrained('document_requirement_sets')->restrictOnDelete();
            $table->timestamp('generated_at')->useCurrent();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_document_checklists');
    }
};
