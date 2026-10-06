<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bloques de aviso previos a la cita.
     *
     * Son tres y fijos (Salud > Avisos): dos "minutos antes" de la cita y uno
     * "un dia antes" a una hora concreta. Cada fila guarda su interruptor
     * Activo, su tiempo (minutes_before o send_time) y el mensaje (admite HTML
     * y emoticonos, con las variables {paciente}, {hora_cita}, {nombre_dr},
     * {fecha_cita} y {clinica}).
     */
    public function up(): void
    {
        Schema::create('heal_appointment_notices', function (Blueprint $table) {
            $table->id();
            $table->string('key', 30)->unique()->comment('first, second o day_before');
            $table->boolean('active')->default(false);
            $table->unsignedSmallInteger('minutes_before')->nullable()->comment('Minutos antes de la cita (bloques first y second)');
            $table->time('send_time')->nullable()->comment('Hora del dia en que se envia (bloque day_before)');
            $table->text('message')->nullable();
            $table->timestamps();
        });

        $now = now();

        $defaults = [
            [
                'key' => 'first',
                'active' => true,
                'minutes_before' => 30,
                'send_time' => null,
                'message' => "Hola {paciente} 👋\nTe recordamos tu cita de hoy a las {hora_cita} con {nombre_dr}. ¡Te esperamos! 🩺",
            ],
            [
                'key' => 'second',
                'active' => false,
                'minutes_before' => 60,
                'send_time' => null,
                'message' => "Hola {paciente}, te esperamos hoy a las {hora_cita} con {nombre_dr}. Si no puedes asistir, avísanos. 🙂",
            ],
            [
                'key' => 'day_before',
                'active' => false,
                'minutes_before' => null,
                'send_time' => '08:00',
                'message' => "Hola {paciente} 📅\nMañana {fecha_cita} tienes tu cita a las {hora_cita} con {nombre_dr} en {clinica}. ¡Te esperamos!",
            ],
        ];

        foreach ($defaults as $row) {
            if (DB::table('heal_appointment_notices')->where('key', $row['key'])->exists()) {
                continue;
            }

            DB::table('heal_appointment_notices')->insert(array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('heal_appointment_notices');
    }
};
