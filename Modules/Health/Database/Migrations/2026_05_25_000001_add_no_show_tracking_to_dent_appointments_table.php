<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dent_appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('dent_appointments', 'no_show_at')) {
                $table->timestamp('no_show_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dent_appointments', function (Blueprint $table) {
            if (Schema::hasColumn('dent_appointments', 'no_show_at')) {
                $table->dropColumn('no_show_at');
            }
        });
    }
};
