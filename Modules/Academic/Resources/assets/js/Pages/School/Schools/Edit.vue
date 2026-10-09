<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { useForm, Link } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';

const props = defineProps({
    school: { type: Object, required: true },
});

const baseUrl = typeof window !== 'undefined' && window.assetUrl ? window.assetUrl : '/';

const currentLogoUrl = props.school.logo ? baseUrl + 'storage/' + props.school.logo : null;

const form = useForm({
    id: props.school.id,
    name: props.school.name,
    modular_code: props.school.modular_code,
    address: props.school.address,
    phone: props.school.phone,
    email: props.school.email,
    logo: null,
    logo_preview: currentLogoUrl,
    type: props.school.type ?? 'privado',
    is_default: !!props.school.is_default,
    status: !!props.school.status,
});

const handleLogoUpload = (event) => {
    const file = event.target.files[0];
    if (!file) {
        form.logo = null;
        form.logo_preview = currentLogoUrl;
        return;
    }
    form.logo = file;
    form.logo_preview = URL.createObjectURL(file);
};

const updateSchool = () => {
    form.post(route('aca_schools_update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Colegio actualizado correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        },
    });
};
</script>

<template>
    <AppLayout title="Editar Colegio">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_schools_list'), title: 'Colegios' },
                { title: 'Editar' },
            ]"
        />
        <div class="pt-5">
            <div class="panel p-6">
                <h3 class="text-lg font-semibold mb-4">Datos del Colegio</h3>
                <form @submit.prevent="updateSchool" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="form-label">Nombre *</label>
                        <input v-model="form.name" type="text" class="form-input" />
                        <p v-if="form.errors.name" class="text-danger text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="form-label">Código modular (MINEDU)</label>
                        <input v-model="form.modular_code" type="text" class="form-input" maxlength="20" />
                    </div>
                    <div>
                        <label class="form-label">Tipo de colegio *</label>
                        <select v-model="form.type" class="form-select">
                            <option value="privado">Privado (cobros con mensualidad obligatoria)</option>
                            <option value="nacional">Nacional (cobros voluntarios)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Teléfono</label>
                        <input v-model="form.phone" type="text" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input v-model="form.email" type="email" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">Dirección</label>
                        <input v-model="form.address" type="text" class="form-input" />
                    </div>
                    <!-- Escudo / insignia del colegio: aparece en el carné escolar -->
                    <div>
                        <label class="form-label">Escudo / insignia</label>
                        <div class="flex items-center gap-4">
                            <div>
                                <img
                                    v-if="form.logo_preview"
                                    :src="form.logo_preview"
                                    alt="Escudo del colegio"
                                    class="size-20 object-cover rounded-full border border-slate-300 dark:border-slate-600"
                                />
                                <span
                                    v-else
                                    class="flex shrink-0 items-center justify-center size-20 rounded-full border-2 border-dotted border-gray-300 text-gray-400 dark:border-neutral-700 dark:text-neutral-600"
                                >
                                    <svg class="size-7" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <circle cx="12" cy="10" r="3"></circle>
                                        <path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"></path>
                                    </svg>
                                </span>
                            </div>
                            <div class="flex-1">
                                <label class="block">
                                    <span class="sr-only">Subir escudo del colegio</span>
                                    <input
                                        @change="handleLogoUpload"
                                        type="file"
                                        accept="image/png, image/jpeg, image/webp, image/svg+xml"
                                        class="block w-full text-sm text-gray-500
                                            file:me-4 file:py-2 file:px-4
                                            file:rounded-lg file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-blue-600 file:text-white
                                            hover:file:bg-blue-700
                                            dark:text-neutral-400
                                            dark:file:bg-blue-500
                                            dark:hover:file:bg-blue-400"
                                    />
                                </label>
                                <p class="text-xs text-gray-500 mt-1">PNG, JPG, WEBP o SVG. Se muestra en el carné escolar.</p>
                                <p v-if="form.errors.logo" class="text-danger text-xs mt-1">{{ form.errors.logo }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-6 pb-2 sm:items-end">
                        <div class="flex items-center">
                            <input id="school_default_e" v-model="form.is_default" type="checkbox" class="form-checkbox" />
                            <label for="school_default_e" class="ml-2 text-sm">Colegio por defecto</label>
                        </div>
                        <div class="flex items-center">
                            <input id="school_status_e" v-model="form.status" type="checkbox" class="form-checkbox" />
                            <label for="school_status_e" class="ml-2 text-sm">Activo</label>
                        </div>
                    </div>
                    <div class="sm:col-span-2 flex justify-end gap-2">
                        <Link :href="route('aca_schools_list')" class="btn btn-outline-danger">Cancelar</Link>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
