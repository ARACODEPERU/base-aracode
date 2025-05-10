<script setup>
    import { useForm } from '@inertiajs/vue3';
    import FormSection from '@/Components/FormSection.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';
    import Keypad from '@/Components/Keypad.vue';
    import swal from 'sweetalert';
    import { ref } from 'vue';
    import VueCollapsible from 'vue-height-collapsible/vue3';
    import iconCaretDown from '@/Components/vristo/icon/icon-caret-down.vue';
    import iconFolder from '@/Components/vristo/icon/icon-folder.vue';

    const treeview1 = ref([]);
    const toggleTreeview1 = (name) => {
        if (treeview1.value.includes(name)) {
            treeview1.value = treeview1.value.filter((d) => d != name);
        } else {
            treeview1.value.push(name);
        }
    };

    const props = defineProps({
        role: {
            type: Object,
            default: () => ({}),
        },
        sorted: {
            type: Object,
            default: () => ({}),
        },
        permissions: {
            type: Object,
            default: () => ({}),
        },
        roleHasPermissions: {
            type: Object,
            default: () => ({}),
        }
    });

    const form = useForm({
        name: props.role.name,
        permissions: props.roleHasPermissions
    });

    const updateRol = () => {
        form.put(route('roles.update',props.role.id), {
            errorBag: 'updateRol',
            preserveScroll: true,
            onSuccess: () => {
                swal('Rol actualizado con éxito.');
            },
        });
    };

    const selectAllCheckbox = (event) => {
        if(event.target.checked){
            const allPermissions = props.permissions;
            for (let i = 0; i < allPermissions.length; i++) {
                form.permissions[i] = allPermissions[i].name;
            }
        }else{
            form.permissions = [];
        }
    }
</script>

<template>
    <FormSection @submitted="updateRol">
        <template #title>
            Roles Detalles
        </template>

        <template #description>
            Editar Rol
        </template>

        <template #form>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="Nombre" value="Nombre" />
                <TextInput
                    id="Nombre"
                    v-model="form.name"
                    type="text"
                    class="block w-full mt-1"
                    autofocus
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>
            <div class="col-span-6">
                <div class="flex items-center mb-6">
                    <input @change="selectAllCheckbox($event)" id="checkboxAll" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label for="checkboxAll" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                        Todos
                    </label>
                </div>
                <ul class="font-semibold">
                    <li v-for="(row, index) in sorted" class="py-[5px]">
                        <button type="button" @click="toggleTreeview1(row.modulo_id)">
                            <icon-caret-down
                                class="w-5 h-5 text-primary inline relative -top-1 ltr:mr-2 rtl:ml-2"
                                :class="{ 'rotate-180': treeview1.includes(row.modulo_id) }"
                            />
                            <icon-folder class="text-primary inline relative -top-1 ltr:mr-2 rtl:ml-2" />
                            {{ row.modulo_description }}
                        </button>
                        <vue-collapsible :isOpen="treeview1.includes(row.modulo_id)">
                            <ul class="mt-2 ltr:pl-14 rtl:pr-14">
                                <li class="py-[5px]">
                                    <div v-if="row.permissions && row.permissions.length > 0">
                                        <div v-for="(item, key) in row.permissions" class="flex items-center space-x-4">
                                            <!-- From Uiverse.io by catraco -->
                                            <div class="can-container">
                                                <input :id="'checkbox'+index+key" v-model="form.permissions" :value="item.name" checked="checked" type="checkbox">
                                                <svg viewBox="0 0 576 512" height="1em" xmlns="http://www.w3.org/2000/svg" class="lock"><path d="M352 144c0-44.2 35.8-80 80-80s80 35.8 80 80v48c0 17.7 14.3 32 32 32s32-14.3 32-32V144C576 64.5 511.5 0 432 0S288 64.5 288 144v48H64c-35.3 0-64 28.7-64 64V448c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V256c0-35.3-28.7-64-64-64H352V144z"></path></svg>
                                                <svg viewBox="0 0 448 512" height="1em" xmlns="http://www.w3.org/2000/svg" class="lock-open"><path d="M144 144v48H304V144c0-44.2-35.8-80-80-80s-80 35.8-80 80zM80 192V144C80 64.5 144.5 0 224 0s144 64.5 144 144v48h16c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V256c0-35.3 28.7-64 64-64H80z"></path></svg>
                                            </div>
                                            <label :for="'checkbox'+index+key">{{ item.name }}</label>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </vue-collapsible>
                    </li>
                </ul>
            </div>
        </template>

        <template #actions>
            <Keypad>
                <template #botones>
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Actualizar
                    </PrimaryButton>
                    <a :href="route('roles.index')"  class="inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out ml-2">Ir al Listado</a>
                </template>
            </Keypad>
        </template>
    </FormSection>
</template>
<style>
/* From Uiverse.io by catraco */
/*------ Settings ------*/
.can-container {
    --color: #8b8b96;
    --size: 16px;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    cursor: pointer;
    font-size: var(--size);
    user-select: none;
    fill: var(--color);
}

.can-container .lock-open {
    position: absolute;
    animation: keyframes-fill .5s;
}

.can-container .lock {
    position: absolute;
    display: none;
    animation: keyframes-fill .5s;
}

/* ------ On check event ------ */
.can-container input:checked ~ .lock-open {
    display: none;
}

.can-container input:checked ~ .lock {
    display: block;
}

/* ------ Hide the default checkbox ------ */
.can-container input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

/* ------ Animation ------ */
@keyframes keyframes-fill {
    0% {
        transform: scale(0);
        opacity: 0;
    }

    50% {
        transform: scale(1.2);
    }
}
</style>
