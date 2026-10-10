import { onBeforeUnmount, onMounted, ref, unref } from 'vue';

/**
 * Lee los códigos que "teclea" una pistola lectora en modo HID.
 *
 * La pistola no es un dispositivo que el navegador conozca: escribe los
 * caracteres como si alguien los tecleara a toda velocidad y cierra con Enter
 * (algunas con Tab). Por eso el código se arma con `keydown` a nivel de
 * documento —sin exigir foco en ningún campo, que es lo que falla en la puerta—
 * y se descarta solo si pasan más de `gapMs` sin teclas.
 *
 * Si el foco está en un input, el evento se ignora: ese campo tiene su propio
 * envío y, si no, el código entraría dos veces.
 *
 * @param {(code: string) => void} onCode   qué hacer con el código leído
 * @param {object}                 options
 * @param {number}                 options.gapMs      silencio que cierra el buffer
 * @param {number}                 options.minLength  largo mínimo para aceptar el código
 * @param {number}                 options.maxLength  largo máximo del buffer
 * @param {import('vue').Ref<boolean>|Function} options.isEnabled  si está escuchando
 */
export function useHidScanner(onCode, options = {}) {
    const gapMs = options.gapMs ?? 120;
    const minLength = options.minLength ?? 4;
    const maxLength = options.maxLength ?? 40;
    const isEnabled = options.isEnabled ?? (() => true);

    /** Lo que se lleva leído del código actual (visible para depurar en pantalla). */
    const buffer = ref('');
    let lastKeyAt = 0;

    const enabled = () => {
        const value = isEnabled;

        return typeof value === 'function' ? value() : unref(value);
    };

    const reset = () => {
        buffer.value = '';
        lastKeyAt = 0;
    };

    const onKeydown = (event) => {
        if (! enabled()) {
            return;
        }

        const target = event.target;
        const tagName = target?.tagName;

        // El operador está escribiendo en un campo (código a mano o búsqueda).
        if (tagName === 'INPUT' || tagName === 'TEXTAREA' || tagName === 'SELECT' || target?.isContentEditable) {
            return;
        }

        const key = event.key;

        if (key === 'Enter' || key === 'Tab') {
            const code = buffer.value.trim();

            if (code.length >= minLength) {
                event.preventDefault();
                onCode(code);
            }

            reset();

            return;
        }

        // Solo caracteres imprimibles: Shift, flechas o F2 no son parte del código.
        if (key.length !== 1 || event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        const now = Date.now();

        if (now - lastKeyAt > gapMs) {
            buffer.value = '';
        }

        lastKeyAt = now;

        if (buffer.value.length < maxLength) {
            buffer.value += key;
        }
    };

    onMounted(() => document.addEventListener('keydown', onKeydown, true));
    onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown, true));

    return { buffer, reset };
}
