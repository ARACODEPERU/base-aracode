/*
|--------------------------------------------------------------------------
| Modo alumno vs. panel administrativo
|--------------------------------------------------------------------------
|
| Misma regla que App\Support\ModoAlumno en el backend: si la cuenta tiene
| 'Alumno' y además un rol de panel, gana el panel. Antes bastaba con tener
| 'Alumno' para que el layout mostrara el sidebar y el header del alumno, así
| que un administrador que también estudiaba solo veía la vista de alumno.
|
| Si se agrega un rol de trabajo nuevo, se añade aquí y en el helper de PHP.
|
*/

/** Roles que abren el panel administrativo y tienen prioridad sobre el alumno. */
export const ROLES_PANEL = [
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
 * Nombres de rol a partir de las dos formas en que llegan en la app:
 * cadenas (auth.roles) o filas de Spatie (auth.user.roles: [{ name }]).
 */
export const nombresDeRol = (roles = []) =>
    (roles || [])
        .map((rol) => (typeof rol === 'string' ? rol : rol?.name))
        .filter(Boolean);

/**
 * Indica si la sesión debe ver el campus del alumno.
 *
 * @param {Array<string|{name: string}>} roles
 * @returns {boolean}
 */
export const esModoAlumno = (roles = []) => {
    const nombres = nombresDeRol(roles);

    if (!nombres.includes('Alumno')) {
        return false;
    }

    return !nombres.some((nombre) => ROLES_PANEL.includes(nombre));
};
