<script  setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import { Link  } from '@inertiajs/vue3';
    import { ref, onMounted  } from 'vue';
    import { usePage } from '@inertiajs/vue3';
    import { useAppStore } from '@/stores/index';
    import Swal from "sweetalert2";
    import shortVideos from "../../Components/shortVideos.vue";

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
</script>

<template>
    <AppLayout title="Mis Cursos">
        <ul class="flex space-x-2 rtl:space-x-reverse">
            <li>
                <a href="javascript:;" class="text-primary hover:underline">Académico</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Mis cursos</span>
            </li>
        </ul>
        <div class="pt-5">
            <div class="grid gap-6 grid-cols-6">
                <section class="col-span-6 sm:col-span-4 rounded-md dark:bg-gray-800">
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <template v-for="(course, index) in courses">
                            <article class="rounded-xl bg-white p-3 shadow-lg hover:shadow-xl hover:transform hover:scale-105 duration-300 dark:bg-gray-900">
                                <template v-if="course.can_view">
                                    <Link :href="route('aca_mycourses_lessons',course.id)">
                                        <div class="relative flex items-end overflow-hidden rounded-xl">
                                            <img :src="getImage(course.image)" alt="Hotel Photo" />
                                        </div>

                                        <div class="mt-1 p-2">
                                            <p class="mt-1 text-sm text-slate-400">{{ course.modality.description }}</p>
                                            <h2 class="text-slate-700 dark:text-slate-400">{{ course.description }}</h2>
                                        </div>
                                    </Link>
                                </template>
                                <template v-else>

                                    <div class="relative flex items-end overflow-hidden justify-center rounded-xl">
                                        <img :src="getImage(course.image)" alt="Hotel Photo" class="max-h-[105.05px]" />
                                    </div>

                                    <div class="mt-1 p-2">
                                        <p class="mt-1 text-sm text-slate-400">{{ course.modality.description }}</p>
                                        <p class="mt-1 text-sm text-slate-400">{{ course.type_description }}</p>
                                        <h2 class="text-slate-700 dark:text-slate-400">{{ course.description }}</h2>
                                    </div>
                                    <div class="space-y-4" v-if="course.price && course.price > 0">
                                        <div class="etiquet-price relative">
                                            <template v-if="course.discount && course.discount > 0">
                                                <template v-if="course.discount_applies == '01'">
                                                    <p class="price-new">
                                                        S./ {{ (course.price - (course.price * course.discount / 100)).toFixed(2) }} <!-- Precio con descuento -->
                                                    </p>
                                                    <p class="price-old line-through">S./ {{ course.price }}</p>
                                                    <span class="badge absolute ltr:right-2 rtl:left-0 -top-4 bg-danger p-0.5 px-1.5 rounded-full">
                                                        -{{ parseFloat(course.discount) % 1 === 0 ? parseInt(course.discount) : parseFloat(course.discount).toFixed(2) }}%
                                                    </span>
                                                </template>
                                                <template v-else-if="course.discount_applies == '02'">
                                                    <template v-if="studentSubscribed && studentSubscribed.status == 1">
                                                        <p class="price-new">
                                                            S./ {{ (course.price - (course.price * course.discount / 100)).toFixed(2) }} <!-- Precio con descuento -->
                                                        </p>
                                                        <p class="price-old line-through">S./ {{ course.price }}</p>
                                                        <span class="badge absolute ltr:right-2 rtl:left-0 -top-4 bg-danger p-0.5 px-1.5 rounded-full">
                                                            -{{ parseFloat(course.discount) % 1 === 0 ? parseInt(course.discount) : parseFloat(course.discount).toFixed(2) }}%
                                                        </span>
                                                    </template>
                                                    <template v-else>
                                                        <p class="price-new">S./ {{ course.price }}</p>
                                                    </template>
                                                </template>
                                            </template>
                                            <template v-else>
                                                <p class="price-new">S./ {{ course.price }}</p>
                                            </template>
                                        </div>
                                        <button v-if="!course.can_view" v-on:click="addBuyCourse(course)" type="button" class="btn btn-primary w-full">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 mr-2">
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
                <section class="col-span-6 sm:col-span-2 rounded-md">
                    <shortVideos />
                </section>
            </div>
        </div>

    </AppLayout>
</template>
<style>
.etiquet-price {
    background: #fdbd4a;
    width: 100%;
    padding: .2rem 1.2rem;
    border-radius: 0.3rem;
}

.etiquet-price .price-new {
    margin: 0;
    padding-top: .4rem;
    font-size: 1.5rem;
    font-weight: 400;
}
.etiquet-price .price-old {
    margin: 0;
    padding-top: .4rem;
    font-size: 0.9rem;
    font-weight: 400;
}
</style>
