<?php

namespace Tests\Unit\Modules\Academic\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Esquema mínimo que necesita el padrón del registro de Telegram.
 *
 * El suite no puede correr todas las migraciones de la aplicación sobre sqlite,
 * así que se arman a mano las tablas que consultan TelegramStudentDirectory y
 * TelegramStudentAccount: la persona (con su número de documento y correo), el
 * alumno, el curso (con precio), la matrícula (con vigencia), la suscripción y su
 * plan, los módulos y los certificados.
 *
 * No se usa RefreshDatabase por el mismo motivo: el esquema se crea y se
 * destruye en cada prueba.
 */
trait BuildsTelegramStudentSchema
{
    private const TELEGRAM_STUDENT_TABLES = [
        'aca_certificates',
        'aca_modules',
        'aca_cap_registrations',
        'aca_student_subscriptions',
        'aca_subscription_types',
        'aca_courses',
        'aca_students',
        'people',
    ];

    private function createTelegramStudentSchema(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('short_name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('number')->unique();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->timestamps();
        });

        Schema::create('aca_students', function (Blueprint $table) {
            $table->id();
            $table->string('student_code')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->timestamps();
        });

        Schema::create('aca_courses', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
            $table->string('type_description')->nullable();
            $table->boolean('status')->default(true);
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('aca_cap_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('course_id');
            $table->boolean('status')->default(false);
            $table->boolean('unlimited')->default(false);
            $table->date('date_end')->nullable();
            $table->timestamps();
        });

        Schema::create('aca_student_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
        });

        // Planes de suscripcion: la marca Premium VIP sale de su titulo.
        Schema::create('aca_subscription_types', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('aca_modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('aca_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('module_id')->nullable();
            $table->timestamps();
        });
    }

    private function dropTelegramStudentSchema(): void
    {
        foreach (self::TELEGRAM_STUDENT_TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
}
