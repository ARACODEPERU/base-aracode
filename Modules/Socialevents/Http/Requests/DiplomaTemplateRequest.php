<?php

namespace Modules\Socialevents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Creación de un modelo de diploma reutilizable.
 *
 * Puede llegar desde una edición (con `award_id`, que define el tipo) o desde
 * el listado del torneo (con `diploma_type`). El fondo puede enviarse como
 * archivo o como base64 desde el editor.
 */
class DiplomaTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'award_id' => ['nullable', 'integer', 'exists:socialevent_edition_awards,id'],
            'diploma_type' => ['nullable', 'in:team,player'],
            'edition_id' => ['nullable', 'integer', 'exists:event_editions,id'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'name' => ['required', 'string', 'max:120'],
            'background' => ['required_without:background_base64', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
            'background_base64' => ['required_without:background', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Escribe un nombre para el modelo.',
            'background.required_without' => 'Sube la imagen de fondo del diploma.',
            'background.image' => 'El fondo debe ser una imagen.',
            'background.max' => 'La imagen no puede superar los 8 MB.',
        ];
    }

    /**
     * Debe llegar el premio (para deducir el tipo) o el tipo explícito.
     */
    public function after(): array
    {
        return [
            function ($validator) {
                if (! $this->input('award_id') && ! $this->input('diploma_type')) {
                    $validator->errors()->add(
                        'diploma_type',
                        'Indica si el modelo es por equipo o por jugador.'
                    );
                }
            },
        ];
    }
}
