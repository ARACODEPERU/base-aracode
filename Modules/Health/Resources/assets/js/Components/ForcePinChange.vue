<script setup>
import { onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const page = usePage();

const showModal = ref(false);

const checkAndForcePinChange = () => {
    const currentDoctor = page.props.health?.currentDoctor;

    if (!currentDoctor) {
        return;
    }

    // Only force if the doctor has the default PIN (has_custom_pin === false)
    if (currentDoctor.has_custom_pin) {
        return;
    }

    showModal.value = true;

    Swal.fire({
        title: 'Cambio obligatorio de PIN',
        width: 560,
        html: `
            <div class="text-left space-y-4">
                <div class="rounded-md border border-warning/30 bg-warning/10 px-4 py-3 text-warning">
                    <div class="text-sm font-semibold">${currentDoctor.name || 'Doctor'}</div>
                    <div class="mt-1 text-xs opacity-80">
                        Por seguridad, debes cambiar tu PIN de firma antes de continuar.
                        El PIN por defecto <strong>1234</strong> no es seguro.
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Correo electrónico
                    </label>
                    <input
                        id="fp_email"
                        type="email"
                        class="form-input w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                        placeholder="tu@correo.com"
                        autocomplete="email"
                    />
                    <p class="mt-1 text-xs text-white-dark">Ingresa tu correo del sistema para verificar tu identidad.</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Contraseña
                    </label>
                    <input
                        id="fp_password"
                        type="password"
                        class="form-input w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                        placeholder="••••••••"
                        autocomplete="current-password"
                    />
                </div>

                <hr class="border-slate-200 dark:border-slate-700" />

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Nuevo PIN (4 dígitos)
                    </label>
                    <input
                        id="fp_new_pin"
                        type="password"
                        maxlength="4"
                        inputmode="numeric"
                        class="form-input w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                        placeholder="Nuevo PIN"
                        autocomplete="off"
                    />
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Confirmar nuevo PIN
                    </label>
                    <input
                        id="fp_new_pin_confirmation"
                        type="password"
                        maxlength="4"
                        inputmode="numeric"
                        class="form-input w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                        placeholder="Repite el PIN"
                        autocomplete="off"
                    />
                </div>
            </div>
        `,
        showCancelButton: false,
        showCloseButton: false,
        allowOutsideClick: false,
        allowEscapeKey: false,
        confirmButtonText: 'Cambiar PIN',
        customClass: {
            popup: 'sweet-alerts',
            confirmButton: 'btn btn-primary',
        },
        buttonsStyling: false,
        padding: '2em',
        preConfirm: () => {
            const email = document.getElementById('fp_email')?.value?.trim();
            const password = document.getElementById('fp_password')?.value;
            const newPin = document.getElementById('fp_new_pin')?.value;
            const confirmation = document.getElementById('fp_new_pin_confirmation')?.value;

            if (!email) {
                Swal.showValidationMessage('Ingresa tu correo electrónico.');
                return false;
            }

            if (!password) {
                Swal.showValidationMessage('Ingresa tu contraseña.');
                return false;
            }

            if (!/^\d{4}$/.test(newPin || '')) {
                Swal.showValidationMessage('El nuevo PIN debe tener exactamente 4 dígitos.');
                return false;
            }

            if (newPin !== confirmation) {
                Swal.showValidationMessage('El nuevo PIN y su confirmación no coinciden.');
                return false;
            }

            return axios.post(route('heal_doctors_pin_update'), {
                email,
                password,
                new_pin: newPin,
                new_pin_confirmation: confirmation,
            })
                .then((response) => response.data)
                .catch((error) => {
                    const errors = error.response?.data?.errors || {};
                    const message = Object.values(errors).flat().join(' ');
                    Swal.showValidationMessage(message || 'No se pudo cambiar el PIN.');
                    return false;
                });
        },
    }).then((result) => {
        if (result.isConfirmed) {
            showModal.value = false;

            // Update the shared state so we don't show the modal again
            if (page.props.health?.currentDoctor) {
                page.props.health.currentDoctor.has_custom_pin = true;
            }

            Swal.fire({
                icon: 'success',
                title: 'PIN cambiado exitosamente',
                text: 'Ya puedes usar tu nuevo PIN de firma.',
                customClass: {
                    popup: 'sweet-alerts',
                    confirmButton: 'btn btn-success',
                },
                buttonsStyling: false,
                padding: '2em',
            });
        }
    });
};

onMounted(() => {
    checkAndForcePinChange();
});
</script>

<template>
    <div />
</template>
