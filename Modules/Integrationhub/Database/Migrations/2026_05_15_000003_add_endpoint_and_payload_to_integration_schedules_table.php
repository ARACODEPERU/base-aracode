<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_schedules', function (Blueprint $table) {
            $table->foreignId('endpoint_id')
                ->nullable()
                ->after('integration_id')
                ->constrained('integration_endpoints')
                ->nullOnDelete();
            $table->json('payload')->nullable()->after('cron_expression');
        });
    }

    public function down(): void
    {
        Schema::table('integration_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('endpoint_id');
            $table->dropColumn('payload');
        });
    }
};
