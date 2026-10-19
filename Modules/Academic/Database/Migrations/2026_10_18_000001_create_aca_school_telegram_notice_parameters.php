<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Token del bot de Telegram para los avisos de asistencia escolar (BotFather). */
    private const TOKEN_CODE = 'SC-00033';

    /** Interruptor general del canal Telegram de los avisos de asistencia. */
    private const ENABLED_CODE = 'SC-00034';

    /** Avisar por Telegram cuando el alumno registra su entrada al colegio. */
    private const ENTRY_CODE = 'SC-00035';

    /** Avisar por Telegram cuando el alumno registra su salida del colegio. */
    private const EXIT_CODE = 'SC-00036';

    /** Avisar por Telegram cuando la entrada del alumno es con tardanza. */
    private const LATE_CODE = 'SC-00037';

    /** Avisar por Telegram cuando la salida del alumno es anticipada. */
    private const EARLY_EXIT_CODE = 'SC-00038';

    /** Plantilla del mensaje de entrada por Telegram. */
    private const ENTRY_MESSAGE_CODE = 'SC-00039';

    /** Plantilla del mensaje de salida por Telegram. */
    private const EXIT_MESSAGE_CODE = 'SC-00040';

    /** Avisar por Telegram solo al apoderado marcado como principal. */
    private const PRIMARY_ONLY_CODE = 'SC-00041';

    /** Máximo de avisos de Telegram por alumno y día (0 = sin límite). */
    private const DAILY_LIMIT_CODE = 'SC-00042';

    /** Pausa mínima entre cada mensaje de Telegram en milisegundos. */
    private const INTERVAL_CODE = 'SC-00043';

    /** Plantilla por defecto del aviso de entrada por Telegram. */
    private const DEFAULT_ENTRY_MESSAGE = '🏫 Estimado(a) {apoderado}: le informamos que {alumno} ({seccion}) registró su INGRESO al colegio {colegio} el {fecha} a las {hora} ({estado}).';

    /** Plantilla por defecto del aviso de salida por Telegram. */
    private const DEFAULT_EXIT_MESSAGE = '🏫 Estimado(a) {apoderado}: le informamos que {alumno} ({seccion}) registró su SALIDA del colegio {colegio} el {fecha} a las {hora} ({estado}).';

    /** Valor por defecto: 500 ms entre mensajes de Telegram. */
    private const DEFAULT_INTERVAL_MS = '500';

    /**
     * Parámetros del canal Telegram para los avisos de asistencia del colegio.
     *
     * Este canal es propio del módulo escolar: usa un bot de Telegram propio
     * (SC-00033) e interruptores independientes de los avisos por SMSGate
     * (SC-00020 a SC-00032) y del bot de capacitaciones (SC-00002).
     *
     * Es idempotente: solo crea las filas que falten y no pisa un valor ya
     * cargado por el administrador.
     */
    public function up(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        $this->ensure(self::TOKEN_CODE, 'tx', 'Avisos de asistencia Telegram (Colegio): Token / ID del bot de Telegram entregado por BotFather', null);
        $this->ensure(self::ENABLED_CODE, 'chx', 'Avisos de asistencia Telegram (Colegio): activar el envío de avisos por Telegram (interruptor general)', '0');
        $this->ensure(self::ENTRY_CODE, 'chx', 'Avisos de asistencia Telegram (Colegio): avisar al apoderado cuando el alumno registra su ENTRADA', '1');
        $this->ensure(self::EXIT_CODE, 'chx', 'Avisos de asistencia Telegram (Colegio): avisar al apoderado cuando el alumno registra su SALIDA', '1');
        $this->ensure(self::LATE_CODE, 'chx', 'Avisos de asistencia Telegram (Colegio): avisar también cuando la entrada es con tardanza', '1');
        $this->ensure(self::EARLY_EXIT_CODE, 'chx', 'Avisos de asistencia Telegram (Colegio): avisar también cuando la salida es anticipada', '1');
        $this->ensure(self::PRIMARY_ONLY_CODE, 'chx', 'Avisos de asistencia Telegram (Colegio): avisar solo al apoderado principal', '0');

        $this->ensure(self::ENTRY_MESSAGE_CODE, 'tx', 'Avisos de asistencia Telegram (Colegio): texto del mensaje de ENTRADA (variables: {alumno}, {apoderado}, {colegio}, {seccion}, {fecha}, {hora}, {estado})', self::DEFAULT_ENTRY_MESSAGE);
        $this->ensure(self::EXIT_MESSAGE_CODE, 'tx', 'Avisos de asistencia Telegram (Colegio): texto del mensaje de SALIDA (variables: {alumno}, {apoderado}, {colegio}, {seccion}, {fecha}, {hora}, {estado})', self::DEFAULT_EXIT_MESSAGE);
        $this->ensure(self::DAILY_LIMIT_CODE, 'tx', 'Avisos de asistencia Telegram (Colegio): máximo de avisos por alumno y día (0 = sin límite)', '0');
        $this->ensure(self::INTERVAL_CODE, 'tx', 'Avisos de asistencia Telegram (Colegio): pausa mínima entre cada mensaje de Telegram en milisegundos', self::DEFAULT_INTERVAL_MS);
    }

    public function down(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        Parameter::whereIn('parameter_code', [
            self::TOKEN_CODE,
            self::ENABLED_CODE,
            self::ENTRY_CODE,
            self::EXIT_CODE,
            self::LATE_CODE,
            self::EARLY_EXIT_CODE,
            self::ENTRY_MESSAGE_CODE,
            self::EXIT_MESSAGE_CODE,
            self::PRIMARY_ONLY_CODE,
            self::DAILY_LIMIT_CODE,
            self::INTERVAL_CODE,
        ])->where('description', 'like', 'Avisos de asistencia Telegram (Colegio):%')->delete();
    }

    /**
     * Crea el parámetro si falta. Si ya existe solo completa la descripción
     * cuando quedó vacía o es una de las nuestras.
     */
    private function ensure(string $code, string $controlType, string $description, ?string $default): void
    {
        $parameter = Parameter::firstOrCreate(
            ['parameter_code' => $code],
            [
                'description' => $description,
                'control_type' => $controlType,
                'json_query_data' => null,
                'value_default' => $default,
            ]
        );

        $current = trim((string) $parameter->description);

        if (($current === '' || str_starts_with($current, 'Avisos de asistencia Telegram (Colegio):')) && $current !== $description) {
            $parameter->update(['description' => $description]);
        }
    }
};
