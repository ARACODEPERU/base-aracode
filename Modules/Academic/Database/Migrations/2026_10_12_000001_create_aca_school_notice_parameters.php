<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** URL del servidor de SMSGate para los avisos de asistencia escolar. */
    private const URL_CODE = 'SC-00020';

    /** Usuario de la cuenta de SMSGate de los avisos de asistencia. */
    private const USER_CODE = 'SC-00021';

    /** Contrasena de la cuenta de SMSGate de los avisos de asistencia. */
    private const PASSWORD_CODE = 'SC-00022';

    /** Interruptor maestro del canal de avisos de asistencia. */
    private const ENABLED_CODE = 'SC-00023';

    /** Avisar cuando el alumno registra su entrada al colegio. */
    private const ENTRY_CODE = 'SC-00024';

    /** Avisar cuando el alumno registra su salida del colegio. */
    private const EXIT_CODE = 'SC-00025';

    /** Avisar cuando la entrada del alumno es con tardanza. */
    private const LATE_CODE = 'SC-00026';

    /** Avisar cuando la salida del alumno es anticipada. */
    private const EARLY_EXIT_CODE = 'SC-00027';

    /** Plantilla del mensaje de entrada. */
    private const ENTRY_MESSAGE_CODE = 'SC-00028';

    /** Plantilla del mensaje de salida. */
    private const EXIT_MESSAGE_CODE = 'SC-00029';

    /** Avisar solo al apoderado marcado como principal. */
    private const PRIMARY_ONLY_CODE = 'SC-00030';

    /** Maximo de avisos por alumno y dia (0 = sin limite). */
    private const DAILY_LIMIT_CODE = 'SC-00031';

    /** URL por defecto: la que usa la aplicacion movil de SMSGate. */
    private const DEFAULT_URL = 'https://api.sms-gate.app/mobile/v1';

    /** Plantilla por defecto del aviso de entrada. */
    private const DEFAULT_ENTRY_MESSAGE = 'Estimado(a) {apoderado}: le informamos que {alumno} registró su INGRESO al colegio {colegio} el {fecha} a las {hora} ({estado}).';

    /** Plantilla por defecto del aviso de salida. */
    private const DEFAULT_EXIT_MESSAGE = 'Estimado(a) {apoderado}: le informamos que {alumno} registró su SALIDA del colegio {colegio} el {fecha} a las {hora} ({estado}).';

    /**
     * Parametros del canal SMSGate para los avisos de asistencia del colegio.
     *
     * La porteria registra la entrada y la salida del alumno escaneando su
     * carné; cuando eso pasa, el sistema avisa al padre o apoderado por SMS.
     * Este canal es propio del modulo Academico escolar: no comparte las
     * credenciales de las campanas de especializacion (SC-00003/04/05) ni las
     * de Salud (SC-00006/07/08). La numeracion continua la del Sistema (hasta
     * SC-00019).
     *
     * Es idempotente: solo crea las filas que falten y no pisa un valor ya
     * cargado por el administrador.
     */
    public function up(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        $this->ensure(self::URL_CODE, 'tx', 'Avisos de asistencia (Colegio): URL del servidor de SMSGate (la misma que usa la aplicacion movil; por defecto ' . self::DEFAULT_URL . ')', self::DEFAULT_URL);
        $this->ensure(self::USER_CODE, 'tx', 'Avisos de asistencia (Colegio): usuario (username) de la cuenta de SMSGate', null);
        $this->ensure(self::PASSWORD_CODE, 'pwd', 'Avisos de asistencia (Colegio): contrasena (password) de la cuenta de SMSGate', null);

        $this->ensure(self::ENABLED_CODE, 'chx', 'Avisos de asistencia (Colegio): activar el envio de avisos por SMS (interruptor general)', '0');
        $this->ensure(self::ENTRY_CODE, 'chx', 'Avisos de asistencia (Colegio): avisar al apoderado cuando el alumno registra su ENTRADA', '1');
        $this->ensure(self::EXIT_CODE, 'chx', 'Avisos de asistencia (Colegio): avisar al apoderado cuando el alumno registra su SALIDA', '1');
        $this->ensure(self::LATE_CODE, 'chx', 'Avisos de asistencia (Colegio): avisar tambien cuando la entrada es con tardanza', '1');
        $this->ensure(self::EARLY_EXIT_CODE, 'chx', 'Avisos de asistencia (Colegio): avisar tambien cuando la salida es anticipada', '1');
        $this->ensure(self::PRIMARY_ONLY_CODE, 'chx', 'Avisos de asistencia (Colegio): avisar solo al apoderado principal', '0');

        $this->ensure(self::ENTRY_MESSAGE_CODE, 'tx', 'Avisos de asistencia (Colegio): texto del mensaje de ENTRADA (variables: {alumno}, {apoderado}, {colegio}, {seccion}, {fecha}, {hora}, {estado})', self::DEFAULT_ENTRY_MESSAGE);
        $this->ensure(self::EXIT_MESSAGE_CODE, 'tx', 'Avisos de asistencia (Colegio): texto del mensaje de SALIDA (variables: {alumno}, {apoderado}, {colegio}, {seccion}, {fecha}, {hora}, {estado})', self::DEFAULT_EXIT_MESSAGE);
        $this->ensure(self::DAILY_LIMIT_CODE, 'tx', 'Avisos de asistencia (Colegio): maximo de avisos por alumno y dia (0 = sin limite)', '0');
    }

    public function down(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        // Revertir solo las filas propias (por codigo + descripcion).
        Parameter::whereIn('parameter_code', [
            self::URL_CODE,
            self::USER_CODE,
            self::PASSWORD_CODE,
            self::ENABLED_CODE,
            self::ENTRY_CODE,
            self::EXIT_CODE,
            self::LATE_CODE,
            self::EARLY_EXIT_CODE,
            self::ENTRY_MESSAGE_CODE,
            self::EXIT_MESSAGE_CODE,
            self::PRIMARY_ONLY_CODE,
            self::DAILY_LIMIT_CODE,
        ])->where('description', 'like', 'Avisos de asistencia (Colegio):%')->delete();
    }

    /**
     * Crea el parametro si falta. Si ya existe solo completa la descripcion
     * cuando quedo vacia o es una de las nuestras.
     */
    private function ensure(string $code, string $controlType, string $description, ?string $default): void
    {
        $parameter = Parameter::firstOrCreate(
            ['parameter_code' => $code],
            [
                'description'     => $description,
                'control_type'    => $controlType,
                'json_query_data' => null,
                'value_default'   => $default,
            ]
        );

        $current = trim((string) $parameter->description);

        if (($current === '' || str_starts_with($current, 'Avisos de asistencia (Colegio):')) && $current !== $description) {
            $parameter->update(['description' => $description]);
        }
    }
};
