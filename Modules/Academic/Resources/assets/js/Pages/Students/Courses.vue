<script  setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import { Link  } from '@inertiajs/vue3';
    import { ref, onMounted  } from 'vue';
    import { usePage } from '@inertiajs/vue3';
    import { useAppStore } from '@/stores/index';
    import axios from 'axios';
    import Swal2 from "sweetalert2";
    import shortVideos from "../../Components/shortVideos.vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';

    const page = usePage();
    const store = useAppStore();
    const publicKey = ref(null);


    const props = defineProps({
        courses: {
            type: Object,
            default: () => ({}),
        },
        certificates:{
            type: Object,
            default: () => ({}),
        },
        studentSubscribed: {
            type: Object,
            default: () => ({}),
        },
        mycourses: {
            type: Object,
            default: () => ({}),
        },
        courseTypes: {
            type: Object,
            default: () => ({}),
        },
        P000019: {
            type: Boolean,
            default: null
        },
        coursesRegistered: {
            type: Object,
            default: () => ({}),
        }
    });

    onMounted(() => {
        publicKey.value = page.props.MERCADOPAGO_KEY;
    });

    const baseUrl = assetUrl;

    const getImage = (path) => {
        return baseUrl + 'storage/'+ path;
    }

    const formatDate = (dateString) => {
        const date = new Date(dateString.replace(' ', 'T')); // Convierte a formato válido
        return new Intl.DateTimeFormat('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        }).format(date);
    };

    const addBuyCourse = (course) => {
        let price = course.price;
        let price_old = 0;
        let discount = 0;

        if(course.price || course.price > 0){
            if(course.discount || course.discount > 0){
                if(course.discount_applies == '01'){
                    price = (course.price - (course.price * course.discount / 100)).toFixed(2);
                    price_old = course.price;
                    discount = parseFloat(course.discount) % 1 === 0 ? parseInt(course.discount) : parseFloat(course.discount).toFixed(2);
                }else if(course.discount_applies == '02'){
                    if(props.studentSubscribed && props.studentSubscribed.status == 1){
                        price = (course.price - (course.price * course.discount / 100)).toFixed(2);
                        price_old = course.price;
                        discount = parseFloat(course.discount) % 1 === 0 ? parseInt(course.discount) : parseFloat(course.discount).toFixed(2);
                    }else{
                        price = course.price;
                    }
                }
            }


            let product = {
                id: course.id,
                name: course.description,
                price: price,
                quantity: 1,
                entity: 'AcaCourse',
                image: getImage(course.image),
                price_old: price_old,
                discount: discount
            };
            store.addToCart(product);
            showAlertToast('Curso agregado al carrito', 'success', 'top');
        }else{
            showAlertToast('No puedes agregar cursos que son gratis')
        }
    }

    const showAlertToast = async (text, iconType = null, xposition = 'top-end') => {
        const toast = Swal.mixin({
            toast: true,
            position: xposition,
            showConfirmButton: false,
            timer: 3000,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        toast.fire({
            icon: iconType,
            title: text,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }

    const coursesData = ref(null);
    const courseIndex = ref(null);

    const changeSelectCourses = (courses, index = 0) => {
        let result = [];

        if(index == 99){
            let mycourses = props.mycourses;
            result = [...mycourses].sort((a, b) => {
                // 1. Primero can_view = true
                if (a.can_view && !b.can_view) return -1;
                if (!a.can_view && b.can_view) return 1;

                // 2. Luego ordenar por descripción (alfabético)
                return a.description.localeCompare(b.description);
            });
        }else if(index == 100){
            result = props.coursesRegistered;
        }else{
            result = [...courses].sort((a, b) => {
                // 1. Primero can_view = true
                if (a.can_view && !b.can_view) return -1;
                if (!a.can_view && b.can_view) return 1;

                // 2. Luego ordenar por descripción (alfabético)
                return a.description.localeCompare(b.description);
            });
        }
        courseIndex.value = index;
        coursesData.value = result;
    }

    onMounted(() => {
        changeSelectCourses(props.coursesRegistered, 100);
    });

    const countContents = (course) => {
        if (!course.modules) return 0;

        // Recorremos módulos -> temas -> contenidos
        return course.modules.reduce((total, module) => {
            const themeContents = module.themes?.reduce((subTotal, theme) => {
                return subTotal + (theme.contents?.length || 0);
            }, 0) || 0;

            return total + themeContents;
        }, 0);
    };
    const calculatePercentage = (course) => {
        const total = countContents(course);
        if (total === 0) return 0;
        const percentage = (course.total_activity / total) * 100;
        return Math.round(percentage);
    };

    const showCertificateButton = (course) => {
        if (course.auto_certificate && course.certificate_exists) {
            return true;
        }
        if (!course.auto_certificate && course.student_grade !== null && course.student_grade >= 11) {
            return true;
        }
        return false;
    };

    const getCertificateStatusText = (course) => {
        if (course.auto_certificate) {
            if (course.certificate_exists) {
                return 'Certificado listo';
            }
            const viewed = course.contents_viewed || course.total_activity || 0;
            const total = course.contents_total || countContents(course);
            return `Progreso: ${viewed}/${total} contenidos`;
        } else {
            if (course.student_grade !== null) {
                const status = course.student_grade >= 11 ? 'Aprobado' : 'En curso';
                return `Nota: ${course.student_grade}/20 - ${status}`;
            }
            return 'Sin nota registrada';
        }
    };

    const getCertificateStatusClass = (course) => {
        if (course.auto_certificate) {
            if (course.certificate_exists) {
                return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
            }
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        } else {
            if (course.student_grade !== null) {
                return course.student_grade >= 11 
                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                    : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
            }
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        }
    };

    const downloadCourseCertificate = async (course) => {
        try {
            const response = await axios.get(route('aca_student_course_certificate_find', course.id));
            
            if (response.data.success && response.data.certificate_id) {
                window.open(route('aca_image_download', response.data.certificate_id), '_blank');
            } else {
                Swal2.fire({
                    title: 'Certificado no disponible',
                    text: 'No se encontró el certificado para este curso',
                    icon: 'warning',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal2.fire({
                title: 'Error',
                text: 'No se pudo descargar el certificado',
                icon: 'error',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }
    };
</script>

<template>
    <AppLayout title="Mis Cursos">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {title: 'Cursos Disponibles'}
            ]"
        />
        <div class="pt-5">
            <div class="grid gap-6"
                :class="P000019 == true ? 'grid-cols-6' : ''"
            >
                <section :class="P000019 == true ? 'col-span-6 sm:col-span-4' : 'w-full'">
                    <!-- Pestañas de filtro con colores sólidos -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-2 mb-6 inline-flex space-x-2">
                        <button
                            @click="changeSelectCourses(null, 99)"
                            class="px-2 py-2.5 rounded-lg font-medium text-sm transition-all duration-200"
                            :class="courseIndex === 99
                                ? 'bg-blue-500 text-white shadow-lg'
                                : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        >
                            Todos
                        </button>
                        <button
                            @click="changeSelectCourses(null, 100)"
                            class="px-2 py-2.5 rounded-lg font-medium text-sm transition-all duration-200"
                            :class="courseIndex === 100
                                ? 'bg-blue-500 text-white shadow-lg'
                                : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        >
                            Matriculados
                        </button>
                        <template v-for="(type, key) in courses">
                            <button
                                @click="changeSelectCourses(type.courses, key)"
                                class="px-2 py-2.5 rounded-lg font-medium text-sm transition-all duration-200"
                                :class="courseIndex === key
                                    ? 'bg-blue-500 text-white shadow-lg'
                                    : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                            >
                                {{ type.type_description }}
                            </button>
                        </template>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <template v-for="(course, index) in coursesData">
                            <article class="group rounded-xl bg-white p-4 shadow-lg hover:shadow-xl hover:bg-blue-50 transition-all duration-300 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700">
                                <template v-if="course.can_view">
                                    <Link :href="route('aca_mycourses_lessons',course.id)" class="block h-full">
                                        <!-- Imagen del curso -->
                                        <div class="relative flex items-end overflow-hidden rounded-xl mb-4">
                                            <img :src="getImage(course.image)" alt="Hotel Photo" class="w-full h-48 object-cover transition-all duration-300 group-hover:brightness-110" />
                                        </div>
                                        <!-- Contenido del curso -->
                                        <div class="space-y-3">
                                            <!-- Badge de modalidad -->
                                            <div class="inline-flex items-center px-2 py-1 bg-blue-500 text-white rounded-lg text-xs font-medium group-hover:bg-blue-600 transition-colors duration-300">
                                                {{ course.modality.description }}
                                            </div>

                                            <!-- Título del curso -->
                                            <h2 class="text-lg font-bold text-gray-900 dark:text-white leading-tight transition-colors group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                                {{ course.description }}
                                            </h2>

                                            <!-- Barra de progreso del curso -->
                                            <div class="space-y-2">
                                                <div class="flex justify-between items-center text-xs font-medium">
                                                    <span class="text-gray-600 dark:text-gray-400">Progreso del curso</span>
                                                    <span class="text-blue-600 dark:text-blue-400 font-bold">
                                                        {{ calculatePercentage(course) }}%
                                                    </span>
                                                </div>

                                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                                    <div
                                                        class="h-full bg-blue-500 rounded-full transition-all duration-700 ease-out group-hover:bg-blue-600"
                                                        :style="{ width: calculatePercentage(course) + '%' }"
                                                    ></div>
                                                </div>

                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    <span class="font-medium">{{ course.total_activity || 0 }}</span> de
                                                    <span class="font-medium">{{ countContents(course) }}</span> lecciones completadas
                                                </div>
                                            </div>
                                            
                                            <!-- Indicador de estado del certificado -->
                                            <div class="mt-3 p-3 rounded-lg border" :class="getCertificateStatusClass(course)">
                                                <div class="flex items-center gap-2">
                                                    <svg v-if="showCertificateButton(course)" class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <svg v-else class="w-4 h-4 flex-shrink-0 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <span class="text-xs font-medium">{{ getCertificateStatusText(course) }}</span>
                                                </div>
                                                
                                                <!-- Botón Descargar Certificado -->
                                                <button 
                                                    v-if="showCertificateButton(course)"
                                                    @click.prevent="downloadCourseCertificate(course)"
                                                    class="w-full mt-2 px-3 py-2 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white rounded-lg font-medium text-xs flex items-center justify-center gap-2 transition-all"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                    Descargar Certificado
                                                </button>
                                            </div>
                                        </div>
                                    </Link>
                                </template>
                                <template v-else>
                                    <!-- Curso no matriculado -->
                                    <div class="relative flex items-end overflow-hidden rounded-xl mb-4">
                                        <img :src="getImage(course.image)" alt="Hotel Photo" class="w-full h-48 object-cover transition-all duration-300 group-hover:brightness-110" />
                                    </div>

                                    <div class="space-y-3">
                                        <!-- Badges de información -->
                                        <div class="flex flex-wrap gap-2">
                                            <div class="inline-flex items-center px-2 py-1 bg-purple-500 text-white rounded-lg text-xs font-medium group-hover:bg-purple-600 transition-colors duration-300">
                                                {{ course.modality.description }}
                                            </div>
                                            <div class="inline-flex items-center px-2 py-1 bg-indigo-500 text-white rounded-lg text-xs font-medium group-hover:bg-indigo-600 transition-colors duration-300">
                                                {{ course.type_description }}
                                            </div>
                                        </div>

                                        <!-- Título del curso -->
                                        <h2 class="text-lg font-bold text-gray-900 dark:text-white leading-tight group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors duration-300">
                                            {{ course.description }}
                                        </h2>
                                    </div>                                    <!-- Sección de precios con colores sólidos -->
                                    <div class="space-y-4" v-if="course.price && course.price > 0">
                                        <!-- Contenedor de precios sólido -->
                                        <div class="bg-blue-500 rounded-xl p-4">
                                            <div class="relative">
                                                <template v-if="course.discount && course.discount > 0">
                                                    <template v-if="course.discount_applies == '01'">
                                                        <div class="flex items-baseline justify-between">
                                                            <div>
                                                                <p class="text-white text-2xl font-bold">
                                                                    S./ {{ (course.price - (course.price * course.discount / 100)).toFixed(2) }}
                                                                </p>
                                                                <p class="text-gray-200 text-sm line-through">S./ {{ course.price }}</p>
                                                            </div>
                                                            <div class="bg-yellow-500 text-gray-900 px-3 py-1 rounded-full text-sm font-bold">
                                                                -{{ parseFloat(course.discount) % 1 === 0 ? parseInt(course.discount) : parseFloat(course.discount).toFixed(2) }}%
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <template v-else-if="course.discount_applies == '02'">
                                                        <template v-if="studentSubscribed && studentSubscribed.status == 1">
                                                            <div class="flex items-baseline justify-between">
                                                                <div>
                                                                    <p class="text-white text-2xl font-bold">
                                                                        S./ {{ (course.price - (course.price * course.discount / 100)).toFixed(2) }}
                                                                    </p>
                                                                    <p class="text-gray-200 text-sm line-through">S./ {{ course.price }}</p>
                                                                </div>
                                                                <div class="bg-yellow-500 text-gray-900 px-3 py-1 rounded-full text-sm font-bold">
                                                                    -{{ parseFloat(course.discount) % 1 === 0 ? parseInt(course.discount) : parseFloat(course.discount).toFixed(2) }}%
                                                                </div>
                                                            </div>
                                                        </template>
                                                        <template v-else>
                                                            <p class="text-white text-2xl font-bold">S./ {{ course.price }}</p>
                                                        </template>
                                                    </template>
                                                </template>
                                                <template v-else>
                                                    <p class="text-white text-2xl font-bold">S./ {{ course.price }}</p>
                                                </template>
                                            </div>
                                        </div>

                                        <!-- Botón de compra con color sólido -->
                                        <button v-if="!course.can_view" v-on:click="addBuyCourse(course)"
                                                type="button"
                                                class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                                            </svg>
                                            Comprar curso
                                        </button>
                                    </div>

                                </template>
                            </article>
                        </template>
                    </div>
                </section>
                <section v-if="P000019" class="col-span-6 sm:col-span-2 rounded-md">
                    <shortVideos />
                </section>
            </div>
        </div>

    </AppLayout>
</template>
<style>
/* Estilos modernos eliminados - ahora usamos Tailwind classes */

</style>
