/**
 * Menú de Gestión Deportiva: ¿quién ve la entrada "Mis pagos"?
 *
 * La vista de pagos es del organizador con cuenta propia: su acceso se resuelve
 * con su organización (OrganizationTopUpController responde 404 cuando el
 * usuario no tiene una). Por eso la fila no se muestra al superadministrador
 * —que en modo deportivo pertenece a la organización histórica y administra
 * desde "Cuentas de organizaciones"— ni fuera del modo deportivo, donde el
 * módulo se sigue presentando como Eventos.
 *
 * La regla vive aquí, con el resto de las utilidades del módulo, para poder
 * probarla sin Vite; los dos sidebars la usan para la fila marcada con
 * `organizationOnly`.
 */
export function puedeVerMisPagos(sportsOrganization = {}, roles = []) {
    const esSuperAdmin = (roles || []).some((role) => ['admin', 'Administrador'].includes(role));

    return Boolean(sportsOrganization?.mode)
        && Boolean(sportsOrganization?.organization_id)
        && Boolean(sportsOrganization?.is_owner)
        && ! esSuperAdmin;
}
