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
        Schema::table('checklist_documental_proceso_items', function (Blueprint $table) {
            $table->dropForeign(['requisito_documental_id']);
            $table->foreign('requisito_documental_id')
                ->references('id')->on('requisitos_documentales')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checklist_documental_proceso_items', function (Blueprint $table) {
            $table->dropForeign(['requisito_documental_id']);
            $table->foreign('requisito_documental_id')
                ->references('id')->on('requisitos_documentales')
                ->restrictOnDelete();
        });
    }
};
