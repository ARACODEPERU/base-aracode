<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heal_attentions', function (Blueprint $table) {
            if (!Schema::hasColumn('heal_attentions', 'signed_at')) {
                $table->timestamp('signed_at')->nullable()->after('observations');
            }

            if (!Schema::hasColumn('heal_attentions', 'signed_by_doctor_id')) {
                $table->unsignedBigInteger('signed_by_doctor_id')->nullable()->after('signed_at');
            }

            if (!Schema::hasColumn('heal_attentions', 'signed_by_user_id')) {
                $table->unsignedBigInteger('signed_by_user_id')->nullable()->after('signed_by_doctor_id');
            }

            if (!Schema::hasColumn('heal_attentions', 'signature_ip')) {
                $table->string('signature_ip', 45)->nullable()->after('signed_by_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('heal_attentions', function (Blueprint $table) {
            if (Schema::hasColumn('heal_attentions', 'signed_by_doctor_id')) {
                $table->dropColumn('signed_by_doctor_id');
            }

            if (Schema::hasColumn('heal_attentions', 'signed_by_user_id')) {
                $table->dropColumn('signed_by_user_id');
            }

            if (Schema::hasColumn('heal_attentions', 'signature_ip')) {
                $table->dropColumn('signature_ip');
            }

            if (Schema::hasColumn('heal_attentions', 'signed_at')) {
                $table->dropColumn('signed_at');
            }
        });
    }
};
