<script setup>
    import { ref, watch } from 'vue';
    import { message } from 'ant-design-vue';
    import { useForm, Link } from '@inertiajs/vue3';
    import Swal from 'sweetalert2';
    import iconXCircle from '@/Components/vristo/icon/icon-x-circle.vue';
    import iconCircleCheck from '@/Components/vristo/icon/icon-circle-check.vue';

    const props = defineProps({
        subscription: {
            type: Object,
            default: () => ({}),
        },
        personInvoice: {
            type: Object,
            default: () => ({}),
        }
    });

    const baseUrl = assetUrl;

    const getImage = (path) => {
        return baseUrl + 'storage/'+ path;
    }

    const formPerInvoice = ref({
        document_type: props.personInvoice.document_type_id == 6 ? 1 : 2,
        dni: props.personInvoice.number,
        ruc: props.personInvoice.document_type_id == 6 ? props.personInvoice.number : null,
        nombreCompleto: props.personInvoice.full_name,
        razonSocial: props.personInvoice.document_type_id == 6 ? props.personInvoice.full_name : null,
        email: props.personInvoice.email,
        statusRuc: null,
        conditionRuc: null
    });

    const apiesLoading = ref(false);
    const activeBtnPaymer = ref(false);

    const searchApispe = () => {
        apiesLoading.value = true;
        axios.post(route('sales_search_person_apies'), {
            document_type: 6,
            number: formPerInvoice.value.ruc
        }).then((res) => {
            if(res.data.success){
                formPerInvoice.value.razonSocial =  res.data.person.razonSocial;
                formPerInvoice.value.statusRuc = res.data.person.estado;
                formPerInvoice.value.conditionRuc = res.data.person.condicion;
                if(formPerInvoice.value.statusRuc == 'ACTIVO' && formPerInvoice.value.conditionRuc == 'HABIDO'){
                    activeBtnPaymer.value = false;
                }
            }else{
                Swal.fire({
                    icon: 'error',
                    text: res.data.error,
                    padding: '2em',
                    customClass: 'sweet-alerts',
                })
            }
        }).finally(()=> {
            apiesLoading.value = false;
        });
    }

    watch(() => {
        if(formPerInvoice.value.document_type == 1){
            activeBtnPaymer.value = true;
        }else{
            formPerInvoice.value.ruc = null;
            formPerInvoice.value.razonSocial = null;
            formPerInvoice.value.email = props.personInvoice.email;
            formPerInvoice.value.statusRuc = null;
            formPerInvoice.value.conditionRuc = null;
            activeBtnPaymer.value = false;
        }
    });
