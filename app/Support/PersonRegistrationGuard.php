<?php

namespace App\Support;

use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Verificacion final de los registros de personas.
 *
 * En los flujos que registran a una persona en las tablas people y users, al final
 * del proceso se llama a este servicio para comprobar que ambas filas existan y que
 * tengan los datos recientes enviados en el formulario. Si falta alguna fila o quedo
 * desactualizada, la crea/actualiza y deja un aviso en el log para poder rastrear el
 * incidente.
 */
class PersonRegistrationGuard
{
    /**
     * Campos de users que se pueden refrescar desde el registro
     * (password y api_token nunca se sobrescriben aqui).
     */
    private const USER_REFRESHABLE = [
        'name',
        'email',
        'person_id',
        'status',
    ];

    /**
     * Verifica y completa el registro de una persona en people y users.
     *
     * @param  Person|null  $person  Persona recien registrada (si el flujo ya la obtuvo).
     * @param  array  $personData  Datos recientes de la persona (los del formulario).
     * @param  array  $userData  Datos recientes del usuario (name, email, password ya hasheado, local_id, status).
     * @param  User|null  $user  Usuario recien registrado (si el flujo ya lo obtuvo).
     * @return array{person: Person, user: User, created_person: bool, created_user: bool, updated_fields: array}
     */
    public function ensure(?Person $person = null, array $personData = [], array $userData = [], ?User $user = null): array
    {
        $updatedFields = [];

        $person = $person ?: $this->findPerson($personData);
        $createdPerson = false;

        if (! $person) {
            $person = Person::create($this->filterPersonPayload($personData));
            $createdPerson = true;
        } else {
            $updatedFields = $this->refreshPerson($person, $personData);
        }

        $user = $user ?: $this->findUser($person, $userData);
        $createdUser = false;

        if (! $user) {
            $user = User::create($this->userPayload($person, $userData));
            $createdUser = true;
        } else {
            $updatedFields = array_merge($updatedFields, $this->refreshUser($user, $person, $userData));
        }

        if ($createdPerson || $createdUser) {
            Log::warning('Registro de persona incompleto: se completo al finalizar el proceso.', [
                'person_id' => $person->id,
                'user_id' => $user->id,
                'created_person' => $createdPerson,
                'created_user' => $createdUser,
                'updated_fields' => $updatedFields,
            ]);
        }

        return [
            'person' => $person,
            'user' => $user,
            'created_person' => $createdPerson,
            'created_user' => $createdUser,
            'updated_fields' => $updatedFields,
        ];
    }

    /**
     * Busca la persona por documento (tipo + numero) o, en su defecto, por correo.
     */
    public function findPerson(array $personData): ?Person
    {
        $number = isset($personData['number']) ? trim((string) $personData['number']) : '';
        $documentTypeId = $personData['document_type_id'] ?? null;

        if ($number !== '') {
            $person = Person::query()
                ->where('number', $number)
                ->when($documentTypeId !== null && $documentTypeId !== '', function ($query) use ($documentTypeId) {
                    $query->where('document_type_id', $documentTypeId);
                })
                ->first();

            if ($person) {
                return $person;
            }
        }

        return $this->findPersonByEmail($personData['email'] ?? null);
    }

    private function findPersonByEmail(?string $email): ?Person
    {
        $email = trim((string) $email);

        if ($email === '') {
            return null;
        }

        return Person::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();
    }

    /**
     * Busca el usuario vinculado a la persona o, si aun no esta vinculado, por correo.
     */
    public function findUser(Person $person, array $userData = []): ?User
    {
        $user = User::where('person_id', $person->id)->first();

        if ($user) {
            return $user;
        }

        $email = trim((string) ($userData['email'] ?? $person->email ?? ''));

        if ($email === '') {
            return null;
        }

        return User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();
    }

    /**
     * Actualiza la persona con los datos recientes del formulario (solo valores enviados).
     *
     * @return array Campos que cambiaron.
     */
    private function refreshPerson(Person $person, array $personData): array
    {
        $changed = [];

        foreach ($this->filterPersonPayload($personData) as $field => $value) {
            if ((string) $person->getAttribute($field) === (string) $value) {
                continue;
            }

            $person->setAttribute($field, $value);
            $changed[] = $field;
        }

        if ($changed) {
            $person->save();
        }

        return $changed;
    }

    /**
     * Completa el usuario: lo vincula a la persona si estaba suelto y refresca nombre/correo.
     *
     * @return array Campos que cambiaron.
     */
    private function refreshUser(User $user, Person $person, array $userData): array
    {
        $changed = [];

        foreach (self::USER_REFRESHABLE as $field) {
            $value = $field === 'person_id'
                ? $person->id
                : ($userData[$field] ?? null);

            if ($value === null || $value === '') {
                continue;
            }

            // El correo del usuario no se pisa si otra cuenta ya lo usa.
            if ($field === 'email' && $this->emailTakenByAnotherUser($user, (string) $value)) {
                continue;
            }

            $current = $field === 'status'
                ? (bool) $user->getAttribute($field)
                : (string) $user->getAttribute($field);

            $comparable = $field === 'status' ? (bool) $value : (string) $value;

            if ($current === $comparable) {
                continue;
            }

            $user->setAttribute($field, $value);
            $changed[] = $field;
        }

        if ($changed) {
            $user->save();
        }

        return $changed;
    }

    private function emailTakenByAnotherUser(User $user, string $email): bool
    {
        return User::query()
            ->where('id', '<>', $user->id)
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
            ->exists();
    }

    /**
     * Datos de la persona que se pueden guardar/refrescar.
     */
    private function filterPersonPayload(array $personData): array
    {
        $fillable = (new Person)->getFillable();
        $payload = [];

        foreach ($fillable as $field) {
            if (! array_key_exists($field, $personData)) {
                continue;
            }

            $value = $personData[$field];

            if ($value === null || $value === '') {
                continue;
            }

            $payload[$field] = $value;
        }

        return $payload;
    }

    /**
     * Datos minimos para crear el usuario cuando el flujo no lo creo.
     */
    private function userPayload(Person $person, array $userData): array
    {
        $email = trim((string) ($userData['email'] ?? $person->email ?? ''));

        if ($email === '') {
            $email = 'persona'.$person->id.'@sistema.local';
        }

        $password = $userData['password'] ?? Hash::make((string) ($person->number ?: Str::random(16)));

        return array_merge([
            'name' => $userData['name'] ?? $person->short_name ?? $person->full_name ?? $person->names ?? $email,
            'email' => $email,
            'password' => $password,
            'local_id' => $userData['local_id'] ?? 1,
            'person_id' => $person->id,
            'status' => $userData['status'] ?? true,
        ], array_intersect_key($userData, array_flip(['name', 'email', 'password', 'local_id', 'person_id', 'status'])));
    }
}
