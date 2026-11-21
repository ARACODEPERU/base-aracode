<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda si la persona otorgó permiso absoluto al autorizar al agente. La
 * decisión viaja al token como ability academic:autonomous.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mcp_oauth_codes') || Schema::hasColumn('mcp_oauth_codes', 'autonomy')) {
            return;
        }

        Schema::table('mcp_oauth_codes', function (Blueprint $table) {
            $table->boolean('autonomy')->default(false)->after('scope');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('mcp_oauth_codes', 'autonomy')) {
            Schema::table('mcp_oauth_codes', function (Blueprint $table) {
                $table->dropColumn('autonomy');
            });
        }
    }
};
