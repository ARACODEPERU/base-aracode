<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_course_landings', function (Blueprint $table) {
            $table->string('payment_facilities_link', 500)->nullable()->after('whatsapp_link');
            $table->string('corporate_contact_link', 500)->nullable()->after('payment_facilities_link');
            $table->text('banner_video_link')->nullable()->after('corporate_contact_link');
        });
    }

    public function down(): void
    {
        Schema::table('aca_course_landings', function (Blueprint $table) {
            $table->dropColumn('payment_facilities_link');
            $table->dropColumn('corporate_contact_link');
            $table->dropColumn('banner_video_link');
        });
    }
};
