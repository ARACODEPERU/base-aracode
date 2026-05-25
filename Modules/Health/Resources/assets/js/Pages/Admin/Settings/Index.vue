<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const extra = computed(() => props.settings?.settings || {});

const establishmentLogo = ref(props.settings?.establishment_logo ? `/storage/${props.settings.establishment_logo}` : null);
const uploadingLogo = ref(false);

const workingHours = ref(
    props.settings?.working_hours?.length
        ? props.settings.working_hours.map((r) => ({ ...r }))
        : [{ start: '08:00', end: '14:00' }, { start: '16:00', end: '20:00' }]
);

const form = useForm({
    establishment_name: props.settings?.establishment_name || 'Mi Consultorio',
    representative: props.settings?.representative || '',
    notification_email: props.settings?.notification_email || '',
    phone: props.settings?.phone || '',
    address: props.settings?.address || '',
    // Extra settings stored in JSON
    primary_color: extra.value?.primary_color || '#4361ee',
    secondary_color: extra.value?.secondary_color || '#805dca',
    ruc: extra.value?.ruc || '',
    print_footer: extra.value?.print_footer || '',
    recipe_template: extra.value?.recipe_template || '',
});

const addTimeRange = () => {
    workingHours.value.push({ start: '08:00', end: '17:00' });
};

const removeTimeRange = (index) => {
    if (workingHours.value.length <= 1) {
        Swal.fire({
            icon: 'warning',
            title: 'Debe haber al menos un rango',
            text: 'Se requiere al menos un rango de horario de atención.',
            customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-warning' },
            buttonsStyling: false,
        });
        return;
    }
    workingHours.value.splice(index, 1);
};

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            working_hours: workingHours.value,
        }))
        .post(route('heal_settings_update'), {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Configuración guardada',
                    text: 'Los cambios se aplicarán en toda la aplicación.',
                    customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-success' },
                    buttonsStyling: false,
                });
            },
            onError: (errors) => {
                const message = Object.values(errors).flat().join(' ');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message || 'No se pudo guardar la configuración.',
                    customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-danger' },
                    buttonsStyling: false,
                });
            },
        });
};

const handleLogoUpload = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    uploadingLogo.value = true;
    const formData = new FormData();
    formData.append('logo', file);

    axios.post(route('heal_settings_upload_logo'), formData)
        .then((response) => {
            establishmentLogo.value = response.data.logo_url;
            Swal.fire({
                icon: 'success',
                title: 'Logo actualizado',
                timer: 2000,
                showConfirmButton: false,
                customClass: { popup: 'sweet-alerts' },
                buttonsStyling: false,
            });
        })
        .catch((error) => {
            const message = error.response?.data?.message || 'No se pudo subir el logo.';
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-danger' },
                buttonsStyling: false,
            });
        })
        .finally(() => {
            uploadingLogo.value = false;
        });
};

const removeLogo = () => {
    axios.post(route('heal_settings_delete_logo'))
        .then(() => {
            establishmentLogo.value = null;
        })
        .catch((error) => {
            const message = error.response?.data?.message || 'No se pudo eliminar el logo.';
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                customClass: { popup: 'sweet-alerts', confirmButton: 'btn btn-danger' },
                buttonsStyling: false,
            });
        });
};

const hoursLabel = computed(() => {
    return workingHours.value
        .map((r) => `${r.start} - ${r.end}`)
        .join(' y ');
});

const canSave = computed(() => {
    return workingHours.value.every((r) => r.start && r.end && r.start < r.end);
});
</script>

