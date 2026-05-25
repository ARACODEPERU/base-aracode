<script setup>
import { onMounted, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import IconX from '@/Components/vristo/icon/icon-x.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const show = ref(false);
const localItems = ref([]);
const signingId = ref(null);

const formatDate = (value) => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const localDateTimeInputValue = (value = null) => {
    const date = value ? new Date(value) : new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const signAttention = (item) => {
    const currentDateTime = localDateTimeInputValue();
    signingId.value = item.id;

    Swal.fire({
        title: 'Firmar atención',
        html: `
            <div class="text-left space-y-3">
                <div class="rounded border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between">
                        <div class="font-semibold">${item.patient_name || 'Paciente'}</div>
                        <span class="text-xs text-white-dark">${formatDate(item.attention_at)}</span>
                    </div>
                    <div class="mt-2 text-sm">
                        <span class="font-medium">Dr. </span>${item.doctor_name || 'Sin doctor'}
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Hora de firma</label>
                    <input id="sign_signed_at" type="datetime-local" class="swal2-input" value="${currentDateTime}">
                    <p class="mt-1 text-xs text-white-dark">Debe ser posterior al inicio de la atención.</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">PIN del doctor</label>
                    <input id="sign_pin" type="password" maxlength="4" inputmode="numeric" class="swal2-input" placeholder="4 números">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Firmar atención',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'sweet-alerts',
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
        },
        buttonsStyling: false,
        reverseButtons: true,
        preConfirm: () => {
            const signedAt = document.getElementById('sign_signed_at')?.value;
            const pin = document.getElementById('sign_pin')?.value;

            if (!signedAt) {
                Swal.showValidationMessage('Ingresa la hora de firma.');
                return false;
            }

            if (!/^\d{4}$/.test(pin || '')) {
                Swal.showValidationMessage('El PIN debe tener 4 números.');
                return false;
            }

            return axios.post(route('heal_attentions_sign', item.id), {
                pin,
                signed_at: signedAt,
            })
                .then((response) => response.data)
                .catch((error) => {
                    signingId.value = null;
                    const errors = error.response?.data?.errors || {};
                    const message = Object.values(errors).flat().join(' ');

                    Swal.showValidationMessage(message || error.response?.data?.message || 'No se pudo firmar la atención.');
                    return false;
                });
        },
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        localItems.value = localItems.value.filter((i) => i.id !== item.id);
        signingId.value = null;

        if (!localItems.value.length) {
            show.value = false;
        }

        Swal.fire({
            icon: 'success',
            title: 'Atención firmada',
            text: 'El documento quedó bloqueado para modificaciones.',
            padding: '2em',
            customClass: 'sweet-alerts',
        }).then(() => {
            router.reload();
        });
    });
};

onMounted(() => {
    localItems.value = [...props.items];
    show.value = localItems.value.length > 0;
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[999] flex items-center justify-center bg-black/60 px-4 py-6">
        <div class="panel w-full max-w-3xl overflow-hidden rounded-lg border-0 p-0">
            <div class="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
                <div>
                    <h3 class="text-lg font-semibold">Atenciones pendientes de firma</h3>
                    <p class="text-sm text-white-dark">Revisa y firma las atenciones que aún están abiertas.</p>
                </div>
                <button type="button" class="text-white-dark hover:text-danger" @click="show = false">
                    <IconX class="h-5 w-5" />
                </button>
            </div>

            <div class="max-h-[60vh] overflow-y-auto p-5">
                <div class="space-y-3">
                    <div
                        v-for="item in localItems"
                        :key="item.id"
                        class="flex flex-col gap-3 rounded border border-[#e0e6ed] p-3 dark:border-[#1b2e4b] sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <div class="font-semibold">{{ item.patient_name || 'Paciente no registrado' }}</div>
                            <div class="mt-1 text-sm text-white-dark">
                                {{ formatDate(item.attention_at) }} · {{ item.doctor_name || 'Sin doctor' }}
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="btn btn-danger btn-sm whitespace-nowrap"
                                :class="{ 'opacity-50 cursor-not-allowed': signingId === item.id }"
                                :disabled="signingId === item.id"
                                @click="signAttention(item)"
                            >
                                <svg v-if="signingId === item.id" class="inline-block h-4 w-4 animate-spin ltr:mr-2 rtl:ml-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" stroke-dasharray="31.4 31.4" stroke-linecap="round" />
                                </svg>
                                {{ signingId === item.id ? 'Firmando...' : 'Firmar atención' }}
                            </button>
                            <Link :href="route('heal_attentions_edit', item.id)" class="btn btn-primary btn-sm whitespace-nowrap">
                                Ver atención
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end border-t border-[#e0e6ed] px-5 py-4 dark:border-[#1b2e4b]">
                <button type="button" class="btn btn-outline-dark" @click="show = false">Cerrar</button>
            </div>
        </div>
    </div>
</template>
