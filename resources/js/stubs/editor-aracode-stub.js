/**
 * Sustituto de @elmerrodriguez/editor-aracode.
 *
 * vite.config.js resuelve el paquete local a este archivo cuando la carpeta
 * del editor no existe en la máquina. Así el frontend compila y los
 * formularios muestran el editor de reserva en lugar de romperse.
 */
export const editorAracodeDisponible = false;

export default class EditorAracodeNoDisponible {
    constructor() {
        throw new Error(
            'El paquete @elmerrodriguez/editor-aracode no está instalado en este entorno.'
        );
    }
}
