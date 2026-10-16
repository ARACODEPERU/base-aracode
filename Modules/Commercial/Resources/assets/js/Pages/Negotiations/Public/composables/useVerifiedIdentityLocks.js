import { ref } from "vue";

/**
 * Campos que quedan de solo lectura porque su valor lo trajo una consulta verificada:
 * RENIEC, SUNAT, la ficha de la busqueda interna o la cuenta de un correo registrado.
 *
 * El bloqueo es por campo y no por bloque completo: solo se marca el input que la
 * consulta realmente lleno. Si la consulta no devuelve datos, el campo sigue editable
 * para que el cliente lo escriba a mano.
 *
 * Quien bloquea guarda la lista que le devuelve `lockFields` y la libera con
 * `unlockFields` cuando el dato deja de valer (cambio de numero de documento, nueva
 * consulta, etc.).
 */
export function useVerifiedIdentityLocks() {
    const lockedFields = ref({});

    /**
     * Marca como no editables los campos indicados.
     *
     * @param  {string[]} fields Campos que la consulta llego a llenar.
     * @return {string[]}        Campos que quedaron bloqueados (para poder liberarlos luego).
     */
    const lockFields = (fields) => {
        const bloqueados = (fields || []).filter(Boolean);

        if (bloqueados.length === 0) {
            return [];
        }

        lockedFields.value = bloqueados.reduce(
            (acc, field) => ({ ...acc, [field]: true }),
            lockedFields.value
        );

        return bloqueados;
    };

    /** Devuelve a editable los campos indicados. */
    const unlockFields = (fields) => {
        if (!fields || fields.length === 0) {
            return;
        }

        const next = { ...lockedFields.value };

        fields.forEach((field) => {
            delete next[field];
        });

        lockedFields.value = next;
    };

    /** Indica si el input debe mostrarse de solo lectura. */
    const isLocked = (field) => Boolean(lockedFields.value[field]);

    return { lockedFields, lockFields, unlockFields, isLocked };
}
