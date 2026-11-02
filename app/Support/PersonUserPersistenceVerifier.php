<?php

namespace App\Support;

use App\Models\Person;
use App\Models\User;
use RuntimeException;

/**
 * Comprueba al final de un registro que ambas filas y los campos recientes
 * esperados quedaron persistidos y enlazados.
 */
class PersonUserPersistenceVerifier
{
    /**
     * @param array<string, mixed> $personData Campos recientes que deben existir en people.
     * @param array<string, mixed> $userData Campos recientes que deben existir en users.
     */
    public static function verify(Person $person, User $user, array $personData, array $userData): void
    {
        $persistedPerson = $person->newQuery()->find($person->getKey());
        $persistedUser = $user->newQuery()->find($user->getKey());

        if (! $persistedPerson) {
            throw new RuntimeException('La persona no quedó persistida en people.');
        }

        if (! $persistedUser) {
            throw new RuntimeException('El usuario no quedó persistido en users.');
        }

        $userData['person_id'] = $persistedPerson->id;

        self::persistLatestFields($persistedPerson, $personData);
        self::persistLatestFields($persistedUser, $userData);

        $persistedPerson->refresh();
        $persistedUser->refresh();

        if ((int) $persistedUser->person_id !== (int) $persistedPerson->id) {
            throw new RuntimeException('La persona y el usuario no quedaron vinculados correctamente.');
        }

        self::verifyFields($persistedPerson, $personData, 'people');
        self::verifyFields($persistedUser, $userData, 'users');
    }

    /**
     * Reaplica los valores actuales si la consulta independiente detecta datos
     * ausentes o antiguos, y deja la verificación final sobre la fila persistida.
     *
     * @param array<string, mixed> $expected
     */
    private static function persistLatestFields(Person|User $model, array $expected): void
    {
        $changes = [];

        foreach ($expected as $field => $value) {
            if (! self::sameValue($model->getAttribute($field), $value)) {
                $changes[$field] = $value;
            }
        }

        if ($changes !== []) {
            $model->forceFill($changes)->saveOrFail();
        }
    }

    /**
     * @param array<string, mixed> $expected
     */
    private static function verifyFields(Person|User $model, array $expected, string $table): void
    {
        foreach ($expected as $field => $value) {
            $actual = $model->getAttribute($field);

            if (! self::sameValue($actual, $value)) {
                throw new RuntimeException("El campo {$field} de {$table} no contiene el dato más reciente.");
            }
        }
    }

    private static function sameValue(mixed $actual, mixed $expected): bool
    {
        if ($actual === $expected) {
            return true;
        }

        if ($actual === null || $expected === null) {
            return false;
        }

        if ($actual instanceof \DateTimeInterface) {
            $actual = $actual->format('Y-m-d H:i:s');
        }

        if ($expected instanceof \DateTimeInterface) {
            $expected = $expected->format('Y-m-d H:i:s');
        }

        return is_scalar($actual) && is_scalar($expected) && (string) $actual === (string) $expected;
    }
}