</script>
<template>
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        <!-- Title -->
        <div class="max-w-2xl mx-auto text-center">
            <h2 class="text-2xl font-bold md:text-4xl md:leading-tight dark:text-white">Formulario de pago</h2>
            <p class="mt-1 text-gray-600 dark:text-neutral-400">pago seguro en aracode.</p>
        </div>
        <!-- End Title -->
        <div class="flex flex-wrap justify-center gap-6">
            <div class="flex flex-col rounded-xl p-8 w-[800px]">
                <ol class="flex items-center w-full text-sm font-medium text-center text-gray-500 dark:text-gray-400 sm:text-base">
                    <li class="flex md:w-full items-center text-yellow-500 dark:text-yellow-600 sm:after:content-[''] after:w-full after:h-1 after:border-b after:border-gray-200 after:border-1 after:hidden sm:after:inline-block after:mx-6 xl:after:mx-10 dark:after:border-gray-700">
                        <span class="flex items-center after:content-['/'] sm:after:hidden after:mx-2 after:text-gray-200 dark:after:text-gray-500">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                            </svg>
                            Acceso
                        </span>
                    </li>
                    <li class="flex md:w-full items-center after:content-[''] after:w-full after:h-1 after:border-b after:border-gray-200 after:border-1 after:hidden sm:after:inline-block after:mx-6 xl:after:mx-10 dark:after:border-gray-700">
                        <span class="flex items-center text-blue-600 dark:text-blue-500 after:content-['/'] sm:after:hidden after:mx-2 after:text-gray-200 dark:after:text-gray-500">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 me-2.5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                <path d="M192 0c-41.8 0-77.4 26.7-90.5 64L64 64C28.7 64 0 92.7 0 128L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64l-37.5 0C269.4 26.7 233.8 0 192 0zm0 64a32 32 0 1 1 0 64 32 32 0 1 1 0-64zM305 273L177 401c-9.4 9.4-24.6 9.4-33.9 0L79 337c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L271 239c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/>
                            </svg>
                            Verificación
                        </span>
                    </li>
                    <li class="flex md:w-full items-center after:content-[''] after:w-full after:h-1 after:border-b after:border-gray-200 after:border-1 after:hidden sm:after:inline-block after:mx-6 xl:after:mx-10 dark:after:border-gray-700">
                        <span class="flex items-center after:content-['/'] sm:after:hidden after:mx-2 after:text-gray-200 dark:after:text-gray-500">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 me-2.5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                                <path d="M64 32C28.7 32 0 60.7 0 96l0 32 576 0 0-32c0-35.3-28.7-64-64-64L64 32zM576 224L0 224 0 416c0 35.3 28.7 64 64 64l448 0c35.3 0 64-28.7 64-64l0-192zM112 352l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-64 0c-8.8 0-16-7.2-16-16s7.2-16 16-16zm112 16c0-8.8 7.2-16 16-16l128 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-128 0c-8.8 0-16-7.2-16-16z"/>
                            </svg>
                            Pagar
                        </span>
                    </li>
                    <li class="flex items-center">
                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 me-2.5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM164.1 325.5C182 346.2 212.6 368 256 368s74-21.8 91.9-42.5c5.8-6.7 15.9-7.4 22.6-1.6s7.4 15.9 1.6 22.6C349.8 372.1 311.1 400 256 400s-93.8-27.9-116.1-53.5c-5.8-6.7-5.1-16.8 1.6-22.6s16.8-5.1 22.6 1.6zM144.4 208a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm192-32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/>
                        </svg>
                        Hecho
                    </li>
                </ol>
                <!-- step uno -->
                <div class="flex justify-center p-8">
                    <div class="w-full p-4 bg-white border border-gray-200 rounded-lg shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700">
                        <div class="flex flex-col items-center pb-4 text-center ">
                            <img v-if="$page.props.auth.user.avatar" class="w-24 h-24 mb-3 rounded-full shadow-lg" :src="getImage($page.props.auth.user.avatar)" alt="Bonnie image"/>
                            <img v-else class="w-24 h-24 mb-3 rounded-full shadow-lg" :src="`https://ui-avatars.com/api/?name=${personInvoice.full_name}&size=150&rounded=true`" alt="Bonnie image"/>
                        </div>
                        <h5 class="mb-2 text-3xl font-bold text-center text-gray-900 dark:text-white">
                            {{ personInvoice.full_name }}
                        </h5>
                        <!-- horizontal form label sizing -->
                        <div class="mt-6 mb-10">
                            <!-- simple pills -->
                            <div class="sm:grid sm:grid-cols-2 gap-6 w-full mb-6">
                                <div class="flex items-center ps-4 border border-gray-200 rounded-sm dark:border-gray-700">
                                    <label class="inline-flex py-4 ms-2">
                                        <input v-model="formPerInvoice.document_type" type="radio" :value="2" name="square_radio" class="form-radio rounded-none" />
                                        <span>Boleta electrónica</span>
                                    </label>
                                </div>
                                <div class="flex items-center ps-4 border border-gray-200 rounded-sm dark:border-gray-700">
                                    <label class="inline-flex py-4 ms-2">
                                        <input v-model="formPerInvoice.document_type" type="radio" :value="1" name="square_radio" class="form-radio rounded-none" />
                                        <span>Factura electrónica</span>
                                    </label>
                                </div>
                            </div>
                            <div v-if="formPerInvoice.document_type == 2">
                                <h4 class="font-semibold text-2xl mb-4">Datos para la boleta</h4>
                                <form class="space-y-5" id="boleta">
                                    <div class="grid grid-cols-6 gap-4">
                                        <label for="dni" class="sm:col-span-2">DNI</label>
                                        <input v-model="formPerInvoice.dni" id="dni" type="text"  class="form-input sm:col-span-4" />
                                    </div>
                                    <div class="grid grid-cols-6 gap-4">
                                        <label for="nombrecompleto" class="sm:col-span-2">Nombre Completo</label>
                                        <input v-model="formPerInvoice.nombreCompleto" id="nombrecompleto" type="text" class="form-input sm:col-span-4" />
                                    </div>
                                    <div class="grid grid-cols-6 gap-4">
                                        <label for="email" class="sm:col-span-2">Email</label>
                                        <input v-model="formPerInvoice.email" id="email" type="email" class="form-input sm:col-span-4" />
                                    </div>
                                </form>
                            </div>
                            <div v-if="formPerInvoice.document_type == 1">
                                <h4 class="font-semibold text-2xl mb-4">Datos para la factura</h4>
                                <form class="space-y-5" id="boleta">
                                    <div class="grid grid-cols-6 gap-4 items-center">
                                        <label for="dni" class="sm:col-span-2">RUC</label>
                                        <div class="col-span-4">
                                            <div class="flex">
                                                <input v-model="formPerInvoice.ruc" id="addonsRight" type="text" placeholder="" class="form-input ltr:rounded-r-none rtl:rounded-l-none" />
                                                <button
                                                    @click="searchApispe"
                                                    type="button"
                                                    class="btn btn-warning ltr:rounded-l-none rtl:rounded-r-none"
                                                    :class="{ 'opacity-25': apiesLoading }" :disabled="apiesLoading"
                                                >
                                                    <svg v-show="apiesLoading" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                                    </svg>
                                                    Buscar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-6 gap-4 items-center">
                                        <label for="nombrecompleto" class="sm:col-span-2">Razón social</label>
                                        <input v-model="formPerInvoice.razonSocial" id="nombrecompleto" type="text" class="form-input sm:col-span-4" />
                                    </div>
                                    <div class="grid grid-cols-6 gap-4 items-center">
                                        <label for="email" class="sm:col-span-2">Email</label>
                                        <input v-model="formPerInvoice.email" id="email" type="email" class="form-input sm:col-span-4" />
                                    </div>
                                    <div class="grid grid-cols-6 gap-4 items-center">
                                        <label for="email" class="sm:col-span-2">Estado</label>
                                        <div class="flex sm:col-span-4 gap-4">
                                            <span v-if="formPerInvoice.statusRuc" class="badge py-1 my-4 flex justify-between items-center text-xs"
                                                :class="formPerInvoice.statusRuc === 'ACTIVO' ? 'bg-success' : 'bg-danger'"
                                            >
                                                <span class="ltr:ml-2 rtl:mr-2">{{ formPerInvoice.statusRuc }}</span>
                                                <span class="ltr:ml-4 rtl:mr-4 cursor-pointer hover:opacity-90">
                                                    <icon-circle-check v-if="formPerInvoice.statusRuc === 'ACTIVO'" class="w-4 h-4" />
                                                    <icon-x-circle v-else class="w-4 h-4" />
                                                </span>
                                            </span>
                                            <span v-if="formPerInvoice.conditionRuc" class="badge py-1 my-4 flex justify-between items-center text-xs"
                                                :class="formPerInvoice.conditionRuc === 'HABIDO' ? 'bg-success' : 'bg-danger'"
                                            >
                                                <span class="ltr:ml-2 rtl:mr-2">{{ formPerInvoice.conditionRuc }}</span>
                                                <span class="ltr:ml-2 rtl:mr-2 cursor-pointer hover:opacity-90">
                                                    <icon-circle-check v-if="formPerInvoice.conditionRuc === 'HABIDO'" class="w-4 h-4" />
                                                    <icon-x-circle v-else class="w-4 h-4" />
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>

                        <div class="items-center justify-center space-y-4 sm:flex sm:space-y-0 sm:space-x-4 rtl:space-x-reverse">
                            <a href="#" class="btn btn-danger">
                                <svg class="me-3 w-7 h-7" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path d="M288 32c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 224c0 17.7 14.3 32 32 32s32-14.3 32-32l0-224zM143.5 120.6c13.6-11.3 15.4-31.5 4.1-45.1s-31.5-15.4-45.1-4.1C49.7 115.4 16 181.8 16 256c0 132.5 107.5 240 240 240s240-107.5 240-240c0-74.2-33.8-140.6-86.6-184.6c-13.6-11.3-33.8-9.4-45.1 4.1s-9.4 33.8 4.1 45.1c38.9 32.3 63.5 81 63.5 135.4c0 97.2-78.8 176-176 176s-176-78.8-176-176c0-54.4 24.7-103.1 63.5-135.4z"/>
                                </svg>
                                <div class="text-left rtl:text-right">
                                    <div class="mb-1 text-xs">Cerrar sesion</div>
                                    <div class="-mt-1 font-sans text-sm font-semibold">Regresar al login</div>
                                </div>
                            </a>
                            <Link
                                :href="route('academic_step_pay', subscription.id)"
                                method="put" :data="{ personInvoice: formPerInvoice }"
                                class="btn btn-primary"
                                :class="{ 'opacity-25': activeBtnPaymer }"
                                :disabled="activeBtnPaymer"
                                as="button"
                            >
                                <svg class="me-3 w-7 h-7" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path d="M502.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-128-128c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L402.7 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l370.7 0-73.4 73.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l128-128z"/>
                                </svg>
                                <div class="text-left rtl:text-right">
                                    <div class="mb-1 text-xs">Continuar</div>
                                    <div class="-mt-1 font-sans text-sm font-semibold">Realizar pago</div>
                                </div>
                            </Link>
                        </div>
                    </div>

                </div>
                <!-- finstep uno -->
            </div>

        </div>
    </div>
</template>



