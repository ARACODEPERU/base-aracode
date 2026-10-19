<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import { ref, reactive, onMounted, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ModalSmall from '@/Components/ModalSmall.vue';
import ModalLarge from '@/Components/ModalLarge.vue';
import GuardianSearchModal from './../../../Components/GuardianSearchModal.vue';
import iconLoader from '@/Components/vristo/icon/icon-loader.vue';

const props = defineProps({
    student: { type: Object, required: true },
    documentTypes: { type: Array, default: () => [] },
    guardianRelationships: { type: Object, default: () => ({}) },
    ubigeo: { type: Array, default: () => [] },
});

const page = usePage();

const permissions = computed(() => page.props.auth?.permissions || []);

const hasPermission = (permission) => {
    if (!permission) {
        return true;
    }

    return permissions.value.includes(permission);
};

const person = props.student.person ?? {};

const form = useForm({
    id: props.student.id,
    document_type_id: person.document_type_id ?? '1',
    number: person.number,
    names: person.names ?? person.short_name,
    father_lastname: person.father_lastname,
    mother_lastname: person.mother_lastname,
    gender: person.gender,
    birthdate: person.birthdate,
    telephone: person.telephone,
    email: person.email,
    address: person.address,
    student_code: props.student.student_code,
    status: !!props.student.status,
});

const updateStudent = () => {
    form.post(route('aca_school_students_update'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Alumno actualizado correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        },
    });
};

/* ---------- Apoderados ---------- */

const guardians = ref(props.student.guardians ?? []);
const loadingGuardians = ref(false);
const guardiansError = ref('');
const showGuardianSearchModal = ref(false);
const showGuardianAssignModal = ref(false);
const selectedGuardianPerson = ref(null);
const savingGuardian = ref(false);
const assignForm = reactive({
    relationship: 'otro',
    is_primary: false,
});

/* ---------- Editar apoderado (datos de la persona + vinculo) ---------- */

// El boton Editar solo se muestra con el permiso propio de apoderados.
const canEditGuardianData = computed(() => hasPermission('aca_school_alumno_apoderados_editar'));
const showGuardianEditModal = ref(false);
const editingGuardian = ref(null);
const savingGuardianEdit = ref(false);
const guardianEditErrors = reactive({});

const guardianEditForm = reactive({
    document_type_id: '',
    number: '',
    names: '',
    father_lastname: '',
    mother_lastname: '',
    gender: '',
    birthdate: '',
    telephone: '',
    email: '',
    address: '',
    relationship: 'otro',
    is_primary: false,
    status: true,
});

const clearGuardianEditErrors = () => {
    Object.keys(guardianEditErrors).forEach((key) => delete guardianEditErrors[key]);
};

// `birthdate` puede llegar como fecha con hora: el input type=date solo acepta YYYY-MM-DD.
const toDateInput = (value) => (value ? String(value).substring(0, 10) : '');

const openGuardianEditModal = (guardian) => {
    const p = guardian.person ?? {};

    editingGuardian.value = guardian;
    clearGuardianEditErrors();

    guardianEditForm.document_type_id = p.document_type_id ?? '';
    guardianEditForm.number = p.number ?? '';
    guardianEditForm.names = p.names ?? p.short_name ?? '';
    guardianEditForm.father_lastname = p.father_lastname ?? '';
    guardianEditForm.mother_lastname = p.mother_lastname ?? '';
    guardianEditForm.gender = p.gender ?? '';
    guardianEditForm.birthdate = toDateInput(p.birthdate);
    guardianEditForm.telephone = p.telephone ?? '';
    guardianEditForm.email = p.email ?? '';
    guardianEditForm.address = p.address ?? '';
    guardianEditForm.relationship = guardian.relationship ?? 'otro';
    guardianEditForm.is_primary = !!guardian.is_primary;
    guardianEditForm.status = !!guardian.status;

    showGuardianEditModal.value = true;
};

const closeGuardianEditModal = () => {
    showGuardianEditModal.value = false;
    editingGuardian.value = null;
    clearGuardianEditErrors();
};

