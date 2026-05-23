<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import SearchOrganization from './SearchOrganization.vue';
import OrganizationMembers from './OrganizationMembers.vue';

const props = defineProps({
    organization: { type: Object, default: null },
    identityDocumentTypes: { type: Array, default: () => [] },
});

const organizationFieldsEnabled = ref(!!props.organization?.id);

const initialMembers = computed(() => {
    if (!props.organization?.members?.length) {
        return [];
    }
    return props.organization.members;
});

const form = useForm({
    id: props.organization?.id || null,
    person_id: props.organization?.person_id || null,
    name: props.organization?.name || '',
    document_number: props.organization?.document_number || '',
    email: props.organization?.email || '',
    phone: props.organization?.phone || '',
    status: props.organization?.status || 'active',
    member_ids: props.organization?.members?.map((m) => m.id) || [],
});

const onOrganizationSelected = (data) => {
    form.person_id = data.person_id;
    form.name = data.name;
    form.document_number = data.document_number;
    form.email = data.email;
    form.phone = data.phone;
    organizationFieldsEnabled.value = true;
};

const submit = () => {
    if (form.id) {
        form.post(route('bib_organizations_update'), { preserveScroll: true });
    } else {
        form.post(route('bib_organizations_store'), { preserveScroll: true });
    }
};
</script>

<template>
    <FormSection @submitted="submit">
        <template #title>
            {{ form.id ? 'Editar organización' : 'Nueva organización' }}
        </template>
        <template #description>
            Empresa o grupo de trabajadores. Busque un cliente con RUC o regístrelo antes de guardar la organización.
        </template>

        <template #form>
            <div class="col-span-6 flex flex-wrap items-center gap-3 border-b border-gray-200 dark:border-zinc-700 pb-4">
                <SearchOrganization @selected="onOrganizationSelected" />
                <p v-if="!organizationFieldsEnabled" class="text-sm text-gray-500">
                    Busque y seleccione una organización para habilitar los datos de la empresa.
                </p>
                <p v-else-if="form.person_id" class="text-sm text-gray-500">
                    Vinculado a cliente #{{ form.person_id }}
                </p>
            </div>

            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Nombre / Razón social *" />
                <TextInput
                    v-model="form.name"
                    class="w-full"
                    required
                    :disabled="!organizationFieldsEnabled"
                />
                <InputError :message="form.errors.name" class="mt-1" />
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="RUC *" />
                <TextInput
                    v-model="form.document_number"
                    class="w-full"
                    maxlength="11"
                    required
                    :disabled="!organizationFieldsEnabled"
                />
                <InputError :message="form.errors.document_number" class="mt-1" />
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Correo" />
                <TextInput
                    v-model="form.email"
                    type="email"
                    class="w-full"
                    :disabled="!organizationFieldsEnabled"
                />
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Teléfono" />
                <TextInput
                    v-model="form.phone"
                    class="w-full"
                    :disabled="!organizationFieldsEnabled"
                />
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Estado" />
                <select v-model="form.status" class="form-select w-full">
                    <option value="active">Activa</option>
                    <option value="inactive">Inactiva</option>
                </select>
            </div>

            <OrganizationMembers
                :member-ids="form.member_ids"
                :initial-members="initialMembers"
                :identity-document-types="identityDocumentTypes"
                @update:member-ids="(ids) => (form.member_ids = ids)"
            />
            <InputError :message="form.errors.member_ids" class="col-span-6 mt-1" />
        </template>

        <template #actions>
            <Link :href="route('bib_organizations')" class="btn btn-danger mr-2">Cancelar</Link>
            <PrimaryButton :disabled="form.processing || !organizationFieldsEnabled">
                <IconLoader v-if="form.processing" class="w-4 h-4 mr-2 animate-spin inline" />
                {{ form.id ? 'Actualizar' : 'Guardar' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
