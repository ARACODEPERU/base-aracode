<script setup>
import { ref, watch } from 'vue';
import { Dialog, DialogOverlay, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue';
import IconX from '@/Components/vristo/icon/icon-x.vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    doctorId: {
        type: Number,
        default: null,
    },
    doctorName: {
        type: String,
        default: '',
    },
    // Minutos que dura la confirmacion antes de poder enlazar (opcion de configuracion).
    confirmMinutes: {
        type: Number,
        default: 5,
    },
});

const emit = defineEmits(['close']);

const password = ref('');
const error = ref('');
const busy = ref(false);

// Cada apertura empieza limpia: no se reutiliza una contraseña escrita antes.
watch(
    () => props.open,
    (value) => {
        if (value) {
            password.value = '';
            error.value = '';
            busy.value = false;
        }
    }
);

const close = () => {
    if (!busy.value) {
        emit('close');
    }
};

// Confirma la contraseña en el servidor; si es correcta, lleva a Google.
const submit = async () => {
    error.value = '';
    busy.value = true;

    try {
        const { data } = await axios.post(route('heal_doctor_google_confirm'), {
            doctor_id: props.doctorId,
            password: password.value,
        });

        window.location.href = data.connectUrl;
    } catch (exception) {
        error.value = exception.response?.data?.message || 'No se pudo confirmar. Inténtalo de nuevo.';
        busy.value = false;
    }
};
</script>

<template>
    <TransitionRoot appear :show="open" as="template">
        <Dialog as="div" class="relative z-[52]" @close="close">
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <DialogOverlay class="fixed inset-0 bg-[black]/60" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center px-4 py-8">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel class="panel w-full max-w-lg overflow-hidden rounded-lg border-0 p-0 text-black dark:text-white-dark">
                            <button
                                type="button"
                                class="absolute top-4 text-gray-400 outline-none hover:text-gray-800 ltr:right-4 rtl:left-4 dark:hover:text-gray-600"
                                :disabled="busy"
                                @click="close"
                            >
                                <IconX />
                            </button>

                            <div class="bg-[#fbfbfb] py-3 text-lg font-medium ltr:pl-5 ltr:pr-[50px] rtl:pl-[50px] rtl:pr-5 dark:bg-[#121c2c]">
                                Enlazar mi Google Calendar
                            </div>

                            <form class="space-y-4 p-5" @submit.prevent="submit">
                                <p class="text-sm">
                                    Enlazarás la agenda de <strong>{{ doctorName }}</strong> con <strong>su propia cuenta</strong> de Google Calendar.
                                </p>

                                <!-- Aviso: esto NO es la agenda principal del consultorio. -->
                                <div class="rounded border border-warning/50 bg-warning/10 p-3 text-sm">
                                    <p class="font-semibold text-warning">Esto no es la agenda principal del consultorio.</p>
                                    <p class="mt-1">
                                        Solo las citas de este doctor se escribirán en su calendario. La agenda principal, que ven todos,
                                        no recibirá sus citas por este enlace.
                                    </p>
                                </div>

                                <div>
                                    <label class="mb-1 block font-semibold">Confirma tu contraseña del sistema</label>
                                    <input
                                        v-model="password"
                                        type="password"
                                        autocomplete="current-password"
                                        class="form-input"
                                        required
                                        :disabled="busy"
                                    />
                                    <p v-if="error" class="mt-1 text-sm text-danger">{{ error }}</p>
                                    <p class="mt-1 text-xs text-white-dark">
                                        Es la misma contraseña con la que inicias sesión. Tendrás {{ confirmMinutes }} minutos para terminar la conexión con Google.
                                    </p>
                                </div>

                                <div class="flex justify-end gap-2">
                                    <button type="button" class="btn btn-outline-danger" :disabled="busy" @click="close">Cancelar</button>
                                    <button type="submit" class="btn btn-primary" :disabled="busy || !password">
                                        {{ busy ? 'Confirmando…' : 'Continuar con Google' }}
                                    </button>
                                </div>
                            </form>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
