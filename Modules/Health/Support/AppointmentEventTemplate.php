<?php

namespace Modules\Health\Support;

use App\Models\Parameter;
use Carbon\Carbon;
use Modules\Dental\Entities\DentAppointment;
use Modules\Health\Entities\HealDoctor;
use Modules\Health\Entities\HealSetting;

/**
 * Plantillas del evento que se envia a Google Calendar.
 *
 * El titulo y la descripcion del evento se arman con variables tipo
 * `{paciente}` o `{hora_cita}`, y se guardan en los parametros del sistema
 * (SC-00018 y SC-00019) para que el consultorio los ajuste desde la pantalla de
 * Salud > Google Calendar. Si los parametros no existen se usan los textos de
 * fabrica, asi que nada se rompe mientras no se corra su migracion.
 *
 * Reglas de armado:
 *   - Se sustituyen las variables conocidas.
 *   - Una linea se omite cuando TODOS sus valores quedaron vacios (asi una cita
 *     sin telefono no deja la linea "Telefono:").
 *   - Las lineas en blanco se omiten.
 *   - Google Calendar no interpreta HTML en la descripcion (lo verificado en su
 *     API): por eso el texto es plano y las etiquetas se quitan, avisando en la
 *     vista previa.
 */
class AppointmentEventTemplate
{
    /** Parametro del sistema con la plantilla del titulo (summary). */
    public const TITLE_CODE = 'SC-00018';

    /** Parametro del sistema con la plantilla de la descripcion. */
    public const DESCRIPTION_CODE = 'SC-00019';

    /** Limite del titulo (la columna summary del mapeo es de 255). */
    public const TITLE_MAX = 255;

    /** Limite de la descripcion que se envia a Google. */
    public const DESCRIPTION_MAX = 1000;

    /** Longitud maxima aceptada al guardar una plantilla. */
    public const TEMPLATE_MAX = 2000;

    /** Titulo de fabrica: lo que se enviaba antes de ser configurable. */
    public const TITLE_DEFAULT = '{paciente} — {motivo_consulta}';

    /** Descripcion de fabrica: mismo contenido y orden que antes. */
    public const DESCRIPTION_DEFAULT = "Cita #{correlativo}\nDoctor: {doctor}\nTeléfono: {telefono}\n{detalle}\n{consultorio}";

    private const TITLE_PARAMETER_DESCRIPTION = 'Google Calendar (Salud): como se arma el titulo del evento; variables entre llaves, por ejemplo {paciente} — {motivo_consulta}';

    private const DESCRIPTION_PARAMETER_DESCRIPTION = 'Google Calendar (Salud): como se arma la descripcion del evento; variables entre llaves, una por linea si se quiere; texto plano, sin HTML';

    /** Estatus de la cita tal como lo ve el paciente. */
    private const STATUS_LABELS = [
        '1' => 'Pendiente',
        '2' => 'Atendido',
        '0' => 'Cancelado',
        '3' => 'No concretada',
    ];

    /**
     * Plantillas en uso, con los valores de fabrica como respaldo.
     *
     * @return array<string, mixed>
     */
    public function templates(): array
    {
        return [
            'title' => $this->template(self::TITLE_CODE, self::TITLE_DEFAULT),
            'description' => $this->template(self::DESCRIPTION_CODE, self::DESCRIPTION_DEFAULT),
            'defaultTitle' => self::TITLE_DEFAULT,
            'defaultDescription' => self::DESCRIPTION_DEFAULT,
            'codes' => ['title' => self::TITLE_CODE, 'description' => self::DESCRIPTION_CODE],
            'limits' => [
                'title' => self::TITLE_MAX,
                'description' => self::DESCRIPTION_MAX,
                'template' => self::TEMPLATE_MAX,
            ],
        ];
    }

    /**
     * Valor guardado de una plantilla (o el de fabrica si falta o esta vacia).
     */
    public function template(string $code, string $default): string
    {
        $stored = trim((string) Parameter::where('parameter_code', $code)->value('value_default'));

        return $stored === '' ? $default : $stored;
    }

    /**
     * Titulo del evento. Nunca devuelve vacio: si la plantilla no arroja nada se
     * usa la de fabrica y, como ultimo recurso, "Cita".
     */
    public function title(DentAppointment $appointment, Carbon $start, Carbon $end): string
    {
        $text = $this->render($this->template(self::TITLE_CODE, self::TITLE_DEFAULT), $appointment, $start, $end, true);

        if ($text === '') {
            $text = $this->render(self::TITLE_DEFAULT, $appointment, $start, $end, true);
        }

        return $text !== '' ? $text : 'Cita';
    }

