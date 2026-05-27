<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_editions', function (Blueprint $table) {
            if (! Schema::hasColumn('event_editions', 'contact_name')) {
                $table->string('contact_name', 255)->nullable()->after('status');
            }
            if (! Schema::hasColumn('event_editions', 'contact_phone')) {
                $table->string('contact_phone', 50)->nullable()->after('contact_name');
            }
            if (! Schema::hasColumn('event_editions', 'contact_whatsapp')) {
                $table->string('contact_whatsapp', 50)->nullable()->after('contact_phone');
            }
            if (! Schema::hasColumn('event_editions', 'landing_hero_image')) {
                $table->string('landing_hero_image', 300)->nullable()->after('contact_whatsapp');
            }
            if (! Schema::hasColumn('event_editions', 'landing_published')) {
                $table->boolean('landing_published')->default(false)->after('landing_hero_image');
            }
            if (! Schema::hasColumn('event_editions', 'public_slug')) {
                $table->string('public_slug', 120)->nullable()->unique()->after('landing_published');
            }
            if (! Schema::hasColumn('event_editions', 'mobile_enabled')) {
                $table->boolean('mobile_enabled')->default(true)->after('public_slug');
            }
            if (! Schema::hasColumn('event_editions', 'branding')) {
                $table->json('branding')->nullable()->after('mobile_enabled');
            }
        });

        if (Schema::hasColumn('event_editions', 'landing_published')) {
            DB::table('event_editions')->update(['landing_published' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('event_editions', function (Blueprint $table) {
            $columns = [
                'contact_name',
                'contact_phone',
                'contact_whatsapp',
                'landing_hero_image',
                'landing_published',
                'public_slug',
                'mobile_enabled',
                'branding',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('event_editions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
