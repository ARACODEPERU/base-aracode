<script setup>
    import { useForm, Link, router } from '@inertiajs/vue3';
    import { Select } from 'ant-design-vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import InputError from '@/Components/InputError.vue';
    import { ref, onMounted } from 'vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import Swal2 from 'sweetalert2';
    import iconTrash from '@/Components/vristo/icon/icon-trash.vue';

    const props = defineProps({
        student:{
            type: Object,
            default : () => ({})
        },
        subscriptions:{
            type: Object,
            default : () => ({})
        },
        subscriptionStudent:{
            type: Object,
            default : () => ({})
        },
    });

    const form = useForm({
        subscription_id: null,
        student_id: props.student.id
    });
    
    const dataSubscriptions = ref([]);

    onMounted(() => {
        dataSubscriptions.value = props.subscriptions.map((obj) => ({
            value: obj.id,
            label: `${obj.description} / precio: ${JSON.parse(obj.prices)[0].amount}`
        }));
    });

    const filterOption = (input, option) => {
        return option.label.toLowerCase().indexOf(input.toLowerCase()) >= 0;
    };

    const saveSubscription = () => {
        form.post(route('aca_students_subscriptions_store'), {
            errorBag: 'saveSubscription',
            preserveScroll: true,
            onSuccess: () => {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se registró correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                form.reset()
            },
        });
    }

    const destroySubscription = (student_id, subscription_id) => {
        Swal2.fire({
            title: '¿Estas seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Eliminar!',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            padding: '2em',
            customClass: 'sweet-alerts',
            preConfirm: () => {
                return axios.delete(route('aca_students_subscriptions_destroy', [student_id, subscription_id])).then((res) => {
                    if (!res.data.success) {
                        Swal2.showValidationMessage(res.data.message)
                    }
                    return res
                });
            },
            allowOutsideClick: () => !Swal2.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se Eliminó correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                router.visit(route('aca_students_registrations_create', props.student.id), { replace: true, method: 'get' });
            }
        });
    };
</script>
<template>
    <div class="panel">
        <form @submit.prevent="saveSubscription"> 
            <div class="space-y-6 mb-4">
                <div class="mb-2">
                    <InputLabel for="subscription_id" value="Planes de suscripcion *" />
                    <Select 
                        show-search
                        v-model:value="form.subscription_id" 
                        class="w-full mb-2"
                        placeholder="Seleccionar"
                        :options="dataSubscriptions"
                        :filter-option="filterOption"
                    />
                    <InputError :message="form.errors.subscription_id" class="mt-1" />
                    <InputError :message="form.errors.student_id" class="mt-1" />
                </div>
            </div>

           <div>
            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                <svg v-show="form.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                    <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                </svg>
                Guardar
            </PrimaryButton>
           </div>
            
        </form>
    </div>
    <div
        v-for="item in subscriptionStudent"
        class="panel"
    >
        <div class="flex items-center gap-4">
            <div class="flex-1 font-medium dark:text-primary-200">
                <div>{{ item.subscription.title }}</div>
                <p>{{ item.subscription.description }}</p>
            </div>
            <button
                @click="destroySubscription(item.student_id,item.subscription_id)"
                type="button"
                class="absolute top-2 right-2 px-3 py-2 text-xs font-medium text-center text-white bg-red-700 rounded-lg hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800"
            >
                <icon-trash class="w-4 h-4" />
            </button>
        </div>
    </div>
</template>