    /**
     * Descripcion del evento. Nunca incluye informacion clinica.
     */
    public function description(DentAppointment $appointment, Carbon $start, Carbon $end): string
    {
        return $this->render($this->template(self::DESCRIPTION_CODE, self::DESCRIPTION_DEFAULT), $appointment, $start, $end, false);
    }

    /**
     * Guarda las plantillas (creando el parametro si la migracion no corrio).
     *
     * El texto se guarda ya limpio: Google Calendar no interpreta HTML.
     */
    public function save(?string $title, ?string $description): void
    {
        $incoming = [
            self::TITLE_CODE => [$title, self::TITLE_PARAMETER_DESCRIPTION],
            self::DESCRIPTION_CODE => [$description, self::DESCRIPTION_PARAMETER_DESCRIPTION],
        ];

        foreach ($incoming as $code => [$value, $parameterDescription]) {
            if ($value === null) {
                continue;
            }

            $parameter = Parameter::firstOrCreate(
                ['parameter_code' => $code],
                ['description' => $parameterDescription, 'control_type' => 'tx']
            );

            $parameter->update(['value_default' => $this->stripTags((string) $value)['text']]);
        }
    }

    /**
     * Texto de una plantilla ya armado.
     */
    public function render(string $template, DentAppointment $appointment, Carbon $start, Carbon $end, bool $singleLine = false): string
    {
        return $this->renderResult($template, $appointment, $start, $end, $singleLine)['text'];
    }

    /**
     * Arma la plantilla y devuelve tambien los avisos para la pantalla.
     *
     * @return array{text: string, warnings: array<int, string>}
     */
    public function renderResult(string $template, DentAppointment $appointment, Carbon $start, Carbon $end, bool $singleLine = false): array
    {
        $warnings = [];
        $clean = $this->stripTags((string) $template);
        $text = $clean['text'];

        if ($clean['hadTags']) {
            $warnings[] = 'El texto trae etiquetas HTML y Google Calendar no las interpreta de forma confiable: se quitan antes de enviar.';
        }

        $unknown = array_values(array_diff($this->tokens($text), array_keys($this->catalog())));

        if ($unknown !== []) {
            $warnings[] = 'Estas variables no existen y se quitaron del texto: '
                . implode(', ', array_map(static fn (string $token): string => '{' . $token . '}', $unknown)) . '.';

            $pattern = '/\{(' . implode('|', array_map(static fn (string $token): string => preg_quote($token, '/'), $unknown)) . ')\}/i';
            $text = (string) preg_replace($pattern, '', $text);
        }

        // Las variables se tratan siempre en minusculas ({Paciente} = {paciente}).
        $text = (string) preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static fn (array $match): string => '{' . mb_strtolower($match[1]) . '}',
            $text
        );

        $values = $this->values($appointment, $start, $end, $this->tokens($text));
        $lines = [];

        foreach ($this->lines($text) as $line) {
            $rendered = trim((string) strtr($line, $values));
            $lineTokens = $this->tokens($line);

            // Sin variables: se conserva tal cual, salvo las lineas vacias.
            if ($lineTokens === []) {
                if ($rendered !== '') {
                    $lines[] = $rendered;
                }

                continue;
            }

            // Con variables: la linea se omite si todos sus valores quedaron
            // vacios (asi una cita sin telefono no deja la linea "Telefono:").
            $hasValue = false;

            foreach ($lineTokens as $token) {
                if (trim((string) ($values['{' . $token . '}'] ?? '')) !== '') {
                    $hasValue = true;

                    break;
                }
            }

            if (! $hasValue) {
                continue;
            }

            // Un separador puede quedar colgando porque su valor estaba vacio
            // ("Juan Perez —"); se limpia el de los extremos, nunca el del
            // medio.
            $rendered = (string) preg_replace('/^[\s—–|·]+|[\s—–|·]+$/u', '', $rendered);
            $rendered = (string) preg_replace('/\s+-\s*$/u', '', $rendered);
            $rendered = trim((string) preg_replace('/\s{2,}/u', ' ', $rendered));

            if ($rendered !== '') {
                $lines[] = $rendered;
            }
        }

        $text = trim(implode("\n", $lines));

        if ($singleLine) {
            $text = trim((string) preg_replace('/\s+/u', ' ', $text));

            if (mb_strlen($text) > self::TITLE_MAX) {
                $warnings[] = 'El titulo quedo mas largo de ' . self::TITLE_MAX . ' caracteres y se recorto.';
                $text = mb_substr($text, 0, self::TITLE_MAX);
            }
        } elseif (mb_strlen($text) > self::DESCRIPTION_MAX) {
            $warnings[] = 'La descripcion quedo mas larga de ' . self::DESCRIPTION_MAX . ' caracteres y se recorto.';
            $text = mb_substr($text, 0, self::DESCRIPTION_MAX);
        }

