<script setup>
import { useForm, Link } from '@inertiajs/vue3';
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
    <form @submit.prevent="submit" class="space-y-6">
        <div class="panel">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Tipo Documento -->
                <div>
                    <InputLabel value="Tipo de Identificación *" />
                    <select v-model="form.document_type_id" class="form-select w-full">
                        <option v-for="dt in identityDocumentTypes" :key="dt.id" :value="dt.id">
                            {{ dt.description }}
                        </option>
                    </select>
                    <InputError :message="form.errors.document_type_id" />
                </div>

                <!-- Número -->
                <div>
                    <InputLabel value="Número *" />
                    <TextInput type="text" v-model="form.number" class="w-full" placeholder="N° de documento" />
                    <InputError :message="form.errors.number" />
                </div>

                <!-- Nombres -->
                <div>
                    <InputLabel value="Nombres *" />
                    <TextInput type="text" v-model="form.names" class="w-full" placeholder="Nombres" />
                    <InputError :message="form.errors.names" />
                </div>

                <!-- Apellido Paterno -->
                <div>
                    <InputLabel value="Apellido Paterno *" />
                    <TextInput type="text" v-model="form.father_lastname" class="w-full" placeholder="Apellido paterno" />
                    <InputError :message="form.errors.father_lastname" />
                </div>

                <!-- Apellido Materno -->
                <div>
                    <InputLabel value="Apellido Materno" />
                    <TextInput type="text" v-model="form.mother_lastname" class="w-full" placeholder="Apellido materno" />
                    <InputError :message="form.errors.mother_lastname" />
                </div>

                <!-- Género -->
                <div>
                    <InputLabel value="Género" />
                    <select v-model="form.gender" class="form-select w-full">
                        <option :value="null">Seleccionar</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                    </select>
                    <InputError :message="form.errors.gender" />
                </div>

                <!-- Fecha de Nacimiento -->
                <div>
                    <InputLabel value="Fecha de Nacimiento" />
                    <TextInput type="date" v-model="form.birthdate" class="w-full" />
                    <InputError :message="form.errors.birthdate" />
                </div>

                <!-- País -->
                <div>
                    <InputLabel value="País" />
                    <select v-model="form.country_id" class="form-select w-full">
                        <option :value="null">Seleccionar</option>
                        <option v-for="country in countries" :key="country.id" :value="country.id">
                            {{ country.description }}
                        </option>
                    </select>
                    <InputError :message="form.errors.country_id" />
                </div>
            </div>
        </div>

        <div class="panel">
            <h3 class="text-lg font-semibold mb-4">Datos del Autor</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Especialidad -->
                <div>
                    <InputLabel value="Especialidad" />
                    <TextInput type="text" v-model="form.specialty" class="w-full" placeholder="Género literario, especialidad" />
                    <InputError :message="form.errors.specialty" />
                </div>

                <!-- Web -->
                <div>
                    <InputLabel value="Web / Portafolio" />
                    <TextInput type="url" v-model="form.webpage" class="w-full" placeholder="https://..." />
                    <InputError :message="form.errors.webpage" />
                </div>

                <!-- Biografía -->
                <div class="md:col-span-2">
                    <InputLabel value="Biografía" />
                    <textarea v-model="form.biography" rows="4" class="form-textarea w-full" placeholder="Biografía del autor"></textarea>
                    <InputError :message="form.errors.biography" />
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <Link :href="route('bib_authors')" class="btn btn-danger px-6 py-2">Cancelar</Link>
            <PrimaryButton type="submit" :disabled="form.processing">
                <IconLoader v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                {{ form.id ? 'Actualizar Autor' : 'Guardar Autor' }}
            </PrimaryButton>
        </div>
    </form>
</template>
