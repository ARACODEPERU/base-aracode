<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parámetros confidenciales: se guardan como cualquier otro, pero la
     * pantalla de Parámetros del sistema nunca los vuelve a mostrar.
     *
     * Son las credenciales que el administrador registra una sola vez: el
     * Client ID, el Client Secret y el refresh token de Google Calendar, el
     * secreto del canal de notificaciones push y la contraseña de SMSGate de
     * los avisos de citas.
     */
    private const SECRET_CODES = [
        'SC-00008', // SMSGate (Salud): contraseña
        'SC-00011', // Google Calendar: Client ID
        'SC-00012', // Google Calendar: Client Secret
        'SC-00013', // Google Calendar: refresh token
        'SC-00015', // Google Calendar: secreto del canal push
    ];

    /**
     * Deja los parámetros confidenciales en `pwd` (el tipo que la interfaz
     * enmascara) y agrega al estado del calendario los datos de la cuenta de
     * Google conectada, para poder mostrar "Conectada como correo@gmail.com".
     *
     * Es idempotente: solo cambia las filas que hoy son `tx` y solo agrega las
     * columnas que falten.
     */
    public function up(): void
    {
        if (Schema::hasTable('parameters')) {
            Parameter::whereIn('parameter_code', self::SECRET_CODES)
                ->where('control_type', 'tx')
                ->update(['control_type' => 'pwd']);
        }

        if (! Schema::hasTable('heal_google_calendar_states')) {
            return;
        }

        Schema::table('heal_google_calendar_states', function (Blueprint $table) {
            if (! Schema::hasColumn('heal_google_calendar_states', 'account_email')) {
                $table->string('account_email', 190)->nullable()->after('calendar_id');
            }

            if (! Schema::hasColumn('heal_google_calendar_states', 'account_name')) {
                $table->string('account_name', 190)->nullable()->after('account_email');
            }

            if (! Schema::hasColumn('heal_google_calendar_states', 'account_picture')) {
                $table->string('account_picture', 500)->nullable()->after('account_name');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('heal_google_calendar_states')) {
            Schema::table('heal_google_calendar_states', function (Blueprint $table) {
                foreach (['account_picture', 'account_name', 'account_email'] as $column) {
                    if (Schema::hasColumn('heal_google_calendar_states', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        // Revertir solo las filas propias (por código + el tipo que pusimos).
        if (Schema::hasTable('parameters')) {
            Parameter::whereIn('parameter_code', self::SECRET_CODES)
                ->where('control_type', 'pwd')
                ->update(['control_type' => 'tx']);
        }
    }
};
