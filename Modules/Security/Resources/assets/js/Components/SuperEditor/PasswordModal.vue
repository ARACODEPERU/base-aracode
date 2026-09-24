<script setup>
/**
 * Confirmación de contraseña del Modo Super Editor.
 *
 * Solo hay un caso que la pida: aplicar el borrador al salir del modo (entrar
 * no pide nada y salir sin cambios tampoco). Se implementa aparte del
 * ConfirmsPassword de Breeze del proyecto porque ese componente llama a una
 * ruta inexistente (password.confirmation) y su POST redirige a HOME en vez de
 * responder JSON. Aquí se confirma contra /security/super-editor/exit, que
 * valida con Hash::check y deja constancia de los intentos fallidos.
 */
import { nextTick, ref, watch } from 'vue';
import { useSuperEditorStore } from 'Modules/Security/Resources/assets/js/stores/superEditor';

const store = useSuperEditorStore();

const password = ref('');
const input = ref(null);

const COPY = {
    title: 'Salir y aplicar los cambios',
    description:
        'Vas a escribir en los permisos: confirma tu contraseña para aplicar el borrador. Los cambios se guardan todos juntos y quedan registrados en el historial.',
    button: 'Aplicar y salir',
    icon: '💾',
};

watch(
    () => store.prompt.open,
    (open) => {
        if (!open) {
            password.value = '';

            return;
        }

        nextTick(() => input.value?.focus());
    }
);

// Si la contraseña falla, el modal sigue abierto: se limpia el campo y se
// devuelve el foco para que el reintento sea inmediato.
watch(
    () => store.prompt.error,
    (error) => {
        if (!error || !store.prompt.open) {
            return;
        }

        password.value = '';
        nextTick(() => input.value?.focus());
    }
);

const submit = () => {
    if (!password.value || store.prompt.busy) {
        return;
    }

    store.submitPrompt(password.value);
};

const cancel = () => {
    store.closePrompt();
};
</script>

<template>
    <div
        v-if="store.prompt.open"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 px-4"
        @keydown.esc="cancel"
    >
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-800">
            <div class="px-6 py-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-blue-600 dark:text-blue-300">
                    Modo Super Editor
                </p>
                <h3 class="mt-1 text-lg font-semibold text-slate-800 dark:text-slate-100">
                    {{ COPY.icon }} {{ COPY.title }}
                </h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-300">
                    {{ COPY.description }}
                </p>

                <label class="mt-4 block text-xs font-semibold text-slate-500 dark:text-slate-300">
                    Tu contraseña
                </label>
                <input
                    ref="input"
                    v-model="password"
                    type="password"
                    autocomplete="current-password"
                    class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                    placeholder="••••••••"
                    @keyup.enter="submit"
                />

                <p
                    v-if="store.prompt.error"
                    class="mt-2 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:bg-rose-500/10 dark:text-rose-200"
                >
                    {{ store.prompt.error }}
                </p>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4 dark:border-slate-700">
                <button
                    type="button"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-500 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700"
                    @click="cancel"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50"
                    :disabled="!password || store.prompt.busy"
                    @click="submit"
                >
                    {{ store.prompt.busy ? 'Comprobando…' : COPY.button }}
                </button>
            </div>
        </div>
    </div>
</template>
