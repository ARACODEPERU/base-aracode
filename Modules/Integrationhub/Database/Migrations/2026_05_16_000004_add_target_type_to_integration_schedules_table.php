<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_schedules', function (Blueprint $table) {
            $table->string('target_type', 30)
                ->default('integration_endpoint')
                ->after('integration_id');
            $table->string('api_route_name', 150)->nullable()->after('endpoint_id');
        });
    }

    public function down(): void
    {
        Schema::table('integration_schedules', function (Blueprint $table) {
            $table->dropColumn(['target_type', 'api_route_name']);
        });
    }
};