<template>
    <AppLayout title="Configuración">
        <Navigation :routeModule="route('health_dashboard')" :titleModule="'Salud'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Configuración</span>
            </li>
        </Navigation>

        <div class="pt-5 space-y-5">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl">Configuración del Establecimiento</h2>
                    <p class="text-sm text-white-dark">
                        Administra la configuración general del módulo Salud.
                        <span class="text-warning">Solo visible para administradores.</span>
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <!-- Left: Establishment Info + Extra Settings -->
                    <div class="space-y-4 lg:col-span-2">
                        <!-- Establishment Name -->
                        <div class="panel">
                            <div class="mb-4 font-semibold">Información del Establecimiento</div>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-semibold">Nombre del Establecimiento</label>
                                    <input v-model="form.establishment_name" type="text" class="form-input" placeholder="Ej: Centro Médico Salud" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-semibold">RUC</label>
                                    <input v-model="form.ruc" type="text" class="form-input" placeholder="Ej: 20123456789" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-semibold">Correo para notificaciones</label>
                                    <input v-model="form.notification_email" type="email" class="form-input" placeholder="Ej: notificaciones@clinica.com" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-semibold">Representante</label>
                                    <input v-model="form.representative" type="text" class="form-input" placeholder="Nombre del representante legal" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-semibold">Teléfono</label>
                                    <input v-model="form.phone" type="text" class="form-input" placeholder="Ej: +51 999 888 777" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm font-semibold">Dirección</label>
                                    <textarea v-model="form.address" rows="2" class="form-textarea" placeholder="Dirección del establecimiento"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Working Hours -->
                        <div class="panel">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <div class="font-semibold">Horarios de Atención</div>
                                    <p class="text-xs text-white-dark">
                                        Define los rangos horarios en que se atiende normalmente.
                                        Las citas fuera de estos horarios se marcarán como "Fuera de hora" en la agenda,
                                        pero aún se pueden agendar. No afecta atenciones de emergencia.
                                    </p>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" @click="addTimeRange">
                                    + Agregar rango
                                </button>
                            </div>

                            <div class="space-y-3">
                                <div
                                    v-for="(range, index) in workingHours"
                                    :key="index"
                                    class="flex flex-wrap items-end gap-3 rounded border border-[#e0e6ed] p-3 dark:border-[#1b2e4b]"
                                >
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold">Desde</label>
                                        <input v-model="range.start" type="time" class="form-input w-36" />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold">Hasta</label>
                                        <input v-model="range.end" type="time" class="form-input w-36" />
                                    </div>
                                    <div class="flex items-center gap-1 text-xs text-white-dark">
                                        <svg class="h-4 w-4 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        {{ range.start }} - {{ range.end }}
                                    </div>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger p-1.5"
                                        @click="removeTimeRange(index)"
                                    >
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div v-if="workingHours.length" class="mt-3 flex items-center gap-2 text-sm">
                                <span class="font-semibold">Vista previa:</span>
                                <span class="rounded bg-primary/10 px-2 py-0.5 text-primary">{{ hoursLabel }}</span>
                            </div>
                            <p v-if="!canSave" class="mt-2 text-xs text-danger">
                                Verifica que cada rango tenga hora de inicio <strong>anterior</strong> a la hora de fin.
                            </p>
                        </div>

                        <!-- Branding Colors -->
                        <div class="panel">
                            <div class="mb-4 font-semibold">Colores Corporativos</div>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-semibold">Color Primario</label>
                                    <div class="flex items-center gap-3">
                                        <input v-model="form.primary_color" type="color" class="form-input w-14 h-10 p-1 cursor-pointer" />
                                        <input v-model="form.primary_color" type="text" class="form-input flex-1" placeholder="#4361ee" maxlength="7" />
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-semibold">Color Secundario</label>
                                    <div class="flex items-center gap-3">
                                        <input v-model="form.secondary_color" type="color" class="form-input w-14 h-10 p-1 cursor-pointer" />
                                        <input v-model="form.secondary_color" type="text" class="form-input flex-1" placeholder="#805dca" maxlength="7" />
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center gap-3">
                                <span class="text-xs text-white-dark">Vista previa:</span>
                                <span class="h-6 w-6 rounded-full border" :style="{ backgroundColor: form.primary_color }"></span>
                                <span class="h-6 w-6 rounded-full border" :style="{ backgroundColor: form.secondary_color }"></span>
                            </div>
                        </div>

                        <!-- Print & PDF Templates -->
                        <div class="panel">
                            <div class="mb-4 font-semibold">Documentos y Reportes PDF</div>
                            <div class="space-y-4">
                                <div>
                                    <label class="mb-1 block text-sm font-semibold">Pie de página para documentos impresos</label>
                                    <input v-model="form.print_footer" type="text" class="form-input" placeholder="Documento generado por el sistema de gestión de salud." />
                                    <p class="mt-1 text-xs text-white-dark">Aparecerá al pie de cada PDF generado.</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-semibold">Plantilla de receta médica (texto base)</label>
                                    <textarea v-model="form.recipe_template" rows="3" class="form-textarea" placeholder="Texto que aparecerá por defecto en las recetas..."></textarea>
                                    <p class="mt-1 text-xs text-white-dark">Texto predefinido para recetas médicas. Se puede editar al generar.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Logo & Summary -->
                    <div class="space-y-4">
                        <div class="panel">
                            <div class="mb-4 font-semibold">Logo del Establecimiento</div>
                            <div class="flex flex-col items-center gap-4">
                                <!-- Logo preview -->
                                <div
                                    class="flex h-40 w-full items-center justify-center rounded-lg border-2 border-dashed border-[#e0e6ed] bg-[#fbfbfb] dark:border-[#1b2e4b] dark:bg-[#121c2c]"
                                >
                                    <img v-if="establishmentLogo" :src="establishmentLogo" alt="Logo" class="max-h-36 max-w-full rounded object-contain" />
                                    <div v-else class="text-center text-white-dark">
                                        <svg class="mx-auto mb-2 h-12 w-12 opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                        <p class="text-xs">Sin logo</p>
                                    </div>
                                </div>

                                <!-- Upload button -->
                                <div class="flex w-full gap-2">
                                    <label class="btn btn-outline-primary btn-sm w-full cursor-pointer">
                                        <svg class="h-4 w-4 ltr:mr-2 rtl:ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                        {{ uploadingLogo ? 'Subiendo...' : 'Subir logo' }}
                                        <input type="file" accept="image/*" class="hidden" @change="handleLogoUpload" :disabled="uploadingLogo" />
                                    </label>
                                    <button
                                        v-if="establishmentLogo"
                                        type="button"
                                        class="btn btn-outline-danger btn-sm"
                                        @click="removeLogo"
                                    >
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </div>
                                <p class="text-xs text-white-dark">Formatos: JPEG, PNG, GIF, SVG, WebP. Máx 2MB.</p>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="panel">
                            <div class="mb-4 font-semibold">Resumen</div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-white-dark">Establecimiento</span>
                                    <span class="font-semibold">{{ form.establishment_name || '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-white-dark">RUC</span>
                                    <span class="font-semibold">{{ form.ruc || '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-white-dark">Representante</span>
                                    <span class="font-semibold">{{ form.representative || '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-white-dark">Email</span>
                                    <span class="font-semibold">{{ form.notification_email || '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-white-dark">Teléfono</span>
                                    <span class="font-semibold">{{ form.phone || '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-white-dark">Horarios</span>
                                    <span class="text-right font-semibold">{{ hoursLabel }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="mt-5 flex justify-end gap-3">
                    <button type="submit" class="btn btn-primary" :disabled="form.processing || !canSave">
                        <svg v-if="form.processing" class="inline-block h-4 w-4 animate-spin ltr:mr-2 rtl:ml-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-dasharray="31.4 31.4" stroke-linecap="round"/></svg>
                        Guardar configuración
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
