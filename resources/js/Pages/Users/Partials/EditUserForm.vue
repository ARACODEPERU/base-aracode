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
    import swal from 'sweetalert';
    import {
        ConfigProvider, Select
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
        }
    });

    const form = useForm({
        name: props.xuser.name,
        email: props.xuser.email,
        password: props.xuser.password,
        local_id: props.xuser.local_id,
        role: props.xrole,
        person_id: props.xuser.person_id
    });

    const updateUser = () => {
        form.put(route('users.update', props.xuser.id), {
            errorBag: 'updateUser',
            preserveScroll: true,
            onSuccess: () => {
                swal('Usuario Modificado con éxito');
            }
        });
    };

    library.add(faTrashAlt);

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
                    <select v-model="form.local_id" id="stablishment" class="mt-1 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
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
                        autofocus
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
        </template>

        <template #actions>
            <Keypad>
                <template #botones>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Actualizar
                    </PrimaryButton>
                    <Link :href="route('users.index')"  class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                </template>
            </Keypad>
        </template>
    </FormSection>
</template>
