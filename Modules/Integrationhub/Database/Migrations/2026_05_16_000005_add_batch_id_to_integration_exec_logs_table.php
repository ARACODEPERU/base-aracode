<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_exec_logs', function (Blueprint $table) {
            $table->string('batch_id', 80)->nullable()->after('endpoint_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('integration_exec_logs', function (Blueprint $table) {
            $table->dropColumn('batch_id');
        });
    }
};
