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
        if (!Schema::hasTable('user_activity_logs')) {
            Schema::create('user_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('method')->nullable();
                $table->string('url')->nullable();
                $table->json('request_payload')->nullable();
                $table->string('ip')->nullable();
                $table->string('user_agent')->nullable();
                $table->integer('status_code')->nullable();
                $table->text('error_message')->nullable();
                $table->json('details_data')->nullable();
                $table->timestamps();
            });
        } else {
            if (!Schema::hasColumn('user_activity_logs', 'method')) {
                Schema::table('user_activity_logs', function (Blueprint $table) {
                    $table->string('method')->nullable()->after('user_id');
                });
            }
            if (!Schema::hasColumn('user_activity_logs', 'url')) {
                Schema::table('user_activity_logs', function (Blueprint $table) {
                    $table->string('url')->nullable()->after('method');
                });
            }
            if (!Schema::hasColumn('user_activity_logs', 'request_payload')) {
                Schema::table('user_activity_logs', function (Blueprint $table) {
                    $table->json('request_payload')->nullable()->after('url');
                });
            }
            if (!Schema::hasColumn('user_activity_logs', 'ip')) {
                Schema::table('user_activity_logs', function (Blueprint $table) {
                    $table->string('ip')->nullable()->after('request_payload');
                });
            }
            if (!Schema::hasColumn('user_activity_logs', 'user_agent')) {
                Schema::table('user_activity_logs', function (Blueprint $table) {
                    $table->string('user_agent')->nullable()->after('ip');
                });
            }
            if (!Schema::hasColumn('user_activity_logs', 'status_code')) {
                Schema::table('user_activity_logs', function (Blueprint $table) {
                    $table->integer('status_code')->nullable()->after('user_agent');
                });
            }
            if (!Schema::hasColumn('user_activity_logs', 'error_message')) {
                Schema::table('user_activity_logs', function (Blueprint $table) {
                    $table->text('error_message')->nullable()->after('status_code');
                });
            }
            if (!Schema::hasColumn('user_activity_logs', 'details_data')) {
                Schema::table('user_activity_logs', function (Blueprint $table) {
                    $table->json('details_data')->nullable()->after('error_message');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};
