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
        Schema::table('crm_messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'created_at'], 'idx_conversation_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_messages', function (Blueprint $table) {
            $table->dropIndex('idx_conversation_created_at');
        });
    }
};
