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
        Schema::table('crm_conversations', function (Blueprint $table) {
            $table->timestamp('last_email_notification_at')->nullable()->after('new_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_conversations', function (Blueprint $table) {
            $table->dropColumn('last_email_notification_at');
        });
    }
};
