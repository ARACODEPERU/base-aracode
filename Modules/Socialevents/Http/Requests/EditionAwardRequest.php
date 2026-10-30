<?php

namespace Modules\Socialevents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Socialevents\Support\DiplomaAwardCatalog;

/**
 * Validación de un premio de la edición.
 *
 * El equipo es obligatorio en premios de equipo y la persona en premios
 * individuales: un diploma sin ganador no debe poder emitirse.
 */
class EditionAwardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // El permiso y la organización se validan en el controlador.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'award_key' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/'],
            'label' => ['nullable', 'string', 'max:120'],
            'recipient_type' => ['required', 'in:team,player,person'],
            'team_id' => ['nullable', 'integer', 'exists:event_teams,id'],
            'person_id' => ['nullable', 'integer', 'exists:people,id'],
            'recipient_name' => ['nullable', 'string', 'max:180'],
            'source' => ['nullable', 'in:auto,manual'],
            'place' => ['nullable', 'integer', 'between:1,99'],
            'meta' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'between:0,32767'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! DiplomaAwardCatalog::exists((string) $this->input('award_key'))) {
                    $validator->errors()->add('award_key', 'El tipo de premio no es válido.');
                }

                $type = (string) $this->input('recipient_type');

                if ($type === 'team' && ! $this->input('team_id')) {
                    $validator->errors()->add('team_id', 'Selecciona el equipo premiado.');
                }

                if (in_array($type, ['player', 'person'], true) && ! $this->input('person_id')) {
                    $validator->errors()->add('person_id', 'Selecciona al jugador premiado.');
                }
            },
        ];
    }
}
