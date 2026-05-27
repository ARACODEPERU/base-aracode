<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_course_landings', function (Blueprint $table) {
            if (!Schema::hasColumn('aca_course_landings', 'payment_facilities_link')) {
                $table->string('payment_facilities_link', 500)->nullable()->after('whatsapp_link');
            }

            if (!Schema::hasColumn('aca_course_landings', 'corporate_contact_link')) {
                $table->string('corporate_contact_link', 500)->nullable()->after('payment_facilities_link');
            }

            if (!Schema::hasColumn('aca_course_landings', 'banner_video_link')) {
                $table->text('banner_video_link')->nullable()->after('corporate_contact_link');
            }
        });
    }

    public function down(): void
    {
        Schema::table('aca_course_landings', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('aca_course_landings', 'payment_facilities_link')) {
                $columnsToDrop[] = 'payment_facilities_link';
            }

            if (Schema::hasColumn('aca_course_landings', 'corporate_contact_link')) {
                $columnsToDrop[] = 'corporate_contact_link';
            }

            if (Schema::hasColumn('aca_course_landings', 'banner_video_link')) {
                $columnsToDrop[] = 'banner_video_link';
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
