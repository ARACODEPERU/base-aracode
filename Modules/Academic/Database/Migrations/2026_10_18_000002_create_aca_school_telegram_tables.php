<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas del canal Telegram para los avisos de asistencia escolar y ajuste
     * de la tabla de entregas (aca_school_notice_deliveries).
     *
     * 1. aca_school_telegram_subscriptions: vínculo entre el chat de Telegram,
     *    el padre/madre/apoderado (guardian_person_id) y cada hijo estudiante
     *    del colegio (student_id). Un mismo chat puede quedar suscrito a uno,
     *    dos o más hijos.
     * 2. aca_school_telegram_sessions: conversación paso a paso del bot escolar
     *    (esperando DNI del apoderado, DNI del hijo o confirmación de si tiene
     *    otro hijo en el colegio).
     * 3. aca_school_notice_deliveries: suma las columnas channel ('sms'|'telegram')
     *    y chat_id, y permite que phone sea nullable en entregas de Telegram.
     */
    public function up(): void
    {
        if (! Schema::hasTable('aca_school_telegram_subscriptions')) {
            Schema::create('aca_school_telegram_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->unsignedBigInteger('guardian_person_id')->index();
                $table->string('chat_id', 64)->index();
                $table->string('telegram_username', 100)->nullable();
                $table->string('telegram_first_name', 150)->nullable();
                $table->string('status', 20)->default('active')->index();
                $table->timestamp('registered_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();

                $table->unique(['student_id', 'chat_id'], 'aca_school_tg_subs_student_chat_unique');
            });
        }

        if (! Schema::hasTable('aca_school_telegram_sessions')) {
            Schema::create('aca_school_telegram_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('chat_id', 64)->unique();
                $table->string('telegram_username', 100)->nullable();
                $table->string('telegram_first_name', 150)->nullable();
                $table->string('step', 40)->default('awaiting_guardian_dni');
                $table->text('context')->nullable();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('aca_school_notice_deliveries')) {
            Schema::table('aca_school_notice_deliveries', function (Blueprint $table) {
                if (! Schema::hasColumn('aca_school_notice_deliveries', 'channel')) {
                    $table->string('channel', 20)->default('sms')->after('event')
                        ->comment('sms|telegram: canal por el que viaja el aviso');
                }

                if (! Schema::hasColumn('aca_school_notice_deliveries', 'chat_id')) {
                    $table->string('chat_id', 64)->nullable()->after('phone')
                        ->comment('Chat de Telegram del apoderado (cuando channel = telegram)');
                }
            });

            if (Schema::hasColumn('aca_school_notice_deliveries', 'phone')) {
                try {
                    Schema::table('aca_school_notice_deliveries', function (Blueprint $table) {
                        $table->string('phone', 30)->nullable()->change();
                    });
                } catch (\Throwable) {
                    // En motores que no soportan change() en memoria se usa string vacío como respaldo.
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('aca_school_notice_deliveries')) {
            Schema::table('aca_school_notice_deliveries', function (Blueprint $table) {
                if (Schema::hasColumn('aca_school_notice_deliveries', 'chat_id')) {
                    $table->dropColumn('chat_id');
                }

                if (Schema::hasColumn('aca_school_notice_deliveries', 'channel')) {
                    $table->dropColumn('channel');
                }
            });
        }

        Schema::dropIfExists('aca_school_telegram_sessions');
        Schema::dropIfExists('aca_school_telegram_subscriptions');
    }
};
