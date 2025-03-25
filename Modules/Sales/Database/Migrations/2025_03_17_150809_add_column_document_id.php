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
        Schema::table('sale_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('document_id')->nullable()->comment('si el documento tiene relacion con otro');
            $table->string('note_type_operation_id', 2)->nullable()->comment('tipo de nota de credito o devito id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_documents', function (Blueprint $table) {
            $table->dropColumn('note_type_operation_id');
            $table->dropColumn('document_id');
        });
    }
};
