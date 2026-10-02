<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { useForm, Link } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';

const props = defineProps({
    documentTypes: { type: Array, default: () => [] },
});

const form = useForm({
    document_type_id: '1',
    number: null,
    names: null,
    father_lastname: null,
    mother_lastname: null,
    gender: null,
    birthdate: null,
    telephone: null,
    email: null,
    address: null,
});

const createStudent = () => {
    form.post(route('aca_school_students_store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Alumno registrado correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            form.reset();
        },
    });
};
</script>

<template>
    <AppLayout title="Nuevo Alumno Escolar">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_students_list'), title: 'Alumnos' },
                { title: 'Nuevo' },
            ]"
        />
        <div class="pt-5">
            <div class="panel p-6">
                <h3 class="text-lg font-semibold mb-4">Datos del Alumno</h3>
                <form @submit.prevent="createStudent" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="form-label">Tipo de documento *</label>
                        <select v-model="form.document_type_id" class="form-select">
                            <option v-for="doc in documentTypes" :key="doc.id" :value="doc.id">{{ doc.description }}</option>
                        </select>
                        <p v-if="form.errors.document_type_id" class="text-danger text-xs mt-1">{{ form.errors.document_type_id }}</p>
                    </div>
                    <div>
                        <label class="form-label">N° de documento *</label>
                        <input v-model="form.number" type="text" class="form-input" maxlength="12" />
                        <p v-if="form.errors.number" class="text-danger text-xs mt-1">{{ form.errors.number }}</p>
                    </div>
                    <div>
                        <label class="form-label">Sexo</label>
                        <select v-model="form.gender" class="form-select">
                            <option value="">Sin especificar</option>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Nombres *</label>
                        <input v-model="form.names" type="text" class="form-input" />
                        <p v-if="form.errors.names" class="text-danger text-xs mt-1">{{ form.errors.names }}</p>
                    </div>
                    <div>
                        <label class="form-label">Apellido paterno *</label>
                        <input v-model="form.father_lastname" type="text" class="form-input" />
                        <p v-if="form.errors.father_lastname" class="text-danger text-xs mt-1">{{ form.errors.father_lastname }}</p>
                    </div>
                    <div>
                        <label class="form-label">Apellido materno *</label>
                        <input v-model="form.mother_lastname" type="text" class="form-input" />
                        <p v-if="form.errors.mother_lastname" class="text-danger text-xs mt-1">{{ form.errors.mother_lastname }}</p>
                    </div>
                    <div>
                        <label class="form-label">Fecha de nacimiento</label>
                        <input v-model="form.birthdate" type="date" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">Teléfono</label>
                        <input v-model="form.telephone" type="text" class="form-input" maxlength="12" />
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input v-model="form.email" type="email" class="form-input" />
                        <p v-if="form.errors.email" class="text-danger text-xs mt-1">{{ form.errors.email }}</p>
                    </div>
                    <div class="sm:col-span-3">
                        <label class="form-label">Dirección</label>
                        <input v-model="form.address" type="text" class="form-input" />
                    </div>
                    <div class="sm:col-span-3 flex justify-end gap-2">
                        <Link :href="route('aca_school_students_list')" class="btn btn-outline-danger">Cancelar</Link>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
