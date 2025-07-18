<?php

namespace Modules\Security\Rules;

use App\Models\Person;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidationPersonUser implements ValidationRule
{
   /**
     * Run the validation rule.
     *
     * @param string $attribute El nombre del atributo que se está validando (ej. 'person_id').
     * @param mixed $value El valor del atributo (el ID de la persona).
     * @param Closure $fail La función de callback para cuando la validación falla.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 1. Si el valor (person_id) es nulo, no hay persona existente a validar,
        //    así que la regla no se aplica y se considera "válida" para este caso.
        //    Esto permite el flujo donde no se envía un person_id y se crea una persona nueva.
        if (is_null($value)) {
            return;
        }

        // 2. Si el person_id NO es nulo, verificamos su existencia y que no tenga usuario.

        // Primero, valida que el ID de persona realmente exista en la tabla 'people'.
        // Aunque puedes poner 'exists:people,id' en el controlador, es buena práctica
        // que la regla personalizada también contemple si el ID es válido antes de buscar el usuario.
        if (!Person::where('id', $value)->exists()) {
            $fail('La persona con el ID proporcionado no existe.');
            return;
        }

        // Luego, verifica si ya existe un usuario con este person_id no nulo.
        $userExists = User::where('person_id', $value)
                          ->whereNotNull('person_id')
                          ->exists();

        // Si existe un usuario con este person_id, la validación falla.
        if ($userExists) {
            $fail('La persona seleccionada ya tiene una cuenta de usuario.');
        }
    }

}
