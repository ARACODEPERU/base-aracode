<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Health\Support\AppointmentEventTemplate;

return new class extends Migration
{
    private const PREFIX = 'Google Calendar (Salud): ';

    /**
     * Plantillas del evento que se envia a Google Calendar.
     *
     * Continua la numeracion del Sistema (SC-00017 era el ultimo). El titulo y
     * la descripcion se arman con variables entre llaves ({paciente},
     * {hora_cita}, ...) y viven aqui para que el consultorio los ajuste desde
     * Salud > Google Calendar.
     *
     * Los valores por defecto son los textos de fabrica, es decir exactamente lo
     * que se enviaba antes de que esto fuera configurable: si la migracion no se
     * ejecuta, la aplicacion usa esos mismos textos y todo sigue igual.
     *
     * Es idempotente: solo crea las filas que falten y no pisa un valor ya
     * cargado por el administrador.
     */
    public function up(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        $this->ensure(
            AppointmentEventTemplate::TITLE_CODE,
            self::PREFIX . 'como se arma el titulo del evento: variables entre llaves, por ejemplo ' . AppointmentEventTemplate::TITLE_DEFAULT,
            AppointmentEventTemplate::TITLE_DEFAULT
        );

        $this->ensure(
            AppointmentEventTemplate::DESCRIPTION_CODE,
            self::PREFIX . 'como se arma la descripcion del evento: variables entre llaves, una por linea si se quiere; texto plano, sin HTML',
            AppointmentEventTemplate::DESCRIPTION_DEFAULT
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        // Revertir solo las filas propias (por codigo + descripcion).
        Parameter::whereIn('parameter_code', [
            AppointmentEventTemplate::TITLE_CODE,
            AppointmentEventTemplate::DESCRIPTION_CODE,
        ])->where('description', 'like', self::PREFIX . '%')->delete();
    }

    /**
     * Crea el parametro si falta. Si ya existe solo completa la descripcion
     * cuando quedo vacia o es una de las nuestras.
     */
    private function ensure(string $code, string $description, string $default): void
    {
        $parameter = Parameter::firstOrCreate(
            ['parameter_code' => $code],
            [
                'description'     => $description,
                'control_type'    => 'tx',
                'json_query_data' => null,
                'value_default'   => $default,
            ]
        );

        $current = trim((string) $parameter->description);

        if (($current === '' || str_starts_with($current, self::PREFIX)) && $current !== $description) {
            $parameter->update(['description' => $description]);
        }
    }
};
