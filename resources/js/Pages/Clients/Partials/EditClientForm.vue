<script setup>
    import { useForm } from '@inertiajs/vue3';
    import FormSection from '@/Components/FormSection.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';

    import { Select, TreeSelect } from 'ant-design-vue';

    const props = defineProps({
        client: {
            type: Object,
            default: () => ({}),
        },
        identityDocumentTypes: {
            type: Object,
            default: () => ({}),
        },
        ubigeo: {
            type: Object,
            default: () => ({}),
        }
    });

    const form = useForm({
        full_name: props.client.full_name,
        number: props.client.number,
        document_type_id: props.client.document_type_id,
        telephone: props.client.telephone,
        email: props.client.email,
        address: props.client.address,
        ubigeo: props.client.ubigeo,
        gender: props.client.gender,
        birthdate: props.client.birthdate,
    });

    const editClient = () => {
        form.put(route('clients.update', props.client.id), {
            //forceFormData: true,
            errorBag: 'editClient',
            preserveScroll: true,
        });
    };

    const filterOption = (input, option) => {
        const inputValueLower = input.toLowerCase();
        const optionTitleLower = option.label.toLowerCase();
        return optionTitleLower.includes(inputValueLower);
    };

</script>

<template>
    <FormSection @submitted="editClient">
        <template #title>
            Cliente Detalles
        </template>

        <template #description>
            <span>Editar cliente, los campos con * son obligatorios. También puedes verificar si el cliente ya existe en </span>

            <a @click="openSwal2Search" href="javascript:;" class="text-primary font-semibold hover:underline group">
                la base de datos
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="ltr:ml-1 rtl:mr-1 inline-block relative transition-all duration-300 group-hover:translate-x-2 rtl:group-hover:-translate-x-2 rtl:rotate-180">
                    <path d="M4 12H20M20 12L14 6M20 12L14 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </a>
        </template>

        <template #form>
            <div class="col-span-4 sm:col-span-2">
                <InputLabel value="Tipo de Documento *" class="mb-1" />
                <select class="form-select text-white-dark"
                    v-model="form.document_type_id"
                    >
                        <option value="" selected>Seleccionar</option>
                        <option v-for="(item, key) in identityDocumentTypes" :value="item.id">{{ item.description }}</option>
                    </select>
                <InputError :message="form.errors.document_type_id" class="mt-2" />
             </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="number" value="Número de Doc. *" />
                <TextInput
                    id="number"
                    v-model="form.number"
                    type="number"
                    autofocus
                />
                <InputError :message="form.errors.number" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="telephone" value="Teléfono" />
                <TextInput
                    id="telephone"
                    v-model="form.telephone"
                    type="text"
                    class="block w-full mt-1"
                    autofocus
                />
                <InputError :message="form.errors.telephone" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel v-if="form.document_type_id==6" for="full_name" value="Razón Social" />
                <InputLabel v-else for="full_name" value="Nombres *" />
                <TextInput
                    id="full_name"
                    v-model="form.full_name"
                    type="text"
                    class="block w-full mt-1"
                    autofocus
                />
                <InputError :message="form.errors.full_name" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-2 ">
                <InputLabel for="birthdate" value="Fecha de nacimiento *" />
                <TextInput
                    id="birthdate"
                    v-model="form.birthdate"
                    type="date"
                    class="block w-full mt-1"

                />
                <InputError :message="form.errors.birthdate" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="gender" value="Genero *" />
                <div class="space-x-4">
                    <label class="inline-flex">
                        <input v-model="form.gender" type="radio" value="M" name="square_radio_g" class="form-radio rounded-none" checked />
                        <span>Masculino</span>
                    </label>
                    <label class="inline-flex">
                        <input v-model="form.gender" type="radio" value="F" name="square_radio_g" class="form-radio text-success rounded-none" />
                        <span>Femenino</span>
                    </label>
                </div>
                <InputError :message="form.errors.gender" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="email" value="Email *" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="block w-full mt-1"
                    autofocus
                />
                <InputError :message="form.errors.email" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="address" value="Dirección *" />
                <TextInput
                    id="address"
                    v-model="form.address"
                    type="text"
                    class="block w-full mt-1"
                    autofocus
                />
                <InputError :message="form.errors.address" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="city" value="Ciudad *" />
                <Select
                    v-model:value="form.ubigeo"
                    style="width: 100%"
                    :options="ubigeo.map((obj) => ({value:obj.district_id,label: obj.department_name+'-'+obj.province_name+'-'+obj.district_name,distric_name: obj.district_name }))"
                    show-search
                    :filter-option="filterOption"
                ></Select>
                <InputError :message="form.errors.address" class="mt-2" />
            </div>

        </template>

        <template #actions>
            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Actualizar
            </PrimaryButton>
            <a :href="route('clients.index')"  class="inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out ml-2">Ir al Listado</a>
        </template>
    </FormSection>
</template>
