<script setup>
    import { useForm } from '@inertiajs/vue3';
    import FormSection from '@/Components/FormSection.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';
    import Keypad from '@/Components/Keypad.vue';
    import Swal2 from 'sweetalert2';

    const props = defineProps({
        roles: {
            type: Object,
            default: () => ({}),
        },
        modulos: {
            type: Object,
            default: () => ({}),
        },
        permission: {
            type: Object,
            default: () => ({}),
        },
        roleHasPermissions: {
            type: Object,
            default: () => ({}),
        },
        moduleIds: {
            type: Array,
            default: () => [],
        }
    });

    const form = useForm({
        name: props.permission.name,
        modulos: props.moduleIds,
        roles: props.roleHasPermissions
    });

    const updateRol = () => {
        form.put(route('permissions.update',props.permission.id), {
            errorBag: 'updateRol',
            preserveScroll: true,
            onSuccess: () => {
                Swal2.fire({
                    title: '¡Enhorabuena!',
                    text: 'Permiso actualizado con éxito.',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            },
        });
    };

    const selectAllCheckbox = (event) => {
        if(event.target.checked){
            const allRoles = props.roles;
            for (let i = 0; i < allRoles.length; i++) {
                form.roles[i] = allRoles[i].name;
            }
        }else{
            form.roles = [];
        }
    }

    const selectAllModules = (event) => {
        if(event.target.checked){
            const allModules = props.modulos;
            for (let i = 0; i < allModules.length; i++) {
                form.modulos[i] = allModules[i].identifier;
            }
        }else{
            form.modulos = [];
        }
    }

</script>

<template>
    <FormSection @submitted="updateRol">
        <template #title>
            Permiso Detalles
        </template>

        <template #description>
            Se recomienda usar un prefijo con el nombre del modulo. ejemplo: {modulo}_nombre_permiso
        </template>

        <template #form>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="Nombre" value="Nombre" />
                <TextInput
                    id="Nombre"
                    v-model="form.name"
                    type="text"
                    class="block w-full mt-1 bg-gray-100"
                    disabled
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>
             <div class="col-span-6 sm:col-span-6">
                 <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Módulos Asociados</label>
                 <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                     <div class="flex items-center mb-4">
                         <input @change="selectAllModules($event)" id="checkboxAllModules" type="checkbox" class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500 dark:focus:ring-green-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                         <label for="checkboxAllModules" class="ml-2 mb-0 text-sm font-medium text-gray-900 dark:text-gray-300">
                             Todos los módulos
                         </label>
                     </div>
                     <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                         <template v-for="(module, index) in modulos">
                             <div class="flex items-center p-2 bg-white dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                 <input v-model="form.modulos" :value="module.identifier" :id="'module'+index" type="checkbox" class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500 dark:focus:ring-green-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                 <label :for="'module'+index" class="ml-3 mb-0 text-sm font-medium text-gray-900 dark:text-gray-300 cursor-pointer">
                                     <div class="font-semibold">{{ module.identifier }}</div>
                                     <div class="text-xs text-gray-600 dark:text-gray-400">{{ module.description }}</div>
                                 </label>
                             </div>
                         </template>
                     </div>
                 </div>
             </div>
             <div class="col-span-6 sm:col-span-6">
                 <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Roles Asociados</label>
                 <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                     <div class="flex items-center mb-4">
                         <input @change="selectAllCheckbox($event)" id="checkboxAll" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                         <label for="checkboxAll" class="ml-2 mb-0 text-sm font-medium text-gray-900 dark:text-gray-300">
                             Todos los roles
                         </label>
                     </div>
                     <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                         <template v-for="(row, index) in roles">
                             <div class="flex items-center p-2 bg-white dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                 <input v-model="form.roles" :value="row.name" :id="'checkbox'+index" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                 <label :for="'checkbox'+index" class="ml-2 mb-0 text-sm font-medium text-gray-900 dark:text-gray-300 cursor-pointer">{{ row.name }}</label>
                             </div>
                         </template>
                     </div>
                 </div>
             </div>
        </template>

        <template #actions>
            <Keypad>
                <template #botones>
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Actualizar
                    </PrimaryButton>
                    <a :href="route('permissions.index')"  class="inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out ml-2">Ir al Listado</a>
                </template>
            </Keypad>
        </template>
    </FormSection>
</template>
