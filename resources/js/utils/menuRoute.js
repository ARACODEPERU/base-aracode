/**
 * Crea el enlace de una entrada de menu a partir del nombre de ruta de Ziggy.
 *
 * Ziggy lanza un error ("Ziggy error: route 'x' is not in the route list") cuando
 * el nombre no existe en el listado que el servidor genero al renderizar la
 * pagina. Como los Menu.js de los modulos llaman a route() al cargarse (import
 * estatico desde MenuData.js), un unico nombre desincronizado rompia la carga de
 * todo el layout del panel con "Uncaught Error".
 *
 * Devuelve null cuando la ruta no existe: la entrada del menu queda sin enlace
 * (o se puede filtrar), pero el panel sigue renderizando.
 */
export function menuRoute(name, params = undefined, absolute = false) {
    try {
        return route(name, params, absolute);
    } catch (error) {
        console.warn(
            `[menu] La ruta "${name}" no esta en el listado de Ziggy; la entrada del menu queda sin enlace. ` +
                'Verifica que la ruta exista y refresca la cache de rutas del servidor (php artisan route:clear).',
            error?.message ?? error
        );

        return null;
    }
}

export default menuRoute;
