<?php

namespace Modules\CMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CmsLandingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title'              => ['required', 'max:300'],
            'slug'               => ['required', 'max:500'],
            'description_short'  => ['required', 'max:255'],
            'description_long'   => ['nullable'],
            //'main_image'         => ['nullable', 'max:255'],
            'image'              => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'data_related'          => 'nullable|array',
            'data_related.table'    => 'required_with:data_related|string',
            'data_related.items'    => 'required_with:data_related|array|min:1',
            'data_related2'      => ['nullable'],
            'additional_field1'  => ['nullable'],
            'additional_field2'  => ['nullable'],
            'additional_field3'  => ['nullable'],
            'date_start'         => ['nullable', 'date'],
            'date_end'           => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'title.required'             => 'El título es obligatorio.',
            'title.string'               => 'El título debe ser un texto.',
            'title.max'                  => 'El título no puede superar los 300 caracteres.',

            'slug.required'              => 'El slug es obligatorio.',
            'slug.string'                => 'El slug debe ser un texto.',
            'slug.max'                   => 'El slug no puede superar los 500 caracteres.',

            'description_short.required' => 'La descripción corta es obligatoria.',
            'description_short.string'   => 'La descripción corta debe ser un texto.',
            'description_short.max'      => 'La descripción corta no puede superar los 255 caracteres.',

            'description_long.string'    => 'La descripción larga debe ser un texto.',

            'main_image.string'          => 'La ruta de la imagen principal debe ser un texto.',
            'main_image.max'             => 'La ruta de la imagen principal no puede superar los 255 caracteres.',

            'data_related.array'         => 'El campo data_related debe ser un objeto válido.',
            'data_related.table.required_with' => 'El campo table es obligatorio dentro de data_related.',
            'data_related.items.required_with' => 'Debe seleccionar al menos un ítem en data_related.',
            'data_related.items.array'   => 'El campo items debe ser una lista válida.',
            'data_related.items.min'     => 'Debe incluir al menos un ítem en la lista.',
            'data_related2.json'         => 'Los datos relacionados 2 deben estar en formato JSON.',

            'additional_field1.string'   => 'El campo título del formulario debe ser un texto.',
            'additional_field2.string'   => 'El campo adicional 2 debe ser un texto.',
            'additional_field3.string'   => 'El campo adicional 3 debe ser un texto.',

            'date_start.date'            => 'La fecha de inicio no tiene un formato válido.',
            'date_end.date'              => 'La fecha de fin no tiene un formato válido.',

            'image.image'           => 'El archivo debe ser una imagen.',
            'image.mimes'           => 'La imagen debe ser en formato JPG o PNG.',
            'image.max'             => 'La imagen no puede superar los 2MB.',
        ];
    }
}