        return ['text' => $text, 'warnings' => $warnings];
    }

    /**
     * Vista previa: el titulo y la descripcion exactamente como se enviarian.
     *
     * @return array{title: string, description: string, warnings: array<int, string>}
     */
    public function preview(DentAppointment $appointment, Carbon $start, Carbon $end, ?string $title = null, ?string $description = null): array
    {
        $title = trim((string) $title);
        $description = trim((string) $description);

        $titleTemplate = $title !== '' ? $title : $this->template(self::TITLE_CODE, self::TITLE_DEFAULT);
        $descriptionTemplate = $description !== '' ? $description : $this->template(self::DESCRIPTION_CODE, self::DESCRIPTION_DEFAULT);

        $titleResult = $this->renderResult($titleTemplate, $appointment, $start, $end, true);
        $descriptionResult = $this->renderResult($descriptionTemplate, $appointment, $start, $end, false);

        if ($titleResult['text'] === '') {
            $titleResult['warnings'][] = 'El titulo quedo vacio: al enviar se usara el texto de fabrica.';
        }

        return [
            'title' => $titleResult['text'],
            'description' => $descriptionResult['text'],
            'warnings' => array_values(array_unique(array_merge($titleResult['warnings'], $descriptionResult['warnings']))),
        ];
    }

    /**
     * Valores de las variables que pide el texto.
     *
     * Solo se resuelve lo que se usa: un evento que no menciona {especialidad}
     * no consulta la tabla de doctores.
     *
     * @param array<int, string> $tokens
     * @return array<string, string>
     */
    private function values(DentAppointment $appointment, Carbon $start, Carbon $end, array $tokens): array
    {
        $wants = static fn (string ...$names): bool => array_intersect($names, $tokens) !== [];

        $patient = $wants('paciente', 'paciente_documento', 'telefono', 'email') ? $appointment->patient : null;
        $doctor = $wants('doctor') ? $appointment->doctor : null;

        $values = [];

        foreach ($tokens as $token) {
            $values['{' . $token . '}'] = $this->value($token, $appointment, $start, $end, $patient, $doctor);
        }

        return $values;
    }

    /**
     * Valor de una variable de la cita.
     */
    private function value(string $token, DentAppointment $appointment, Carbon $start, Carbon $end, ?object $patient, ?object $doctor): string
    {
        return match ($token) {
            'paciente' => trim((string) ($patient->full_name ?? '')) ?: 'Paciente',
            'paciente_documento' => trim((string) ($patient->number ?? '')),
            'telefono' => $this->phone($appointment, $patient),
            'email' => trim((string) ($appointment->email ?: ($patient->email ?? ''))),
            'doctor' => trim((string) ($doctor->full_name ?? '')),
            'especialidad' => trim((string) (HealDoctor::whereKey($appointment->doctor_id)->value('specialty') ?? '')),
            'fecha_cita' => $start->format('d/m/Y'),
            'hora_cita' => $start->format('h:i A'),
            'fecha_fin' => $end->format('d/m/Y'),
            'hora_fin' => $end->format('h:i A'),
            'duracion' => max(0, (int) $start->diffInMinutes($end)) . ' min',
            'motivo_consulta', 'descripcion' => trim((string) ($appointment->description ?? '')),
            'detalle' => trim((string) ($appointment->details ?? '')),
            'ubicacion' => trim((string) ($appointment->message ?? '')),
            'correlativo' => $this->correlative($appointment),
            'estado' => self::STATUS_LABELS[(string) $appointment->status] ?? '',
            'importante' => $appointment->important ? 'Importante' : '',
            'consultorio', 'clinica' => trim((string) (HealSetting::first()?->establishment_name ?? '')),
            'sistema' => (string) config('app.name', ''),
            default => '',
        };
    }

    /**
     * Telefono de contacto: el de la cita y, si falta, el del paciente.
     */
    private function phone(DentAppointment $appointment, ?object $patient): string
    {
        $phone = trim((string) ($appointment->telephone ?? ''));

        return $phone !== '' ? $phone : trim((string) ($patient->telephone ?? ''));
    }

    /**
     * Correlativo de la cita (o su id mientras no tenga).
     */
    private function correlative(DentAppointment $appointment): string
    {
        $correlative = trim((string) ($appointment->correlative ?? ''));

        return $correlative !== '' ? $correlative : (string) $appointment->id;
    }

    /**
     * Variables presentes en un texto (en minusculas y sin repetir).
     *
     * @return array<int, string>
     */
    private function tokens(string $text): array
    {
        if (preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $text, $matches) === 0) {
            return [];
        }

        return array_values(array_unique(array_map(
            static fn (string $token): string => mb_strtolower($token),
            $matches[1]
        )));
    }

    /**
     * Lineas de un texto (acepta saltos de linea de cualquier sistema).
     *
     * @return array<int, string>
     */
    private function lines(string $text): array
    {
        return preg_split('/\R/u', $text) ?: [];
    }

    /**
     * Quita el HTML dejando un texto plano legible.
     *
     * Google Calendar no interpreta HTML en la descripcion creada por API: si
     * quedara, el paciente veria las etiquetas. Los saltos y las vinetas si se
     * conservan, que es lo que de verdad se usa al escribir.
     *
     * @return array{text: string, hadTags: bool}
     */
    private function stripTags(string $text): array
    {
        $hadTags = $this->hasTags($text);

        $text = (string) preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $text);
        $text = (string) preg_replace('/<\s*li[^>]*>/i', '• ', $text);
        $text = (string) preg_replace('/<\s*\/\s*(p|div|li|tr|h[1-6])\s*>/i', "\n", $text);
        $text = (string) preg_replace('/<[^>]*>/', '', $text);

        return [
            'text' => html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'hadTags' => $hadTags,
        ];
    }

    /**
     * true si el texto trae alguna etiqueta HTML.
     */
    private function hasTags(string $text): bool
    {
        return preg_match('/<[a-z\/][^>]*>/i', $text) === 1;
    }

    /**
     * Catalogo de variables disponibles.
     *
     * @return array<string, array{label: string, example: string, group: string}>
     */
    private function catalog(): array
    {
        return [
            'paciente' => ['label' => 'Paciente', 'example' => 'María López', 'group' => 'Del paciente'],
            'paciente_documento' => ['label' => 'Documento del paciente', 'example' => '45678912', 'group' => 'Del paciente'],
            'telefono' => ['label' => 'Teléfono (de la cita o del paciente)', 'example' => '999 888 777', 'group' => 'Del paciente'],
            'email' => ['label' => 'Correo (de la cita o del paciente)', 'example' => 'maria@correo.com', 'group' => 'Del paciente'],
            'doctor' => ['label' => 'Doctor de la cita', 'example' => 'Juan Pérez', 'group' => 'Del doctor'],
            'especialidad' => ['label' => 'Especialidad del doctor', 'example' => 'Odontología general', 'group' => 'Del doctor'],
            'fecha_cita' => ['label' => 'Fecha de la cita', 'example' => '13/11/2026', 'group' => 'Fecha y hora'],
            'hora_cita' => ['label' => 'Hora de la cita', 'example' => '08:00 AM', 'group' => 'Fecha y hora'],
            'fecha_fin' => ['label' => 'Fecha de fin', 'example' => '13/11/2026', 'group' => 'Fecha y hora'],
            'hora_fin' => ['label' => 'Hora de fin', 'example' => '08:30 AM', 'group' => 'Fecha y hora'],
            'duracion' => ['label' => 'Duración', 'example' => '30 min', 'group' => 'Fecha y hora'],
            'motivo_consulta' => ['label' => 'Motivo de la cita', 'example' => 'Limpieza dental', 'group' => 'De la cita'],
            'descripcion' => ['label' => 'Igual que {motivo_consulta}', 'example' => 'Limpieza dental', 'group' => 'De la cita'],
            'detalle' => ['label' => 'Detalle de la cita', 'example' => 'Paciente con ortodoncia', 'group' => 'De la cita'],
            'ubicacion' => ['label' => 'Ubicación o mensaje de la cita', 'example' => 'Consultorio 2', 'group' => 'De la cita'],
            'correlativo' => ['label' => 'Correlativo de la cita', 'example' => 'C-00012', 'group' => 'De la cita'],
            'estado' => ['label' => 'Estado de la cita', 'example' => 'Pendiente', 'group' => 'De la cita'],
            'importante' => ['label' => 'Marca «Importante» (vacío si no lo es)', 'example' => 'Importante', 'group' => 'De la cita'],
            'consultorio' => ['label' => 'Nombre del consultorio o clínica', 'example' => 'Clínica Dental Sonrisa', 'group' => 'Del consultorio'],
            'clinica' => ['label' => 'Igual que {consultorio}', 'example' => 'Clínica Dental Sonrisa', 'group' => 'Del consultorio'],
            'sistema' => ['label' => 'Nombre del sistema', 'example' => 'AraCode', 'group' => 'Del consultorio'],
        ];
    }

    /**
     * Variables para la pantalla, listas para pintar como botones.
     *
     * @return array<int, array{token: string, label: string, example: string, group: string}>
     */
    public function available(): array
    {
        $items = [];

        foreach ($this->catalog() as $token => $data) {
            $items[] = ['token' => $token] + $data;
        }

        return $items;
    }
}
