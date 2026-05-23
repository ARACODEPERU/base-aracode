<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

const props = defineProps({
    identityDocumentTypes: { type: Array, default: () => [] },
    countries: { type: Array, default: () => [] },
    author: { type: Object, default: null },
});

const form = useForm({
    id: props.author?.id || null,
    person_id: props.author?.person?.id || null,
    document_type_id: props.author?.person?.document_type_id || '1',
    number: props.author?.person?.number || '',
    names: props.author?.person?.names || '',
    father_lastname: props.author?.person?.father_lastname || '',
    mother_lastname: props.author?.person?.mother_lastname || '',
    gender: props.author?.person?.gender || null,
    birthdate: props.author?.person?.birthdate || null,
    country_id: props.author?.person?.country_id || null,
    biography: props.author?.biography || '',
    specialty: props.author?.specialty || '',
    webpage: props.author?.webpage || '',
});

const submit = () => {
    if (form.id) {
        form.post(route('bib_authors_update'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    } else {
        form.post(route('bib_authors_store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }
};

const setPersonData = (person) => {
    form.person_id = person.id;
    form.document_type_id = person.document_type_id;
    form.number = person.number;
    form.names = person.names;
    form.father_lastname = person.father_lastname;
    form.mother_lastname = person.mother_lastname;
    form.gender = person.gender;
    form.birthdate = person.birthdate;
    form.country_id = person.country_id;
    if (person.author) {
        form.id = person.author.id;
        form.biography = person.author.biography || '';
        form.specialty = person.author.specialty || '';
        form.webpage = person.author.webpage || '';
    }
};

defineExpose({ form, setPersonData });
</script>

<template>
    <FormSection @submitted="submit">
        <template #title>{{ form.id ? 'Editar autor' : 'Nuevo autor' }}</template>
        <template #description>
            Identificación de la persona y datos bibliográficos. Los campos con * son obligatorios.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="Tipo de Identificación *" />
                <select v-model="form.document_type_id" class="form-select w-full">
                    <option v-for="dt in identityDocumentTypes" :key="dt.id" :value="dt.id">
                        {{ dt.description }}
                    </option>
                </select>
                <InputError :message="form.errors.document_type_id" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="Número *" />
                <TextInput v-model="form.number" type="text" class="w-full" placeholder="N° de documento" />
                <InputError :message="form.errors.number" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="Nombres *" />
                <TextInput v-model="form.names" type="text" class="w-full" placeholder="Nombres" />
                <InputError :message="form.errors.names" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="Apellido Paterno *" />
                <TextInput v-model="form.father_lastname" type="text" class="w-full" placeholder="Apellido paterno" />
                <InputError :message="form.errors.father_lastname" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="Apellido Materno" />
                <TextInput v-model="form.mother_lastname" type="text" class="w-full" placeholder="Apellido materno" />
                <InputError :message="form.errors.mother_lastname" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="Género" />
                <select v-model="form.gender" class="form-select w-full">
                    <option :value="null">Seleccionar</option>
                    <option value="M">Masculino</option>
                    <option value="F">Femenino</option>
                </select>
                <InputError :message="form.errors.gender" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="Fecha de Nacimiento" />
                <TextInput v-model="form.birthdate" type="date" class="w-full" />
                <InputError :message="form.errors.birthdate" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="País" />
                <select v-model="form.country_id" class="form-select w-full">
                    <option :value="null">Seleccionar</option>
                    <option v-for="country in countries" :key="country.id" :value="country.id">
                        {{ country.description }}
                    </option>
                </select>
                <InputError :message="form.errors.country_id" />
            </div>

            <div class="col-span-6 border-t border-gray-200 dark:border-zinc-700 pt-4 mt-2">
                <p class="text-sm font-semibold text-gray-700 dark:text-neutral-200">Datos del autor</p>
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Especialidad" />
                <TextInput v-model="form.specialty" type="text" class="w-full" placeholder="Género literario, especialidad" />
                <InputError :message="form.errors.specialty" />
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Web / Portafolio" />
                <TextInput v-model="form.webpage" type="url" class="w-full" placeholder="https://..." />
                <InputError :message="form.errors.webpage" />
            </div>
            <div class="col-span-6">
                <InputLabel value="Biografía" />
                <textarea v-model="form.biography" rows="4" class="form-textarea w-full" placeholder="Biografía del autor" />
                <InputError :message="form.errors.biography" />
            </div>
        </template>

        <template #actions>
            <Link :href="route('bib_authors')" class="btn btn-danger mr-2">Cancelar</Link>
            <PrimaryButton :disabled="form.processing">
                <IconLoader v-if="form.processing" class="w-4 h-4 mr-2 animate-spin inline" />
                {{ form.id ? 'Actualizar Autor' : 'Guardar Autor' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
