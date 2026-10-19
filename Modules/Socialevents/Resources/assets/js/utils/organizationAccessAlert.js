/**
 * Aviso de acceso vencido del organizador.
 *
 * Vencida la prueba (o el acceso pagado) la organización queda en solo lectura:
 * sus pantallas siguen abriéndose, pero el servidor responde 403 a cualquier
 * registro o edición. Este aviso es el que lo explica y lleva a "Mis pagos".
 *
 * La regla vive aquí, con el resto de las utilidades del módulo, para poder
 * probarla sin Vite.
 */
export function debeAvisarAccesoVencido(sportsOrganization = {}, roles = [], url = '') {
    const esSuperAdmin = (roles || []).some((role) => ['admin', 'Administrador'].includes(role));

    // El superadministrador no se ve afectado por el vencimiento de una cuenta.
    if (esSuperAdmin || ! sportsOrganization?.mode) return false;

    // Las cuentas bloqueadas no pueden leer el módulo, pero conservan acceso a
    // "Mis pagos" para regularizarse. En ese caso el aviso los lleva directamente.
    const estaBloqueada = sportsOrganization.status === 'suspended';
    const vencidaSoloLectura = sportsOrganization.can_read === true && sportsOrganization.can_write === false;
    if (! estaBloqueada && ! vencidaSoloLectura) return false;

    // En "Mis pagos" la página ya explica el estado y trae el formulario de pago.
    return ! String(url || '').startsWith('/socialevents/organizations/topup');
}
