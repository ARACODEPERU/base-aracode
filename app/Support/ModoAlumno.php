<?php

namespace App\Support;

/*
|--------------------------------------------------------------------------
| Modo alumno vs. panel administrativo
|--------------------------------------------------------------------------
|
| Una misma cuenta puede tener el rol 'Alumno' y además un rol de trabajo
| ('admin', 'Docente', ...). Antes bastaba con tener 'Alumno' para que todo el
| sistema mostrara el campus del alumno, así que un administrador que también
| estudiaba entraba a la vista de alumno y perdía el panel.
|
| La regla ahora es por prioridad: si la cuenta tiene cualquier rol de panel,
| la sesión entra al panel administrativo. El campus del alumno se muestra solo
| cuando 'Alumno' es el único rol de peso de la cuenta.
|
| Para añadir un rol de trabajo nuevo basta con incluirlo en ROLES_PANEL; la
| lista es la única fuente de verdad y la comparten el backend (este archivo) y
| el frontend (resources/js/utils/modoAlumno.js).
|
*/

class ModoAlumno
{
    /**
     * Roles que abren el panel administrativo y, por lo tanto, tienen prioridad
     * sobre el campus del alumno.
     *
     * @var array<int, string>
     */
    public const ROLES_PANEL = [
        'admin',
        'webAdmin',
        'Administrador',
        'Ventas',
        'Vendedor',
        'Docente',
        'Contabilidad',
        'Doctor',
        'BiblioAdmin',
        'Automatizaciones',
    ];

    /**
     * Indica si la sesión debe ver el campus del alumno.
     *
     * Devuelve false cuando no hay usuario, cuando no tiene el rol 'Alumno' o
     * cuando tiene además un rol de panel (el panel gana).
     */
    public static function activo($user): bool
    {
        if (! $user || ! method_exists($user, 'hasRole') || ! method_exists($user, 'hasAnyRole')) {
            return false;
        }

        if (! $user->hasRole('Alumno')) {
            return false;
        }

        return ! $user->hasAnyRole(self::ROLES_PANEL);
    }
}
