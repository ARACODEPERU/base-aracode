<script setup>
    import { useForm, Link } from '@inertiajs/vue3';
    import FormSection from '@/Components/FormSection.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';
    import { library } from "@fortawesome/fontawesome-svg-core";
    import { faTrashAlt } from "@fortawesome/free-solid-svg-icons";
    import Keypad from '@/Components/Keypad.vue';
    import swal from 'sweetalert2';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import SuccessButton  from '@/Components/SuccessButton.vue';
    import { ref } from 'vue';

    import {
        ConfigProvider, Select, Input, InputSearch
    } from 'ant-design-vue';

    const props = defineProps({
        establishments: {
            type: Object,
            default: () => ({}),
        },
        xuser:{
            type: Object,
            default: () => ({}),
        },
		xrole:{
            type: Object,
            default: () => ({}),
        },
        roles:{
            type: Object,
            default: () => ({}),
        },
        person:{
            type: Object,
            default: () => ({}),
        },
        identityDocumentTypes: {
            type: Object,
            default: () => ({}),
        },
        ubigeo: {
            type: Object,
            default: () => ({})
        },
    });

    const form = useForm({
        name: props.xuser.name,
        email: props.xuser.email,
        password: null,
        local_id: props.xuser.local_id,
        role: props.xrole,
        person_id: props.xuser.person_id
    });

    const updateUser = () => {
        form.put(route('users.update', props.xuser.id), {
            errorBag: 'updateUser',
            preserveScroll: true,
            onSuccess: () => {
                swal.fire({
                    title: 'Enhorabuena',
                    text: 'Usuario Modificado con éxito',
                    icon: 'info',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }
        });
    };

    library.add(faTrashAlt);

    const displayModalPerson = ref(false);
    const searchLoader = ref(false);

    const personForm = useForm({
        user_id: props.xuser.id,
        id: props.person?.id ?? null,
        document_type: props.person?.document_type_id,
        number: props.person?.number ?? null,
        telephone: props.person?.telephone ?? null,
        email: props.person?.email ?? null,
        image: null,
        image_preview: props.person?.image ?? null,
        address: props.person?.address ?? null,
        ubigeo: props.person?.ubigeo ?? null,
        birthdate: props.person?.birthdate ?? null,
        names: props.person?.names ?? props.xuser.name ?? null,
        father_lastname: props.person?.father_lastname ?? null,
        mother_lastname: props.person?.mother_lastname ?? null,
        ubigeo_description: props.person?.city ?? null,
        gender: props.person?.gender ?? 'M',
    });

    const openModalPersonUser = () => {
        displayModalPerson.value = true;
    }

    const closeModalPersonUser = () => {
        displayModalPerson.value = false;
    }

    const searchPersonExists = () => {
        searchLoader.value = true;
        axios({
            method: 'post',
            url: route('search_person_number'),
            data: personForm
        }).then(function (res) {
            if (res.data.status) {
                personForm.id = res.data.person.id;
                personForm.number = res.data.person.number;
                personForm.telephone = res.data.person.telephone;
                personForm.full_name = res.data.person.full_name;
                personForm.email = res.data.person.email;
                personForm.address = res.data.person.address;
                personForm.ubigeo_description = res.data.person.ubigeo_description
                personForm.names = res.data.person.names
                personForm.father_lastname = res.data.person.father_lastname
                personForm.mother_lastname = res.data.person.mother_lastname
                personForm.birthdate = res.data.person.birthdate
                personForm.ubigeo = res.data.person.ubigeo
                personForm.gender = res.data.person.gender
            } else {
                personForm.errors.document_type = res.data.document_type;
                personForm.errors.number = res.data.number;
                swal.fire({
                    title: 'Información Importante',
                    text: res.data.alert,
                    icon: 'info',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }

        }).finally(() => {
            searchLoader.value = false;
        });
    }

    const savePersonUser = () => {
        personForm.post(route('user_persom_info_store'), {
            errorBag: 'savePersonUser',
            preserveScroll: true,
            onSuccess: () => {
                swal.fire({
                    title: 'Enhorabuena',
                    text: 'Información personal guardada con éxito',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });

            },
            onFinish: visit => {
                displayModalPerson.value = false;
                form.name = personForm.names;
                form.email = personForm.email;
            },
        });
    }

    const filterOption = (input, option) => {
        const inputValueLower = input.toLowerCase();
        const optionTitleLower = option.label.toLowerCase();
        return optionTitleLower.includes(inputValueLower);
    };

    const handleChange = (value,option) => {
        //console.log(`selected ${value}: `, option);
        personForm.ubigeo_description = option.label
    };
</script>

<template>
    <FormSection @submitted="updateUser">
        <template #title>
            Datos de Usuario
        </template>

        <template #description>
            <p class="mb-4">Editar usuario, los campos con * son necesarios</p>
            <div
                class="
                    relative
                    flex
                    items-center
                    border
                    p-3.5
                    rounded
                    before:absolute before:top-1/2
                    ltr:before:left-0
                    rtl:before:right-0 rtl:before:rotate-180
                    before:-mt-2 before:border-l-8 before:border-t-8 before:border-b-8 before:border-t-transparent before:border-b-transparent before:border-l-inherit
                    text-warning
                    bg-warning-light
                    !border-warning
                    ltr:border-l-[64px]
                    rtl:border-r-[64px]
                    dark:bg-warning-dark-light
                "
                >
                <span class="absolute ltr:-left-11 rtl:-right-11 inset-y-0 text-white w-6 h-6 m-auto">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                        <path fill="currentColor" d="M64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l448 0c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64L64 32zm80 256l64 0c44.2 0 80 35.8 80 80c0 8.8-7.2 16-16 16L80 384c-8.8 0-16-7.2-16-16c0-44.2 35.8-80 80-80zm-32-96a64 64 0 1 1 128 0 64 64 0 1 1 -128 0zm256-32l128 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-128 0c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 64l128 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-128 0c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 64l128 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-128 0c-8.8 0-16-7.2-16-16s7.2-16 16-16z"/>
                    </svg>
                </span>
                <span class="ltr:pr-2 rtl:pl-2"><strong class="ltr:mr-1 rtl:ml-1">Advertencia!</strong>La cuenta de usuario no está vinculada a una persona o le falta información necesaria para el funcionamiento óptimo del sistema.</span>
                </div>
        </template>

        <template #form>
            <ConfigProvider>
                <div class="col-span-6 sm:col-span-3">
                    <InputLabel for="stablishment" value="Establecimiento" />
                    <select v-model="form.local_id" id="stablishment" class="form-select">
                        <template v-for="(establishment, index) in props.establishments" :key="index">
                            <option :value="establishment.id">{{ establishment.description }}</option>
                        </template>
                    </select>
                    <InputError :message="form.errors.local_id" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <InputLabel for="name" value="Nombre" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="block w-full mt-1"
                    />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <InputLabel for="email" value="Email" />
                    <TextInput
                        id="email"
                        v-model="form.email"
                        type="text"
                        class="block w-full mt-1"
                    />
                    <InputError :message="form.errors.email" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <InputLabel for="password" value="Contraseña" />
                    <TextInput
                        id="password"
                        v-model="form.password"
                        type="text"
                        class="block w-full mt-1"

                    />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <InputLabel for="rol" value="Rol" />
                    <Select
                        v-model:value="form.role"
                        mode="multiple"
                        style="width: 100%"
                        placeholder="Please select"
                        :options="roles.map((obj) => ({ value: obj.name, label: obj.name }))"
                    ></Select>
                    <InputError :message="form.errors.rol" class="mt-2" />
                </div>
            </ConfigProvider>
            <ModalLarge :show="displayModalPerson" :onClose="closeModalPersonUser" :icon="'/img/usuario.png'">
                <template #title>{{ personForm.names }}</template>
                <template #message>Información Personal</template>
                <template #content>
                    <div class="grid grid-cols-6 gap-6">
                        <div class="col-span-6 sm:col-span-2 ">
                            <InputLabel for="document_type_id" value="Tipo *" />
                            <Select
                                style="width: 100%;"
                                v-model:value="personForm.document_type"
                                :options="identityDocumentTypes.map((obj) => ({ value: obj.id, label: obj.description }))"
                            ></Select>
                            <InputError :message="personForm.errors.document_type" class="mt-2" />
                        </div>
                        <div class="col-span-6 sm:col-span-2 ">
                            <InputLabel for="number" value="Número *" />
                            <input-search
                                v-model:value="personForm.number"
                                placeholder="Buscar"
                                style="width: 100%"
                                :loading="searchLoader"
                                @search="searchPersonExists"
                            />
                            <InputError :message="personForm.errors.number" class="mt-2" />
                        </div>
                        <div class="col-span-6 sm:col-span-2 ">
                            <InputLabel for="birthdate" value="Fecha de nacimiento *" />
                            <Input v-model:value="personForm.birthdate" class="w-full" type="date" />
                            <InputError :message="personForm.errors.birthdate" class="mt-2" />
                        </div>
                        <div class="col-span-6 sm:col-span-3 ">
                            <InputLabel for="ubigeo" value="Ciudad *" />
                            <Select
                                v-model:value="personForm.ubigeo"
                                show-search
                                placeholder="Seleccione una ciudad"
                                style="width: 100%"
                                :options="ubigeo.map((row) => ({value: row.district_id, label: row.department_name+'-'+row.province_name+'-'+row.district_name,district_name: row.district_name }))"
                                :filter-option="filterOption"
                                @change="handleChange"
                            ></Select>
                            <InputError :message="personForm.errors.ubigeo" class="mt-2" />
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel for="address" value="Dirección *" />
                            <Input
                                id="address"
                                v-model:value="personForm.address"
                                type="text"
                            />
                            <InputError :message="personForm.errors.address" class="mt-2" />
                        </div>
                        <div class="col-span-6 sm:col-span-3 ">
                            <InputLabel for="names" value="Nombres *" />
                            <Input
                                id="names"
                                v-model:value="personForm.names"
                                type="text"
                            />
                            <InputError :message="personForm.errors.names" class="mt-2" />
                        </div>
                        <div class="col-span-6 sm:col-span-3 ">
                            <InputLabel for="father_lastname" value="Apellido Paterno *" />
                            <Input
                                id="father_lastname"
                                v-model:value="personForm.father_lastname"
                                type="text"
                            />
                            <InputError :message="personForm.errors.father_lastname" class="mt-2" />
                        </div>
                        <div class="col-span-6 sm:col-span-3 ">
                            <InputLabel for="mother_lastname" value="Apellido Materno *" />
                            <Input
                                id="mother_lastname"
                                v-model:value="personForm.mother_lastname"
                                type="text"
                            />
                            <InputError :message="personForm.errors.mother_lastname" class="mt-2" />
                        </div>
                        <div class="col-span-6 sm:col-span-3 ">
                            <InputLabel for="telephone" value="Teléfono *" />
                            <Input
                                id="telephone"
                                v-model:value="personForm.telephone"
                                type="text"
                            />
                            <InputError :message="personForm.errors.telephone" class="mt-2" />
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel for="email" value="Email *" />
                            <Input
                                id="email"
                                v-model:value="personForm.email"
                                type="text"
                            />
                            <InputError :message="personForm.errors.email" class="mt-2" />
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel for="gender" value="Genero *" />
                            <div class="space-x-4">
                                <label class="inline-flex">
                                    <input v-model="personForm.gender" type="radio" value="M" name="square_radio_g" class="form-radio rounded-none" />
                                    <span>Masculino</span>
                                </label>
                                <label class="inline-flex">
                                    <input v-model="personForm.gender" type="radio" value="F" name="square_radio_g" class="form-radio text-success rounded-none" />
                                    <span>Femenino</span>
                                </label>
                            </div>
                            <InputError :message="personForm.errors.gender" class="mt-2" />
                        </div>
                    </div>
                </template>
                <template #buttons>
                    <InputError :message="personForm.errors.id" />
                    <PrimaryButton @click="savePersonUser" :class="{ 'opacity-25': personForm.processing }" :disabled="form.processing">
                        Guardar cambios
                    </PrimaryButton>
                </template>
            </ModalLarge>
        </template>

        <template #actions>
            <Keypad>
                <template #botones>
                    <SuccessButton @click="openModalPersonUser" type="button" class="">Información Personal</SuccessButton>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Actualizar
                    </PrimaryButton>
                    <Link :href="route('users.index')"  class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                </template>
            </Keypad>
        </template>
    </FormSection>
</template>
