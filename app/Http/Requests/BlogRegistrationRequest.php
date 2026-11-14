<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validacion del alta de lectores desde el blog.
 *
 * El telefono y el pais son obligatorios, igual que en el resto de formularios
 * de registro del sistema; la contrasena se confirma y el alta exige aceptar
 * los terminos y la politica de privacidad.
 */
class BlogRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'email' => is_string($this->email) ? strtolower(trim($this->email)) : $this->email,
            'telefono' => is_string($this->telefono) ? trim($this->telefono) : $this->telefono,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            // Mismo tope que users.telefono (20) y solo caracteres de un numero real.
            'telefono' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9][0-9\s().-]{5,19}$/'],
            'country_id' => ['required', 'integer', Rule::exists('countries', 'id')],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'acepto' => ['required', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'email.required' => 'El correo electronico es obligatorio.',
            'email.email' => 'Debes ingresar un correo electronico valido.',
            'email.unique' => 'Este correo ya tiene una cuenta. Inicia sesion para continuar.',
            'telefono.required' => 'El numero de telefono es obligatorio.',
            'telefono.max' => 'El numero de telefono no puede superar los 20 caracteres.',
            'telefono.regex' => 'Ingresa un numero de telefono valido (solo numeros, +, espacios y guiones).',
            'country_id.required' => 'Selecciona tu pais.',
            'country_id.exists' => 'El pais seleccionado no es valido.',
            'password.required' => 'La contrasena es obligatoria.',
            'password.confirmed' => 'Las contrasenas no coinciden.',
            'password.min' => 'La contrasena debe tener al menos 8 caracteres.',
            'acepto.required' => 'Debes aceptar los Terminos y Condiciones y la Politica de Privacidad.',
            'acepto.accepted' => 'Debes aceptar los Terminos y Condiciones y la Politica de Privacidad.',
        ];
    }
}
