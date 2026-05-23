<script setup>
import { ref, watch, computed } from 'vue';
import ModalLargeX from '@/Components/ModalLargeX.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import IconEdit from '@/Components/vristo/icon/icon-edit.vue';
import IconTrash from '@/Components/vristo/icon/icon-trash.vue';
import IconUserPlus from '@/Components/vristo/icon/icon-user-plus.vue';
import IconDatabaseSearch from '@/Components/vristo/icon/icon-database-search.vue';
import IconUser from '@/Components/vristo/icon/icon-user.vue';
import { bibSwal } from '../../../../utils/bibSwal.js';

const DNI_DOCUMENT_TYPE_ID = 1;

const props = defineProps({
    memberIds: { type: Array, default: () => [] },
    initialMembers: { type: Array, default: () => [] },
    identityDocumentTypes: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:memberIds']);

const mapUserToMember = (user) => {
    const p = user.person || user;
    return {
        id: user.id,
        name: user.name || p.names,
        email: user.email || p.email,
        document_type_id: p.document_type_id ?? 1,
        number: p.number ?? '',
        names: p.names ?? user.name,
        father_lastname: p.father_lastname ?? '',
        mother_lastname: p.mother_lastname ?? '',
        telephone: p.telephone ?? '',
        full_name: p.full_name ?? user.name,
        gender: p.gender ?? null,
    };
};

const selectedMembers = ref(
    props.initialMembers.length
        ? props.initialMembers.map(mapUserToMember)
        : []
);

const syncMemberIds = () => {
    emit(
        'update:memberIds',
        selectedMembers.value.map((m) => m.id)
    );
};

watch(selectedMembers, syncMemberIds, { deep: true });

const searchQuery = ref('');
const searchResults = ref([]);
const searchMeta = ref({ current_page: 1, last_page: 1, total: 0 });
const searchLoading = ref(false);
const showSearchDropdown = ref(false);

const searchMembers = (page = 1) => {
    if (!searchQuery.value.trim() && page === 1) {
        return;
    }
    searchLoading.value = true;
    axios
        .post(route('bib_organizations_members_search'), {
            search: searchQuery.value.trim(),
            exclude_ids: selectedMembers.value.map((m) => m.id),
            page,
            per_page: 8,
        })
        .then((res) => {
            searchResults.value = res.data.data || [];
            searchMeta.value = {
                current_page: res.data.current_page,
                last_page: res.data.last_page,
                total: res.data.total,
            };
            showSearchDropdown.value = true;
        })
        .finally(() => {
            searchLoading.value = false;
        });
};

const addMember = (member) => {
    if (selectedMembers.value.some((m) => m.id === member.id)) {
        return;
    }
    selectedMembers.value.push({ ...member });
    showSearchDropdown.value = false;
    searchQuery.value = '';
    searchResults.value = [];
    syncMemberIds();
};

const removeMember = (id) => {
    selectedMembers.value = selectedMembers.value.filter((m) => m.id !== id);
    syncMemberIds();
};

const displayModal = ref(false);
const modalLoading = ref(false);
const dbLoading = ref(false);
const reniecLoading = ref(false);
const saveLoading = ref(false);
const modalErrors = ref({});

const emptyModalForm = () => ({
    user_id: null,
    document_type_id: 1,
    number: '',
    names: '',
    father_lastname: '',
    mother_lastname: '',
    email: '',
    telephone: '',
    gender: null,
    password: '',
});

const modalForm = ref(emptyModalForm());
const isEditModal = computed(() => !!modalForm.value.user_id);
const isDniDocument = computed(
    () => Number(modalForm.value.document_type_id) === DNI_DOCUMENT_TYPE_ID
);

const openCreateModal = () => {
    modalForm.value = emptyModalForm();
    modalErrors.value = {};
    displayModal.value = true;
};

const openEditModal = async (member) => {
    modalErrors.value = {};
    modalLoading.value = true;
    displayModal.value = true;
    try {
        const res = await axios.get(route('bib_organizations_members_show', member.id));
        const m = res.data.member;
        modalForm.value = {
            user_id: m.id,
            document_type_id: m.document_type_id ?? 1,
            number: m.number ?? '',
            names: m.names ?? '',
            father_lastname: m.father_lastname ?? '',
            mother_lastname: m.mother_lastname ?? '',
            email: m.email ?? '',
            telephone: m.telephone ?? '',
            gender: m.gender ?? null,
            password: '',
        };
    } catch {
        modalForm.value = {
            ...emptyModalForm(),
            user_id: member.id,
            document_type_id: member.document_type_id ?? 1,
            number: member.number ?? '',
            names: member.names ?? member.name,
            father_lastname: member.father_lastname ?? '',
            mother_lastname: member.mother_lastname ?? '',
            email: member.email ?? '',
            telephone: member.telephone ?? '',
            gender: member.gender ?? null,
            password: '',
        };
    } finally {
        modalLoading.value = false;
    }
};

const closeModal = () => {
    displayModal.value = false;
};

const fillModalFromPersonRecord = (p) => {
    modalForm.value.names = p.names ?? modalForm.value.names;
    modalForm.value.father_lastname = p.father_lastname ?? '';
    modalForm.value.mother_lastname = p.mother_lastname ?? '';
    modalForm.value.email = p.email ?? modalForm.value.email;
    modalForm.value.telephone = p.telephone ?? modalForm.value.telephone;
    modalForm.value.gender = p.gender ?? modalForm.value.gender;
};

const searchReniec = () => {
    const number = String(modalForm.value.number).replace(/\D/g, '');
    if (number.length !== 8) {
        bibSwal({ icon: 'warning', title: 'DNI inválido', text: 'El DNI debe tener 8 dígitos.' });
        return;
    }
    reniecLoading.value = true;
    axios
        .post(route('bib_organizations_members_reniec'), {
            document_type_id: modalForm.value.document_type_id,
            number,
        })
        .then((res) => {
            if (!res.data.success) {
                bibSwal({ icon: 'error', title: 'RENIEC', text: res.data.error || 'No se pudo consultar.' });
                return;
            }
            if (res.data.source === 'database') {
                fillModalFromPersonRecord(res.data.person);
                bibSwal({ icon: 'info', title: 'Ya existe', text: res.data.message });
                return;
            }
            const person = res.data.person;
            modalForm.value.number = person.document_number ?? number;
            modalForm.value.names = person.names ?? '';
            modalForm.value.father_lastname = person.father_lastname ?? '';
            modalForm.value.mother_lastname = person.mother_lastname ?? '';
            bibSwal({ icon: 'success', title: 'RENIEC', text: 'Datos consultados correctamente.' });
        })
        .finally(() => {
            reniecLoading.value = false;
        });
};

const searchInternalDoc = () => {
    if (!modalForm.value.number?.trim()) {
        return;
    }
    dbLoading.value = true;
    axios
        .post(route('search_person_number'), {
            document_type: modalForm.value.document_type_id,
            number: modalForm.value.number,
            searchBy: 1,
        })
        .then((res) => {
            if (!res.data.status) {
                bibSwal({ icon: 'info', title: 'No encontrado', text: res.data.alert });
                return;
            }
            const p = res.data.person;
            fillModalFromPersonRecord(p);
            if (p.id && !modalForm.value.user_id) {
                bibSwal({
                    icon: 'info',
                    title: 'Persona en sistema',
                    text: 'Datos cargados. Al guardar se vinculará o creará el usuario Lector.',
                });
            }
        })
        .finally(() => {
            dbLoading.value = false;
        });
};

const saveMember = () => {
    modalErrors.value = {};
    saveLoading.value = true;
    const url = isEditModal.value
        ? route('bib_organizations_members_update', modalForm.value.user_id)
        : route('bib_organizations_members_store');
    const method = isEditModal.value ? 'put' : 'post';

    axios[method](url, modalForm.value)
        .then((res) => {
            const member = res.data.member;
            const idx = selectedMembers.value.findIndex((m) => m.id === member.id);
            if (idx >= 0) {
                selectedMembers.value[idx] = member;
            } else {
                selectedMembers.value.push(member);
            }
            syncMemberIds();
            closeModal();
            bibSwal({
                icon: 'success',
                title: isEditModal.value ? 'Actualizado' : 'Registrado',
                text: 'Usuario Lector guardado correctamente.',
            });
        })
        .catch((err) => {
            if (err.response?.data?.errors) {
                modalErrors.value = err.response.data.errors;
            } else {
                bibSwal({ icon: 'error', title: 'Error', text: 'No se pudo guardar el lector.' });
            }
        })
        .finally(() => {
            saveLoading.value = false;
        });
};

const saveAndAddMember = () => {
    saveLoading.value = true;
    modalErrors.value = {};
    const url = isEditModal.value
        ? route('bib_organizations_members_update', modalForm.value.user_id)
        : route('bib_organizations_members_store');
    const method = isEditModal.value ? 'put' : 'post';

    axios[method](url, modalForm.value)
        .then((res) => {
            addMember(res.data.member);
            closeModal();
        })
        .catch((err) => {
            if (err.response?.data?.errors) {
                modalErrors.value = err.response.data.errors;
            }
        })
        .finally(() => {
            saveLoading.value = false;
        });
};

const documentLabel = (member) => {
    if (!member.number) {
        return '—';
    }
    const type = props.identityDocumentTypes.find((t) => t.id == member.document_type_id);
    const prefix = type?.description ? `${type.description}: ` : '';
    return `${prefix}${member.number}`;
};
</script>

<template>
    <div class="col-span-6 panel p-4">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-neutral-100">
                    Miembros (usuarios Lector)
                </h3>
                <p class="text-sm text-gray-500 mt-0.5">
                    Busque lectores existentes o registre uno nuevo. Solo se muestran los asignados a esta organización.
                </p>
            </div>
            <button type="button" class="btn btn-success btn-sm" @click="openCreateModal">
                <IconUserPlus class="w-4 h-4 mr-1 inline" />
                Nuevo lector
            </button>
        </div>

        <div class="relative mb-4">
            <form class="flex gap-0" @submit.prevent="searchMembers(1)">
                <input
                    v-model="searchQuery"
                    type="search"
                    class="form-input flex-1 rounded-r-none"
                    placeholder="Buscar por nombre, correo o documento para agregar..."
                    autocomplete="off"
                />
                <button type="submit" class="btn btn-primary rounded-l-none" :disabled="searchLoading">
                    <IconLoader v-if="searchLoading" class="w-4 h-4 animate-spin" />
                    <span v-else>Buscar</span>
                </button>
            </form>

            <div
                v-if="showSearchDropdown && searchResults.length"
                class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg max-h-56 overflow-y-auto"
            >
                <button
                    v-for="row in searchResults"
                    :key="row.id"
                    type="button"
                    class="w-full text-left px-4 py-3 text-sm hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0"
                    @click="addMember(row)"
                >
                    <span class="font-medium">{{ row.full_name || row.name }}</span>
                    <span class="text-gray-500 block text-xs">
                        {{ row.email }}
                        <template v-if="row.number"> · {{ row.number }}</template>
                    </span>
                </button>
                <div
                    v-if="searchMeta.last_page > 1"
                    class="flex justify-between items-center px-3 py-2 text-xs border-t dark:border-gray-600"
                >
                    <button
                        type="button"
                        class="btn btn-outline-primary btn-xs"
                        :disabled="searchMeta.current_page <= 1"
                        @click="searchMembers(searchMeta.current_page - 1)"
                    >
                        Anterior
                    </button>
                    <span>{{ searchMeta.current_page }} / {{ searchMeta.last_page }}</span>
                    <button
                        type="button"
                        class="btn btn-outline-primary btn-xs"
                        :disabled="searchMeta.current_page >= searchMeta.last_page"
                        @click="searchMembers(searchMeta.current_page + 1)"
                    >
                        Siguiente
                    </button>
                </div>
            </div>
            <p
                v-else-if="showSearchDropdown && !searchResults.length && !searchLoading"
                class="absolute z-20 mt-1 w-full rounded-lg border px-4 py-3 text-sm text-gray-500 bg-white dark:bg-gray-800"
            >
                Sin resultados. Use «Nuevo lector» para registrarlo.
            </p>
        </div>

        <div class="overflow-x-auto border border-gray-200 dark:border-gray-600 rounded-lg">
            <table class="table-hover min-w-full text-sm">
                <thead>
                    <tr>
                        <th class="text-center w-24">Acciones</th>
                        <th>Documento</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="member in selectedMembers" :key="member.id">
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    type="button"
                                    class="text-primary hover:underline"
                                    title="Editar"
                                    @click="openEditModal(member)"
                                >
                                    <IconEdit class="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    class="text-danger hover:underline"
                                    title="Quitar de la organización"
                                    @click="removeMember(member.id)"
                                >
                                    <IconTrash class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                        <td>{{ documentLabel(member) }}</td>
                        <td class="font-medium">{{ member.full_name || member.name }}</td>
                        <td>{{ member.email || '—' }}</td>
                        <td>{{ member.telephone || '—' }}</td>
                    </tr>
                    <tr v-if="!selectedMembers.length">
                        <td colspan="5" class="text-center py-8 text-gray-500">
                            No hay miembros asignados. Busque un lector o registre uno nuevo.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-500 mt-2">{{ selectedMembers.length }} miembro(s) asignado(s)</p>

        <ModalLargeX :show="displayModal" :on-close="closeModal" :icon="'/img/perfil.png'">
            <template #title>{{ isEditModal ? 'Editar lector' : 'Nuevo lector' }}</template>
            <template #message>
                Registre o actualice la persona y el usuario con rol Lector. La contraseña por defecto es el número de documento.
            </template>
            <template #content>
                <div v-if="modalLoading" class="flex justify-center py-8">
                    <IconLoader class="w-8 h-8 animate-spin text-primary" />
                </div>
                <div v-else class="grid grid-cols-6 gap-4">
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel value="Tipo de documento *" />
                        <select v-model="modalForm.document_type_id" class="form-select w-full">
                            <option v-for="dt in identityDocumentTypes" :key="dt.id" :value="dt.id">
                                {{ dt.description }}
                            </option>
                        </select>
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel value="Número *" />
                        <TextInput v-model="modalForm.number" class="w-full" />
                        <InputError :message="modalErrors.number?.[0]" class="mt-1" />
                    </div>
                    <div class="col-span-6 sm:col-span-2 flex items-end gap-2">
                        <button
                            v-if="isDniDocument"
                            type="button"
                            class="btn btn-primary flex-1"
                            :disabled="reniecLoading"
                            @click="searchReniec"
                        >
                            <IconLoader v-if="reniecLoading" class="w-4 h-4 animate-spin mr-1 inline" />
                            <IconUser v-else class="w-4 h-4 mr-1 inline" />
                            RENIEC
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger flex-1"
                            :disabled="dbLoading"
                            @click="searchInternalDoc"
                        >
                            <IconLoader v-if="dbLoading" class="w-4 h-4 animate-spin mr-1 inline" />
                            <IconDatabaseSearch v-else class="w-4 h-4 mr-1 inline" />
                            Base de datos
                        </button>
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel value="Nombres *" />
                        <TextInput v-model="modalForm.names" class="w-full" />
                        <InputError :message="modalErrors.names?.[0]" class="mt-1" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel value="Apellido paterno *" />
                        <TextInput v-model="modalForm.father_lastname" class="w-full" />
                        <InputError :message="modalErrors.father_lastname?.[0]" class="mt-1" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel value="Apellido materno" />
                        <TextInput v-model="modalForm.mother_lastname" class="w-full" />
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <InputLabel value="Correo (usuario) *" />
                        <TextInput v-model="modalForm.email" type="email" class="w-full" />
                        <InputError :message="modalErrors.email?.[0]" class="mt-1" />
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <InputLabel :value="isEditModal ? 'Nueva contraseña (opcional)' : 'Contraseña (opcional)'" />
                        <TextInput
                            v-model="modalForm.password"
                            type="password"
                            class="w-full"
                            :placeholder="isEditModal ? 'Dejar vacío para no cambiar' : 'Por defecto: número de documento'"
                        />
                        <InputError :message="modalErrors.password?.[0]" class="mt-1" />
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <InputLabel value="Teléfono" />
                        <TextInput v-model="modalForm.telephone" class="w-full" />
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <InputLabel value="Género" />
                        <select v-model="modalForm.gender" class="form-select w-full">
                            <option :value="null">—</option>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                    </div>

                </div>
            </template>
            <template #buttons>
                <PrimaryButton type="button" class="mr-2" :disabled="saveLoading || modalLoading" @click="saveMember">
                    Guardar
                </PrimaryButton>
                <PrimaryButton
                    v-if="!isEditModal"
                    type="button"
                    class="mr-2 btn-success"
                    :disabled="saveLoading || modalLoading"
                    @click="saveAndAddMember"
                >
                    Guardar y agregar
                </PrimaryButton>
            </template>
        </ModalLargeX>
    </div>
</template>