const saveGuardianEdit = () => {
    if (! editingGuardian.value) return;

    savingGuardianEdit.value = true;
    clearGuardianEditErrors();

    axios.put(route('aca_school_students_guardians_person_update', editingGuardian.value.id), guardianEditForm)
        .then((res) => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: res.data.message ?? 'Apoderado actualizado correctamente',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            closeGuardianEditModal();
            loadGuardians();
        })
        .catch((error) => {
            const data = error.response?.data;

            if (data?.errors) {
                Object.entries(data.errors).forEach(([field, messages]) => {
                    guardianEditErrors[field] = Array.isArray(messages) ? messages[0] : messages;
                });
            }

            const firstError = data?.errors ? Object.values(data.errors)[0] : null;
            Swal2.fire({
                icon: 'error',
                text: (Array.isArray(firstError) ? firstError[0] : firstError)
                    ?? data?.message
                    ?? 'No se pudo actualizar el apoderado',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        })
        .finally(() => {
            savingGuardianEdit.value = false;
        });
};

const loadGuardians = () => {
    loadingGuardians.value = true;
    guardiansError.value = '';
    axios.get(route('aca_school_students_guardians_list', props.student.id)).then((res) => {
        if (res.data.success) {
            guardians.value = res.data.guardians;
        }
    }).catch((error) => {
        // Si la lista no carga hay que decirlo: sin esto el panel mostraba
        // "sin apoderados" y parecia que el alta no habia guardado nada.
        guardiansError.value = error.response?.data?.message ?? 'No se pudieron cargar los apoderados.';
    }).finally(() => {
        loadingGuardians.value = false;
    });
};

onMounted(loadGuardians);

const relationshipLabel = (value) => props.guardianRelationships[value] ?? value;

const onGuardianSelected = (personSelected) => {
    showGuardianSearchModal.value = false;
    selectedGuardianPerson.value = personSelected;
    assignForm.relationship = 'otro';
    assignForm.is_primary = guardians.value.length === 0;
    showGuardianAssignModal.value = true;
};

const closeAssignModal = () => {
    showGuardianAssignModal.value = false;
    selectedGuardianPerson.value = null;
};

const saveGuardian = () => {
    if (!selectedGuardianPerson.value) return;
    savingGuardian.value = true;
    axios.post(route('aca_school_students_guardians_store', props.student.id), {
        person_id: selectedGuardianPerson.value.id,
        relationship: assignForm.relationship,
        is_primary: assignForm.is_primary,
    }).then((res) => {
        Swal2.fire({
            title: 'Enhorabuena',
            text: res.data.message ?? 'Apoderado guardado correctamente',
            icon: 'success',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        closeAssignModal();
        loadGuardians();
    }).catch((error) => {
        Swal2.fire({
            icon: 'error',
            text: error.response?.data?.message ?? 'No se pudo guardar el apoderado',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }).finally(() => {
        savingGuardian.value = false;
    });
};

const updateGuardian = (guardian) => {
    axios.put(route('aca_school_students_guardians_update', guardian.id), {
        relationship: guardian.relationship,
        is_primary: guardian.is_primary,
        status: guardian.status,
    }).then((res) => {
        Swal2.fire({
            text: res.data.message ?? 'Apoderado actualizado correctamente',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        loadGuardians();
    }).catch((error) => {
        Swal2.fire({
            icon: 'error',
            text: error.response?.data?.message ?? 'No se pudo actualizar el apoderado',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    });
};

const setPrimary = (guardian) => {
    guardian.is_primary = true;
    updateGuardian(guardian);
};

const toggleGuardianStatus = (guardian) => {
    guardian.status = !guardian.status;
    updateGuardian(guardian);
};

const removeGuardian = (guardian) => {
    Swal2.fire({
        title: '¿Quitar apoderado?',
        text: `Se desvinculará a ${guardian.person?.full_name ?? ''} del alumno.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, quitar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('aca_school_students_guardians_destroy', guardian.id)).then(() => {
                loadGuardians();
            });
        }
    });
};
</script>

<template>
    <AppLayout title="Editar Alumno Escolar">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_students_list'), title: 'Alumnos' },
                { title: 'Editar' },
            ]"
        />
        <div class="pt-5">
            <div class="panel p-6">
                <h3 class="text-lg font-semibold mb-4">Datos del Alumno</h3>
                <form @submit.prevent="updateStudent" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="form-label">Tipo de documento *</label>
                        <select v-model="form.document_type_id" class="form-select">
                            <option v-for="doc in documentTypes" :key="doc.id" :value="doc.id">{{ doc.description }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">N° de documento *</label>
                        <input v-model="form.number" type="text" class="form-input" maxlength="12" />
                        <p v-if="form.errors.number" class="text-danger text-xs mt-1">{{ form.errors.number }}</p>
                    </div>
                    <div>
                        <label class="form-label">Código interno</label>
                        <input v-model="form.student_code" type="text" class="form-input" />
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
                    <div>
                        <label class="form-label">Dirección</label>
                        <input v-model="form.address" type="text" class="form-input" />
                    </div>
                    <div class="flex items-end pb-1">
                        <input id="student_status" v-model="form.status" type="checkbox" class="form-checkbox" />
                        <label for="student_status" class="ml-2 text-sm">Activo</label>
                    </div>
                    <div class="sm:col-span-3 flex justify-end gap-2">
                        <Link :href="route('aca_school_students_list')" class="btn btn-outline-secondary">Ir al listado</Link>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">Guardar cambios</button>
                    </div>
                </form>
            </div>

            <div v-if="hasPermission('aca_school_alumno_apoderados')" class="panel mt-5">
                <div class="flex items-center justify-between p-5 border-b border-[#ebedf2] dark:border-[#191e3a]">
                    <h3 class="text-lg font-semibold">Apoderados</h3>
                    <PrimaryButton type="button" @click="showGuardianSearchModal = true">
                        + Agregar apoderado
                    </PrimaryButton>
                </div>
                <div class="table-responsive p-5">
                    <p v-if="loadingGuardians" class="text-sm text-gray-500 flex items-center gap-2">
                        <icon-loader class="w-4 h-4 animate-spin" /> Cargando apoderados...
                    </p>
                    <p v-else-if="guardiansError" class="text-sm text-danger">
                        {{ guardiansError }}
                    </p>
                    <p v-else-if="guardians.length === 0" class="text-sm text-gray-500">
                        El alumno aún no tiene apoderados registrados.
                    </p>
                    <table v-else class="table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Persona</th>
                                <th>Documento</th>
                                <th>Teléfono</th>
                                <th>Parentesco</th>
                                <th>Principal</th>
                                <th class="text-center">Activo</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="guardian in guardians" :key="guardian.id">
                                <td>{{ guardian.person?.full_name }}</td>
                                <td>{{ guardian.person?.number }}</td>
                                <td>{{ guardian.person?.telephone ?? '-' }}</td>
                                <td>
                                    <select v-model="guardian.relationship" @change="updateGuardian(guardian)" class="form-select w-40 py-1 text-sm">
                                        <option v-for="(label, value) in guardianRelationships" :key="value" :value="value">{{ label }}</option>
                                    </select>
                                </td>
                                <td>
                                    <span v-if="guardian.is_primary" class="badge bg-success">Principal</span>
                                    <button v-else type="button" class="btn btn-outline-secondary btn-sm" @click="setPrimary(guardian)">
                                        Hacer principal
                                    </button>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" :checked="guardian.status" @change="toggleGuardianStatus(guardian)" class="form-checkbox" />
                                </td>
                                <td class="text-center whitespace-nowrap">
                                    <button v-if="canEditGuardianData" type="button" class="btn btn-outline-primary btn-sm mr-1" @click="openGuardianEditModal(guardian)">Editar</button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" @click="removeGuardian(guardian)">Quitar</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <GuardianSearchModal
            :display="showGuardianSearchModal"
            :onClose="() => (showGuardianSearchModal = false)"
            :documentTypes="documentTypes"
            :ubigeo="ubigeo"
            @selected="onGuardianSelected"
        />

        <ModalLarge :show="showGuardianEditModal" :onClose="closeGuardianEditModal" :icon="'/img/comunidad.png'">
            <template #title>
                Editar apoderado
            </template>
            <template #message>
                {{ editingGuardian?.person?.full_name ?? '' }}
            </template>
            <template #content>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="form-label">Tipo de documento *</label>
                        <select v-model="guardianEditForm.document_type_id" class="form-select">
                            <option v-for="doc in documentTypes" :key="doc.id" :value="doc.id">{{ doc.description }}</option>
                        </select>
                        <p v-if="guardianEditErrors.document_type_id" class="text-danger text-xs mt-1">{{ guardianEditErrors.document_type_id }}</p>
                    </div>
                    <div>
                        <label class="form-label">N° de documento *</label>
                        <input v-model="guardianEditForm.number" type="text" class="form-input" maxlength="12" />
                        <p v-if="guardianEditErrors.number" class="text-danger text-xs mt-1">{{ guardianEditErrors.number }}</p>
                    </div>
                    <div>
                        <label class="form-label">Sexo</label>
                        <select v-model="guardianEditForm.gender" class="form-select">
                            <option value="">Sin especificar</option>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Nombres *</label>
                        <input v-model="guardianEditForm.names" type="text" class="form-input" />
                        <p v-if="guardianEditErrors.names" class="text-danger text-xs mt-1">{{ guardianEditErrors.names }}</p>
                    </div>
                    <div>
                        <label class="form-label">Apellido paterno *</label>
                        <input v-model="guardianEditForm.father_lastname" type="text" class="form-input" />
                        <p v-if="guardianEditErrors.father_lastname" class="text-danger text-xs mt-1">{{ guardianEditErrors.father_lastname }}</p>
                    </div>
                    <div>
                        <label class="form-label">Apellido materno *</label>
                        <input v-model="guardianEditForm.mother_lastname" type="text" class="form-input" />
                        <p v-if="guardianEditErrors.mother_lastname" class="text-danger text-xs mt-1">{{ guardianEditErrors.mother_lastname }}</p>
                    </div>
                    <div>
                        <label class="form-label">Fecha de nacimiento</label>
                        <input v-model="guardianEditForm.birthdate" type="date" class="form-input" />
                        <p v-if="guardianEditErrors.birthdate" class="text-danger text-xs mt-1">{{ guardianEditErrors.birthdate }}</p>
                    </div>
                    <div>
                        <label class="form-label">Teléfono</label>
                        <input v-model="guardianEditForm.telephone" type="text" class="form-input" maxlength="12" />
                        <p v-if="guardianEditErrors.telephone" class="text-danger text-xs mt-1">{{ guardianEditErrors.telephone }}</p>
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input v-model="guardianEditForm.email" type="email" class="form-input" />
                        <p v-if="guardianEditErrors.email" class="text-danger text-xs mt-1">{{ guardianEditErrors.email }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label">Dirección</label>
                        <input v-model="guardianEditForm.address" type="text" class="form-input" />
                        <p v-if="guardianEditErrors.address" class="text-danger text-xs mt-1">{{ guardianEditErrors.address }}</p>
                    </div>
                    <div>
                        <label class="form-label">Parentesco *</label>
                        <select v-model="guardianEditForm.relationship" class="form-select">
                            <option v-for="(label, value) in guardianRelationships" :key="value" :value="value">{{ label }}</option>
                        </select>
                        <p v-if="guardianEditErrors.relationship" class="text-danger text-xs mt-1">{{ guardianEditErrors.relationship }}</p>
                    </div>
                    <div class="flex items-end gap-6 pb-1 sm:col-span-3">
                        <div class="flex items-center">
                            <input id="guardian_edit_primary" v-model="guardianEditForm.is_primary" type="checkbox" class="form-checkbox" />
                            <label for="guardian_edit_primary" class="ml-2 text-sm">Apoderado principal</label>
                        </div>
                        <div class="flex items-center">
                            <input id="guardian_edit_status" v-model="guardianEditForm.status" type="checkbox" class="form-checkbox" />
                            <label for="guardian_edit_status" class="ml-2 text-sm">Activo</label>
                        </div>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton type="button" @click="saveGuardianEdit" :disabled="savingGuardianEdit">
                    <icon-loader v-if="savingGuardianEdit" class="w-4 h-4 animate-spin mr-1" />
                    Guardar
                </PrimaryButton>
            </template>
        </ModalLarge>

        <ModalSmall :show="showGuardianAssignModal" :onClose="closeAssignModal" :icon="'/img/comunidad.png'">
            <template #title>
                Asignar apoderado
            </template>
            <template #message>
                {{ selectedGuardianPerson?.full_name ?? '' }}
            </template>
            <template #content>
                <div class="space-y-3">
                    <div>
                        <label class="form-label">Documento</label>
                        <input type="text" class="form-input" :value="selectedGuardianPerson?.number ?? ''" disabled />
                    </div>
                    <div>
                        <label class="form-label">Parentesco *</label>
                        <select v-model="assignForm.relationship" class="form-select">
                            <option v-for="(label, value) in guardianRelationships" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </div>
                    <div class="flex items-center">
                        <input id="guardian_is_primary" v-model="assignForm.is_primary" type="checkbox" class="form-checkbox" />
                        <label for="guardian_is_primary" class="ml-2 text-sm">Apoderado principal</label>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton type="button" @click="saveGuardian" :disabled="savingGuardian">
                    <icon-loader v-if="savingGuardian" class="w-4 h-4 animate-spin mr-1" />
                    Guardar
                </PrimaryButton>
            </template>
        </ModalSmall>
    </AppLayout>
</template>
