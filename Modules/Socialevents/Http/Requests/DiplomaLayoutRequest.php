<?php

namespace Modules\Socialevents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Socialevents\Support\DiplomaFonts;

/**
 * Valida el JSON del editor Konva antes de guardarlo.
 *
 * Los límites evitan layouts corruptos que rompan el render o el PDF: tipos
 * desconocidos, colores inválidos, rutas externas y columnas fuera de rango.
 */
class DiplomaLayoutRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:120'],
            'layout' => ['required', 'array'],
            'layout.version' => ['nullable', 'integer', 'between:1,10'],
            'layout.background' => ['nullable', 'array'],
            'layout.background.width' => ['nullable', 'integer', 'between:100,5000'],
            'layout.background.height' => ['nullable', 'integer', 'between:100,5000'],
            'layout.elements' => ['required', 'array', 'max:60'],
            'layout.elements.*.id' => ['required', 'string', 'max:60'],
            'layout.elements.*.type' => ['required', 'in:text,image,qr,rect,line,squad'],
            'layout.elements.*.name' => ['nullable', 'string', 'max:60'],
            'layout.elements.*.x' => ['required', 'numeric', 'between:-2000,5000'],
            'layout.elements.*.y' => ['required', 'numeric', 'between:-2000,5000'],
            'layout.elements.*.width' => ['nullable', 'numeric', 'between:-2000,5000'],
            'layout.elements.*.height' => ['nullable', 'numeric', 'between:-2000,5000'],
            'layout.elements.*.rotation' => ['nullable', 'numeric', 'between:-360,360'],
            'layout.elements.*.visible' => ['nullable', 'boolean'],
            'layout.elements.*.locked' => ['nullable', 'boolean'],
            'layout.elements.*.text' => ['nullable', 'string', 'max:500'],
            'layout.elements.*.fontFamily' => ['nullable', 'in:'.implode(',', DiplomaFonts::FAMILIES)],
            'layout.elements.*.fontSize' => ['nullable', 'numeric', 'between:6,400'],
            'layout.elements.*.color' => ['nullable', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'layout.elements.*.stroke' => ['nullable', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'layout.elements.*.strokeWidth' => ['nullable', 'numeric', 'between:0,80'],
            'layout.elements.*.fill' => ['nullable', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'layout.elements.*.align' => ['nullable', 'in:left,center,right'],
            'layout.elements.*.lineHeight' => ['nullable', 'numeric', 'between:0.6,4'],
            'layout.elements.*.letterSpacing' => ['nullable', 'numeric', 'between:-20,80'],
            'layout.elements.*.radius' => ['nullable', 'numeric', 'between:0,1000'],
            'layout.elements.*.size' => ['nullable', 'numeric', 'between:20,1500'],
            'layout.elements.*.mode' => ['nullable', 'in:landing,diploma'],
            'layout.elements.*.src' => ['nullable', 'string', 'max:255'],
            'layout.elements.*.columns' => ['nullable', 'integer', 'between:1,4'],
            'layout.elements.*.gap' => ['nullable', 'numeric', 'between:0,400'],
            'layout.elements.*.sortBy' => ['nullable', 'in:number,name,position'],
            'layout.elements.*.showJerseyNumber' => ['nullable', 'boolean'],
            'layout.elements.*.showPosition' => ['nullable', 'boolean'],
            'layout.elements.*.title' => ['nullable', 'string', 'max:60'],
            'layout.elements.*.titleFontSize' => ['nullable', 'numeric', 'between:6,200'],
        ];
    }

    /**
     * Normaliza booleanos y valida que las imágenes sean rutas internas.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ((array) $this->input('layout.elements', []) as $index => $element) {
                $src = $element['src'] ?? null;

                if ($src && ! str_starts_with(ltrim(str_replace('\\', '/', $src), '/'), 'socialevents/diplomas/')) {
                    $validator->errors()->add(
                        "layout.elements.$index.src",
                        'La imagen debe ser un archivo subido al módulo de diplomas.'
                    );
                }
            }
        });
    }
}
