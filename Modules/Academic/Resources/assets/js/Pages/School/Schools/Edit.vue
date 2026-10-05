<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { useForm, Link } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';

const props = defineProps({
    school: { type: Object, required: true },
});

const form = useForm({
    id: props.school.id,
    name: props.school.name,
    modular_code: props.school.modular_code,
    address: props.school.address,
    phone: props.school.phone,
    email: props.school.email,
    type: props.school.type ?? 'privado',
    is_default: !!props.school.is_default,
    status: !!props.school.status,
});

const updateSchool = () => {
    form.post(route('aca_schools_update'), {
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
                    <div class="flex items-center gap-6 pb-2">
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
