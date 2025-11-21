<script setup>
    import { ref, onMounted, watch } from 'vue'
    import iconCheck from '@/Components/vristo/icon/icon-check.vue';
    import iconChevronRight from '@/Components/vristo/icon/icon-chevron-right.vue';
    import iconChevronLeft from '@/Components/vristo/icon/icon-chevron-left.vue';
    import { useForm } from '@inertiajs/vue3';
    import { Select, SelectOption } from 'ant-design-vue';
    import Multiselect from '@suadelabs/vue3-multiselect';
    import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';
    import TextInput from '@/Components/TextInput.vue';
    import InputError from '@/Components/InputError.vue';
    import iconSearch from '@/Components/vristo/icon/icon-search.vue';
    import Swal2 from 'sweetalert2';
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import previewInvoice from './previewInvoice.vue';
    import IconTrash from '@/Components/vristo/icon/icon-trash.vue';

    const disReset = ref(true);
    const isFinalStep = ref(false);
    const currentStep = ref(1)
    const totalSteps = 3

    const nextStep = () => {
        if (currentStep.value <= totalSteps) {
            if (validateStep()) {
                currentStep.value++
            }
        }
    }

    const prevStep = () => {
        if (currentStep.value > 1) {
            currentStep.value--
        }
    }

    const deleteStepper = () => {
        disReset.value = false;
        currentStep.value = 0
        formSale.reset();
    }

    const resetStepper = () => {
        currentStep.value = 1
    }

    watch(() => currentStep.value, ( step ) => {

        if(step > totalSteps){
            isFinalStep.value = true;
        }else{
            isFinalStep.value = false
        }
    });

    const props = defineProps({
        courses: {
            type: Object,
            default: () => ({})
        },
        types: {
            type: Object,
            default: () => ({})
        },
        identityDocumentTypes: {
            type: Object,
            default: () => ({})
        },
        countries: {
            type: Object,
            default: () => ({})
        },
        ubigeo: {
            type: Object,
            default: () => ({})
        },
        saleDocumentTypes: {
            type: Object,
            default: () => ({})
        },
        payments: {
            type: Object,
            default: () => ({})
        },
        igv: {
            type: Number,
            default: 18 // Valor por defecto si no se pasa desde el padre
        },
        subscriptionTypes: {
            type: Object,
            default: () => ({})
        }
    });

    const formSale = useForm({
        courses: [],
        names: null,
        alu_number: null,
        alu_document_type: 1,
        afather: null,
        amother: null,
        address: null,
        ubigeo: null,
        ubigeo_description: null,
        telephone: null,
        email: null,
        gender: 'M',
        country_id: 1,
        sale_document_type: 2,
        document_type: 1,
        sale_full_name: null,
        number: null,
        sale_address: null,
        sale_ubigeo_description: null,
        sale_ubigeo: null,
        estado: null,
        condicion: null,
        payments: [{
            type:1,
            reference: null,
            amount: 0
        }],
        total: 0,
        estado_pago: 'approved',
        aplasos: false,
        date_end: null,
        subscriptions: []
    });

    const filteredCountries = ref([]);

    onMounted(() => {
        filteredCountries.value = props.countries;
        console.log(filteredCountries.value)
    });

    const normalizeText = (text) => {
        return (text || "")
            .normalize("NFD")                // descompone letras + tildes (ej: "á" → "á")
            .replace(/\p{Diacritic}/gu, "")  // elimina los diacríticos
            .toLowerCase()
            .trim();
    };


    const handleSearch = (input) => {
        const term = normalizeText(input);

        if (!term) {
            filteredCountries.value = props.countries;
            return;
        }

        filteredCountries.value = props.countries.filter(c =>
            normalizeText(c.description).includes(term)
        );
    };

    const countrySelected = ref(1);
    const baseUrl = assetUrl;

    const getFlagImage = (path) => {
        return baseUrl + path;
    };
    const handleChange = (val) => {
        formSale.country_id = val;
    };

    const ubigeoSelected = ref(null);
    const saleUbigeoSelected = ref(null);

    const selectCity = () => {
        formSale.ubigeo_description = ubigeoSelected.value.ubigeo_description;
        formSale.ubigeo = ubigeoSelected.value.district_id;
    }

    const selectCitySale = () => {
        formSale.sale_ubigeo_description = saleUbigeoSelected.value.ubigeo_description;
        formSale.sale_ubigeo = saleUbigeoSelected.value.district_id;
    }

    const apiesLoading = ref(false);

    const searchApispe = () => {
        apiesLoading.value = true;
        axios.post(route('sales_search_person_apies'), formSale).then((res) => {

            if(res.data.success){
                if(formSale.document_type == 6){
                    formSale.sale_full_name =  res.data.person['razon_social'];
                    //formSale.email = null;
                    formSale.sale_address = res.data.person['direccion'] == '-' ? null : res.data.person['direccion'];
                    formSale.sale_ubigeo = res.data.person['ubigeo'];
                    saleUbigeoSelected.value = {
                        district_id: res.data.person['ubigeo'],
                        city_name: res.data.person['departamento'] + '-' + res.data.person['provincia'] + '-'+ res.data.person['distrito']
                    };
                    //console.log(saleUbigeoSelected.value)
                    formSale.sale_ubigeo_description = res.data.person['departamento'] + '-' + res.data.person['provincia'] + '-'+ res.data.person['distrito'];
                    formSale.estado = res.data.person['estado'];
                    formSale.condicion = res.data.person['condicion'];
                }else{
                    formSale.sale_full_name =  res.data.person['razon_social'];
                    formSale.estado = null;
                    formSale.condicion = null;
                }
            }else{
                Swal2.fire({
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

    const chageDocumenteType = () => {
        if(formSale.sale_document_type == 1){
            formSale.document_type = 6;
        }else{
            formSale.document_type = 1;
        }
    }

    const validateStep = () => {
        formSale.clearErrors() // limpia errores previos

        if (currentStep.value === 1) {
            const hasCourses = formSale.courses && formSale.courses.length > 0
            const hasSubscriptions = formSale.subscriptions && formSale.subscriptions.length > 0

            if (!hasCourses && !hasSubscriptions) {
                formSale.setError('courses', 'Debe seleccionar al menos un curso o una suscripción.')
                formSale.setError('subscriptions', 'Debe seleccionar al menos un curso o una suscripción.')
                return false
            }
        }

        if (currentStep.value === 2) {
            const requiredFields = [
                'names',
                'alu_number',
                'alu_document_type',
                'afather',
                'amother',
                'email',
            ]
            let valid = true
            requiredFields.forEach(field => {
                if (!formSale[field]) {
                    formSale.setError(field, 'Este campo es obligatorio.')
                    valid = false
                }
            });
            if (!valid) return false
        }

        if (currentStep.value === 3) {
            const requiredAlways = ['sale_full_name', 'number']
            let valid = true

            requiredAlways.forEach(field => {
                if (!formSale[field]) {
                    formSale.setError(field, 'Este campo es obligatorio.')
                    valid = false
                }
            });

            // Si es factura (document_type = 1)
            if (formSale.document_type === 1) {
                const facturaFields = [
                    'sale_address',
                    'sale_ubigeo_description',
                    'sale_ubigeo',
                    'estado',
                    'condicion',
                ]
                facturaFields.forEach(field => {
                    if (!formSale[field]) {
                        formSale.setError(field, 'Este campo es obligatorio para factura.')
                        valid = false
                    }
                });
            }

            if (!valid) return false
        }

        return true // si todo ok
    }

    const toggleCourse = (course) => {
        const index = formSale.courses.findIndex((c) => c.id === course.id)
        if (index === -1) {
            // Si no está, agregarlo
            formSale.courses.push({
                id: course.id,
                description: course.description,
                price: course.price,
            })
        } else {
            // Si ya estaba, quitarlo
            formSale.courses.splice(index, 1)
        }
    }

    const toggleCourse2 = (subscription) => {
        const index = formSale.subscriptions.findIndex((c) => c.id === subscription.id)
        if (index === -1) {
            // Si no está, agregarlo
            formSale.subscriptions.push({
                id: subscription.id,
                description: `${subscription.title}/${subscription.description}`,
                price: subscription.prices[0].amount,
            })
        } else {
            // Si ya estaba, quitarlo
            formSale.subscriptions.splice(index, 1)
        }
    }


    const isSelected = (id) => {
        return formSale.courses.some((c) => c.id === id)
    }

    const isSelected2 = (id) => {
        return formSale.subscriptions.some((c) => c.id === id)
    }

    const getSelectedCourse = (id) => {
        return formSale.courses.find((c) => c.id === id) || {}
    }

    const getSelectedCourse2 = (id) => {
        return formSale.subscriptions.find((c) => c.id === id) || {}
    }

    const saveFinish = () => {
        formSale.date_end = null;
        formSale.aplasos = false;
        formSale.post(route('onlineshop_sales_store'), {
            preserveScroll: true,
            onSuccess: () => {
                formSale.reset();
            },
        });
    }

    const addPayment = () => {
        let ar = {
            type:1,
            reference: null,
            amount:0
        };
        formSale.payments.push(ar);
    };

    const removePayment = (index) => {
        if(index>0){
            formSale.payments.splice(index,1);
        }
    };

    const calcularTotal = (courses = [], subscriptions = []) => {
        let totalCursos = 0;
        let totalSubscription = 0;

        courses.forEach(item => {
            const rawPrice = item.price ?? item.precio ?? item.amount ?? 0
            const qty = item.qty ?? item.quantity ?? item.cantidad ?? 1

            const priceNum = Number(rawPrice) || 0
            const qtyNum = Number(qty) || 1

            totalCursos += priceNum * qtyNum
        });

        subscriptions.forEach(item => {
            const rawPrice = item.price ?? item.precio ?? item.amount ?? 0
            const qty = item.qty ?? item.quantity ?? item.cantidad ?? 1

            const priceNum = Number(rawPrice) || 0
            const qtyNum = Number(qty) || 1

            totalSubscription += priceNum * qtyNum
        });

        let total = totalCursos + totalSubscription;

        formSale.payments[0].amount = total;
        formSale.total = total;
    }


    // Observa el array de courses y recalcula cuando cambie
    watch(
        [() => formSale.courses, () => formSale.subscriptions],
        ([courses, subscriptions]) => {
            if (!Array.isArray(courses)) courses = []
            if (!Array.isArray(subscriptions)) subscriptions = []

            calcularTotal(courses, subscriptions)
        },
        { deep: true, immediate: true }
    );

    const alertCuotas = () => {
        Swal2.fire({
            icon: 'warning',
            title: 'Confirmar registro con pago pendiente',
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            html: `<div class="flex flex-col justify-start space-y-2">
                    <span class="text-justify leading-relaxed">
                        Con esta opción registrarás la venta y se activarán los accesos del alumno.
                        Se fijará una fecha límite de pago; si no se realiza ningún abono antes de esa fecha,
                        la cuenta será bloqueada.
                    </span>
                    <input
                        id="dateEnd"
                        name="dateEnd"
                        type="date"
                        class="form-input border rounded-md px-3 py-2 focus:ring focus:ring-blue-200"
                    >
                </div>
            `,
            padding: '2em',
            customClass: 'sweet-alerts',
            footer: 'Se define una fecha de pago inicial. El usuario podrá liquidar el total o reagendar una nueva fecha para cancelar el saldo pendiente.',
            focusConfirm: false,
            allowOutsideClick: false, // ❌ No se cierra al hacer clic fuera
            allowEscapeKey: false,    // ❌ No se cierra con la tecla Esc
            allowEnterKey: true,      // ✅ Permite presionar Enter para confirmar
            preConfirm: () => {
                const date = document.getElementById("dateEnd").value;
                if (!date) {
                    Swal2.showValidationMessage("Por favor selecciona una fecha de pago");
                    return false;
                }
                return date; // retorna directamente el valor
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const selectedDate = result.value;
                formSale.date_end = selectedDate;
                formSale.aplasos = true;
                formSale.post(route('onlineshop_sales_store'), {
                    preserveScroll: true,
                    onSuccess: () => {
                        formSale.reset();
                    },
                });
            } else if (result.isDismissed) {
                formSale.date_end = null;
                formSale.aplasos = false;
            }
        });
    }

    const selectType = ref('Cursos Taller');
    const displayCourse = ref(true);

    const changeSelectType = () => {

        if(selectType.value == 'subscription'){
            displayCourse.value = false;
        } else {
            displayCourse.value = true;
        }
    }

</script>


<template>

    <!-- Stepper -->
    <div>
        <!-- Stepper Nav -->
        <ul class="relative flex flex-row gap-x-2">
            <li class="flex items-center gap-x-2 shrink basis-0 flex-1 group">
                <span class="min-w-7 min-h-7 group inline-flex items-center text-xs align-middle">
                    <span class="size-7 flex justify-center items-center shrink-0  font-medium bg-blue-600 text-white dark:bg-blue-500 rounded-full dark:text-white"
                        :class="[
                            {'bg-red-100 text-red-800 group-focus:bg-red-200 dark:group-focus:bg-red-600' : currentStep === 0},
                            {'bg-teal-500 group-focus:bg-teal-600 dark:bg-teal-500 dark:group-focus:bg-teal-600' : currentStep === 4}
                        ]"
                    >
                        <span v-if="currentStep == 1">1</span>
                        <iconCheck v-else-if="currentStep > 1" class="shrink-0 size-3" />
                    </span>
                    <span class="ms-2 text-sm font-medium text-gray-800 dark:text-neutral-200">
                        Items de compra
                    </span>
                </span>
                <div class="w-full h-px flex-1 bg-gray-200 dark:bg-neutral-700"
                    :class="[
                        {'bg-blue-600 text-white dark:bg-blue-600': currentStep > 1},
                        {'bg-teal-500 group-focus:bg-teal-600 dark:bg-teal-500 dark:group-focus:bg-teal-600' : currentStep === 4}
                    ]"
                ></div>
            </li>

            <li class="flex items-center gap-x-2 shrink basis-0 flex-1 group">
                <span class="min-w-7 min-h-7 group inline-flex items-center text-xs align-middle">
                    <span class="size-7 flex justify-center items-center shrink-0 bg-gray-100 text-gray-800 group-focus:bg-gray-200 dark:group-focus:bg-gray-600 font-medium rounded-full dark:text-white"
                        :class="[
                            {'bg-red-100 text-red-800 group-focus:bg-red-200 dark:group-focus:bg-red-600' : currentStep === 0},
                            {'bg-blue-600 text-white dark:bg-blue-500': currentStep >= 2},
                            {'bg-teal-500 group-focus:bg-teal-600 dark:bg-teal-500 dark:group-focus:bg-teal-600' : currentStep === 4}
                        ]"
                    >
                        <span v-if="currentStep <= 2">2</span>
                        <iconCheck v-else-if="currentStep > 2" class="shrink-0 size-3" />
                    </span>
                    <span class="ms-2 text-sm font-medium text-gray-800 dark:text-neutral-200">
                        Información personal
                    </span>
                </span>
                <div class="w-full h-px flex-1 bg-gray-200 dark:bg-neutral-700"
                    :class="[
                        {'bg-blue-600 text-white dark:bg-blue-600': currentStep > 2},
                        {'bg-teal-500 group-focus:bg-teal-600 dark:bg-teal-500 dark:group-focus:bg-teal-600' : currentStep === 4}
                    ]"
                ></div>
            </li>

            <li class="flex items-center gap-x-2 shrink basis-0 flex-1 group">
                <span class="min-w-7 min-h-7 group inline-flex items-center text-xs align-middle">
                    <span class="size-7 flex justify-center items-center shrink-0 bg-gray-100 text-gray-800 group-focus:bg-gray-200 dark:group-focus:bg-gray-600 font-medium rounded-full dark:text-white"
                        :class="[
                            {'bg-red-100 text-red-800 group-focus:bg-red-200 dark:group-focus:bg-red-600' : currentStep === 0},
                            {'bg-blue-600 text-white dark:bg-blue-500': currentStep >= 3},
                            {'bg-teal-500 group-focus:bg-teal-600 dark:bg-teal-500 dark:group-focus:bg-teal-600' : currentStep === 4}
                        ]"
                    >
                        <span v-if="currentStep <= 3">3</span>
                        <iconCheck v-else-if="currentStep > 3" class="shrink-0 size-3" />
                    </span>
                    <span class="ms-2 text-sm font-medium text-gray-800 dark:text-neutral-200">
                        Documento de venta
                    </span>
                </span>
                <div class="w-full h-px flex-1 bg-gray-200 group-last:hidden hs-stepper-success:bg-blue-600 hs-stepper-completed:bg-teal-600 dark:bg-neutral-700 dark:hs-stepper-success:bg-blue-600 dark:hs-stepper-completed:bg-teal-600"></div>
            </li>
        <!-- End Item -->
    </ul>
    <!-- End Stepper Nav -->

    <!-- Stepper Content -->
    <div class="mt-5 sm:mt-8">
        <!-- First Content -->
        <div v-show="currentStep === 0">
            <div class="p-4 h-48 bg-gray-50 flex justify-center items-center border border-dashed border-gray-200 rounded-xl">
                <h3 class="text-gray-500">basio content</h3>
            </div>
        </div>

        <div v-show="currentStep === 1">
            <div class="max-w-lg mx-auto mb-4">
                <div class="flex">
                    <label for="search-dropdown" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Your Email</label>
                    <select @change="changeSelectType" v-model="selectType" class="p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 ltr:rounded-l-lg rtl:rounded-r-none">
                        <template v-for="type in types">
                           <option :value="type">{{ type }}</option>
                        </template>
                        <option :value="'subscription'">Suscripción</option>
                    </select>
                    <div class="relative w-full flex-1">
                        <input type="search" id="search-dropdown" class="block p-2.5 w-full z-20 text-sm text-gray-900 bg-gray-50 rounded-e-lg border-s-gray-50 border-s-2 border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-s-gray-700  dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:border-blue-500" />
                        <button type="submit" class="absolute top-0 end-0 p-2.5 text-sm font-medium h-full text-white bg-blue-700 rounded-e-lg border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                            </svg>
                            <span class="sr-only">Buscar</span>
                        </button>
                    </div>
                </div>
            </div>
            <perfect-scrollbar
                :options="{
                    swipeEasing: true,
                    wheelPropagation: false
                }"
                class="min-h-[380px] max-h-[calc(100vh-80px)] relative overflow-auto"
            >
                <div class="p-4 bg-gray-50 flex justify-center items-center border border-dashed border-gray-200 rounded-xl">

                    <div class="grid sm:grid-cols-2 md:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-6">
                        <template v-if="displayCourse">
                            <div
                                v-for="course in courses"
                                :key="course.id"
                                :class="[
                                    'p-3 w-full border border-gray-200 rounded-lg text-sm transition-colors duration-200 dark:border-neutral-700 dark:text-neutral-400',
                                    isSelected(course.id)
                                    ? 'bg-blue-100 dark:bg-blue-900/40'
                                    : 'bg-white dark:bg-neutral-900'
                                ]"
                            >
                                <!-- Checkbox y descripción -->
                                <label :for="`hs-checkbox-${course.id}`" class="flex items-center mb-2 cursor-pointer gap-2">
                                    <span class="text-sm text-gray-700 dark:text-neutral-300">
                                    {{ course.description }}
                                    </span>
                                    <input
                                        type="checkbox"
                                        :id="`hs-checkbox-${course.id}`"
                                        :checked="isSelected(course.id)"
                                        @change="toggleCourse(course)"
                                        class="shrink-0 ms-auto border-gray-300 rounded-sm text-blue-600 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500"
                                    />
                                </label>

                                <!-- Precio (solo visible si está seleccionado) -->
                                <div v-if="isSelected(course.id)" class="flex sm:flex-row flex-col items-center">
                                    <label class="mb-0 sm:w-1/4 sm:ltr:mr-2 rtl:ml-2">Precio</label>
                                    <div class="flex-1 relative">
                                        <input
                                            type="number"
                                            v-model.number="getSelectedCourse(course.id).price"
                                            class="peer py-2.5 sm:py-3 pe-0 ps-8 block w-full bg-transparent border-t-transparent border-b-2 border-x-transparent border-b-gray-200 sm:text-sm focus:border-b-blue-500 focus:ring-0 dark:border-b-neutral-700 dark:text-neutral-400"
                                            placeholder="0.00"
                                        />
                                        <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-2">
                                            S/.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <div
                                v-for="subscriptionType in subscriptionTypes"
                                :key="subscriptionType.id"
                                :class="[
                                    'p-3 w-full border border-gray-200 rounded-lg text-sm transition-colors duration-200 dark:border-neutral-700 dark:text-neutral-400',
                                    isSelected2(subscriptionType.id)
                                    ? 'bg-blue-100 dark:bg-blue-900/40'
                                    : 'bg-white dark:bg-neutral-900'
                                ]"
                            >
                                <!-- Checkbox y descripción -->
                                <label :for="`hs-checkbox-2-${subscriptionType.id}`" class="flex items-center mb-2 cursor-pointer gap-2">
                                   <div>
                                        <span class="text-sm text-gray-700 dark:text-neutral-300">
                                            {{ subscriptionType.title }}
                                        </span>
                                        <p class="text-xs text-gray-700 dark:text-neutral-300">
                                            {{ subscriptionType.description }}
                                        </p>
                                   </div>
                                    <input
                                        type="checkbox"
                                        :id="`hs-checkbox-2-${subscriptionType.id}`"
                                        :checked="isSelected2(subscriptionType.id)"
                                        @change="toggleCourse2(subscriptionType)"
                                        class="shrink-0 ms-auto border-gray-300 rounded-sm text-blue-600 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500"
                                    />
                                </label>
                                <div>
                                    <ul class="mt-7 space-y-2.5 text-sm">
                                        <li v-for="detail in subscriptionType.details" class="flex gap-x-2">
                                            <svg class="shrink-0 mt-0.5 size-4 text-blue-600 dark:text-blue-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                            <span class="text-gray-800 dark:text-neutral-400">
                                                {{ detail.label }}
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                                <!-- Precio (solo visible si está seleccionado) -->
                                <div v-if="isSelected2(subscriptionType.id)" class="flex sm:flex-row flex-col items-center p-6">

                                    <label class="mb-0 sm:w-1/4 sm:ltr:mr-2 rtl:ml-2">Precio</label>

                                    <div class="flex-1 relative">
                                        <input
                                            type="number"
                                            v-model.number="getSelectedCourse2(subscriptionType.id).price"
                                            class="peer py-2.5 sm:py-3 pe-0 ps-8 block w-full bg-transparent border-t-transparent border-b-2 border-x-transparent border-b-gray-200 sm:text-sm focus:border-b-blue-500 focus:ring-0 dark:border-b-neutral-700 dark:text-neutral-400"
                                            placeholder="0.00"
                                        />
                                        <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-2">
                                            S/.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </perfect-scrollbar>
            <div class="mt-4">
                <InputError :message="formSale.errors.courses" />
            </div>
        </div>

        <div v-show="currentStep === 2">
            <div class="max-w-lg mx-auto mb-4 justify-center">
                <h4>Los campos con * son requeridos</h4>
            </div>
            <div class="p-4 bg-gray-50 flex justify-center items-center border border-dashed border-gray-200 rounded-xl">
                <div class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <div>
                            <label for="names">tipo de identificación *</label>
                            <select v-model="formSale.alu_document_type" id="identificacion" class="form-select">
                                <template v-for="value in identityDocumentTypes">
                                    <option :value="value.id">{{ value.description }}</option>
                                </template>
                            </select>
                            <InputError :message="formSale.errors.alu_document_type" class="mt-2" />
                        </div>
                        <div>
                            <label for="numero">Número *</label>
                            <input v-model="formSale.alu_number" id="numero" type="text" class="form-input" />
                            <InputError :message="formSale.errors.alu_number" class="mt-2" />
                        </div>
                        <div>
                            <label for="names">Nombres *</label>
                            <input v-model="formSale.names" id="names" type="text" class="form-input" />
                            <InputError :message="formSale.errors.names" class="mt-2" />
                        </div>
                        <div>
                            <label for="paterno">Apellido Paterno *</label>
                            <input v-model="formSale.afather" id="paterno" type="text" class="form-input" />
                            <InputError :message="formSale.errors.afather" class="mt-2" />
                        </div>
                        <div>
                            <label for="materno">Apellido Materno *</label>
                            <input v-model="formSale.amother"  id="materno" type="text" class="form-input" />
                            <InputError :message="formSale.errors.amother" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <label for="address">Dirección</label>
                            <input v-model="formSale.address" id="address" type="text" class="form-input" />
                            <InputError :message="formSale.errors.address" class="mt-2" />
                        </div>
                        <div>
                            <label for="pais">país</label>
                            <Select
                                style="width: 100%"
                                placeholder="Seleccione un país"
                                show-search
                                :filter-option="false"
                                @search="handleSearch"
                                @change="handleChange"
                                v-model:value="countrySelected"
                                :allowClear="true"
                            >
                                <template v-for="country in filteredCountries">
                                    <SelectOption :value="country.id" :label="country.description">
                                        <div class="flex items-center gap-4">
                                            <img :src="getFlagImage(country.image)" class="w-4 h-4" />
                                            <span>{{ country.description }}</span>
                                        </div>
                                    </SelectOption>
                            </template>
                            </Select>
                            <InputError :message="formSale.errors.country_id" class="mt-2" />
                        </div>
                        <div class="md:col-span-2">
                            <label for="ubigeo_id">Ciudad</label>
                            <template v-if="formSale.country_id == 1">
                                <multiselect
                                    id="ubigeo_id"
                                    :model-value="ubigeoSelected"
                                    v-model="ubigeoSelected"
                                    :options="ubigeo"
                                    class="custom-multiselect"
                                    :searchable="true"
                                    placeholder="Buscar"
                                    selected-label="seleccionado"
                                    select-label="Elegir"
                                    deselect-label="Quitar"
                                    label="ubigeo_description"
                                    track-by="district_id"
                                    @update:model-value="selectCity"
                                ></multiselect>
                                <InputError :message="formSale.errors.ubigeo" class="mt-2" />
                            </template>
                            <template v-else>
                                <TextInput
                                    id="ubigeo_id"
                                    v-model="formSale.ubigeo_description"
                                    type="text"
                                />
                                <InputError :message="formSale.errors.ubigeo_description" class="mt-2" />
                            </template>
                        </div>
                        <div>
                            <label for="email">Correo electrónico *</label>
                            <input v-model="formSale.email" id="email" type="email" class="form-input" />
                            <InputError :message="formSale.errors.email" class="mt-2" />
                        </div>
                        <div>
                            <label for="telefono">Teléfono</label>
                            <input v-model="formSale.telephone" id="telefono" type="number" class="form-input" />
                            <InputError :message="formSale.errors.telephone" class="mt-2" />
                        </div>
                    </div>
                    <div>
                        <label for="telefono">Sexo</label>
                        <div class="flex flex-col ">
                            <label class="inline-flex">
                                <input v-model="formSale.gender" :value="'M'" type="radio" name="gender_radio" class="form-radio" />
                                <span>Masculino</span>
                            </label>
                            <label class="inline-flex">
                                <input v-model="formSale.gender" :value="'F'" type="radio" name="gender_radio" class="form-radio text-success" />
                                <span>Femenino</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-show="currentStep === 3">
            <div class="p-4 bg-gray-50 justify-center items-center border border-dashed border-gray-200 rounded-xl">
                <div class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <div>
                            <label for="names">tipo documento *</label>
                            <select
                                @change="chageDocumenteType"
                                v-model="formSale.sale_document_type" id="identificacion" class="form-select">
                                <template v-for="value in saleDocumentTypes">
                                    <option :value="value.id">{{ value.description }}</option>
                                </template>
                            </select>
                            <InputError :message="formSale.errors.sale_document_type" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <label for="sale_full_name">Nombres / Razón social*</label>
                            <input v-model="formSale.sale_full_name" id="sale_full_name" type="text" class="form-input" />
                            <InputError :message="formSale.errors.sale_full_name" class="mt-2" />
                        </div>
                        <div>
                            <label for="sale_number">DNI / RUC *</label>
                            <div class="flex">
                                <input v-model="formSale.number" id="sale_number" type="text" class="form-input ltr:rounded-r-none rtl:rounded-l-none" />
                                <button
                                    @click="searchApispe"
                                    type="button"
                                    class="bg-[#eee] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md px-3 font-semibold border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]"
                                >
                                    <icon-loader v-if="apiesLoading" class="w-4 h-4 animate-spin" />
                                    <iconSearch v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <InputError :message="formSale.errors.number" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <label for="sale_address">Dirección fiscal *</label>
                            <input v-model="formSale.sale_address" id="sale_address" type="text" class="form-input" />
                            <InputError :message="formSale.errors.sale_address" class="mt-2" />
                        </div>
                        <div class="md:col-span-2">
                            <label for="sale_ubigeo_id">Ciudad</label>
                            <multiselect
                                id="sale_ubigeo_id"
                                :model-value="saleUbigeoSelected"
                                v-model="saleUbigeoSelected"
                                :options="ubigeo"
                                class="custom-multiselect"
                                :searchable="true"
                                placeholder="Buscar"
                                selected-label="seleccionado"
                                select-label="Elegir"
                                deselect-label="Quitar"
                                label="ubigeo_description"
                                track-by="district_id"
                                @update:model-value="selectCitySale"
                            ></multiselect>
                            <InputError :message="formSale.errors.sale_ubigeo" class="mt-2" />
                        </div>
                        <div v-if="formSale.condicion && formSale.estado" class="col-span-4">
                            <div class="flex items-center gap-6">
                                <div>
                                    <InputLabel for="condicion" value="Condicion" />
                                    <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-500">{{ formSale.condicion }}</span>
                                </div>
                                <div>
                                    <InputLabel for="estado" value="Estado" />
                                    <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-500">{{ formSale.estado }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6">
                    <div class="grid sm:grid-cols-2 items-center gap-6">
                        <p style="font-size: 14px;" class="italic font-bold uppercase mb-4">Medio de Pago <button @click="addPayment()" type="button" class="inline-block px-0 py-2 bg-transparent text-blue-600 font-medium text-xs leading-tight uppercase rounded transition duration-150 ease-in-out">Agregar (+)</button></p>
                    </div>
                    <table class="table-bordered w-full ltr:text-left rtl:text-right text-gray-600">
                        <tbody>
                            <tr v-for="(row, index) in formSale.payments" v-bind:key="index">
                                <td style="width: 70px;" class="text-right">
                                    <button @click="removePayment(index)" type="button" class="btn btn-sm btn-outline-danger">
                                        <IconTrash class="w-4 h-4" />
                                    </button>
                                </td>
                                <td >
                                    <select v-model="row.type" class="form-select text-white-dark py-1.5 text-xs">
                                        <template v-for="(payment) in payments">
                                            <option :value="payment.id">{{ payment.description }}</option>
                                        </template>
                                    </select>
                                    <InputError :message="formSale.errors[`payments.${index}.id`]" class="mt-2" />
                                </td>
                                <td>
                                    <input v-model="row.reference" type="text" id="first_name" class="form-input form-input-sm text-right" placeholder="Referencia">
                                    <InputError :message="formSale.errors[`payments.${index}.reference`]" class="mt-2" />
                                </td>
                                <td style="width: 110px;">
                                    <input v-model="row.amount" type="text" id="first_name" class="form-input form-input-sm text-right" autofocus placeholder="Monto" required>
                                    <InputError :message="formSale.errors[`payments.${index}.amount`]" class="mt-2" />
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- End First Content -->

        <!-- Final Content -->
        <div v-show="isFinalStep">
            <div class="p-4 bg-gray-50 flex justify-center items-center border border-dashed border-gray-200 rounded-xl dark:bg-gray-800 dark:text-white-dark dark:border-gray-600">
                <previewInvoice :invoice="formSale" :courses="courses" :igv="igv" />
            </div>
        </div>
        <!-- End Final Content -->

        <!-- Button Group -->
        <div class="mt-5 flex justify-between items-center gap-x-2">
            <button
                type="button"
                @click="prevStep"
                :disabled="currentStep === 1 || currentStep === 0"
                class="btn btn-info uppercase text-xs"
            >
                <iconChevronLeft class="w-4 h-4" />
                Atras
            </button>

            <button
                :disabled="currentStep === 0"
                v-if="currentStep <= totalSteps"
                type="button"
                @click="nextStep"
                class="btn btn-success uppercase text-xs"
            >
                Continuar
                <iconChevronRight class="w-4 h-4" />
            </button>

            <div v-else class="sm:flex gap-6">
                <button
                    type="button"
                    @click="alertCuotas"
                    class="btn btn-warning uppercase text-xs"
                >
                    Generar cuotas de pago
                </button>
                <button
                    type="button"
                    @click="saveFinish"
                    class="btn btn-primary uppercase text-xs"
                >
                    Guardar y finalizar
                </button>
            </div>
        </div>
        <!-- End Button Group -->
    </div>
    <!-- End Stepper Content -->
    </div>
    <!-- End Stepper -->

    <div class="flex flex-wrap gap-2 mt-4">
        <button @click="deleteStepper" type="button" class="btn btn-outline-danger uppercase text-xs">
            <svg class="shrink-0 size-3.5 mr-1" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                <path d="M598.6 118.6C611.1 106.1 611.1 85.8 598.6 73.3C586.1 60.8 565.8 60.8 553.3 73.3L361.3 265.3L326.6 230.6C322.4 226.4 316.6 224 310.6 224C298.1 224 288 234.1 288 246.6L288 275.7L396.3 384L425.4 384C437.9 384 448 373.9 448 361.4C448 355.4 445.6 349.6 441.4 345.4L406.7 310.7L598.7 118.7zM373.1 417.4L254.6 298.9C211.9 295.2 169.4 310.6 138.8 341.2L130.8 349.2C108.5 371.5 96 401.7 96 433.2C96 440 103.1 444.4 109.2 441.4L160.3 415.9C165.3 413.4 169.8 420 165.7 423.8L39.3 537.4C34.7 541.6 32 547.6 32 553.9C32 566.1 41.9 576 54.1 576L227.4 576C266.2 576 303.3 560.6 330.8 533.2C361.4 502.6 376.7 460.1 373.1 417.4z"/>
            </svg>
            Limpiar todo
        </button>
        <button @click="resetStepper" type="button" class="btn btn-outline-dark uppercase text-xs" :disabled="disReset">
            <svg class="shrink-0 size-3.5 mr-1" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                <path d="M352 64C352 46.3 337.7 32 320 32C302.3 32 288 46.3 288 64L288 320C288 337.7 302.3 352 320 352C337.7 352 352 337.7 352 320L352 64zM210.3 162.4C224.8 152.3 228.3 132.3 218.2 117.8C208.1 103.3 188.1 99.8 173.6 109.9C107.4 156.1 64 233 64 320C64 461.4 178.6 576 320 576C461.4 576 576 461.4 576 320C576 233 532.6 156.1 466.3 109.9C451.8 99.8 431.9 103.3 421.7 117.8C411.5 132.3 415.1 152.2 429.6 162.4C479.4 197.2 511.9 254.8 511.9 320C511.9 426 425.9 512 319.9 512C213.9 512 128 426 128 320C128 254.8 160.5 197.1 210.3 162.4z"/>
            </svg>
            Reinicializar
        </button>
    </div>
</template>
