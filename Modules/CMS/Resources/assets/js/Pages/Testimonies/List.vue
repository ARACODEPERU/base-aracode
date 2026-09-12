<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import Keypad from '@/Components/Keypad.vue';
    import Pagination from '@/Components/Pagination.vue';

    import Swal2 from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
    import { computed, ref } from 'vue';
    import { faPencilAlt, faCheck, faTrashAlt, faXmark, faEye, faPenToSquare } from "@fortawesome/free-solid-svg-icons";
    import IaCorrectionMenu from '../../Components/IaCorrectionMenu.vue';
    import ModifyModal from './Partials/ModifyModal.vue';

    const props = defineProps({
        types: {
            type: Object,
            default: () => ({}),
        },
        testimonies: {
            type: Object,
            default: () => ({}),
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
        source: {
            type: String,
            default: 'cms',
        },
        approvalStatus: {
            type: String,
            default: null,
        },
        counters: {
            type: Object,
            default: () => ({}),
        },
    });

    const isStudents = computed(() => props.source === 'student');

    // Modificacion desde el panel y correccion con IA.
    const modifyOpen = ref(false);
    const modifyTestimony = ref(null);
    const modifyInitialComment = ref(null);
    const modifyIaMode = ref(null);
    const iaLoadingId = ref(null);

    const openModify = (testimony, initialComment = null, iaMode = null) => {
        modifyTestimony.value = testimony;
        modifyInitialComment.value = initialComment;
        modifyIaMode.value = iaMode;
        modifyOpen.value = true;
    };

    const closeModify = () => {
        modifyOpen.value = false;
        modifyTestimony.value = null;
        modifyInitialComment.value = null;
        modifyIaMode.value = null;
    };

    // Al guardar los cambios, cerramos el modal y refrescamos la lista.
    const onModifySaved = () => {
        closeModify();
        router.reload({ only: ['testimonies', 'counters'] });
    };

    /**
     * El boton IA corrige el comentario del testimonio y abre el modal de
     * modificacion con el texto corregido para que el admin lo revise.
     */
    const correctWithIa = (testimony, mode) => {
        iaLoadingId.value = testimony.id;

        axios
            .post(route('cms_testimonies_ia_correct'), { id: testimony.id, mode: mode })
            .then((response) => {
                if (!response.data.success) {
                    Swal2.fire({
                        icon: 'error',
                        title: 'No se pudo corregir',
                        text: response.data.message || 'Inténtalo nuevamente.',
                        confirmButtonText: 'Entendido',
                    });
                    return;
                }

                openModify(testimony, response.data.corrected, response.data.mode);

                Swal2.fire({
                    icon: 'success',
                    title: 'Texto corregido',
                    text: 'Revisa la corrección y guarda para aplicarla.',
                    timer: 2200,
                    showConfirmButton: false,
                });
            })
            .catch(() => {
                Swal2.fire({
                    icon: 'error',
                    title: 'No se pudo corregir',
                    text: 'Hubo un problema al conectar con la IA.',
                    confirmButtonText: 'Entendido',
                });
            })
            .finally(() => {
                iaLoadingId.value = null;
            });
    };

    const form = useForm({
        search: props.filters.search,
    });

    const goTo = (params = {}) => {
        router.get(
            route('cms_testimonies_list'),
            {
                source: isStudents.value ? 'student' : 'cms',
                ...params,
            },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const statusBadgeClass = (status) => {
        if (status === 'approved') {
            return 'bg-emerald-100 text-emerald-800 border-emerald-400 dark:bg-gray-700 dark:text-emerald-400';
        }
        if (status === 'rejected') {
            return 'bg-red-100 text-red-800 border-red-400 dark:bg-gray-700 dark:text-red-400';
        }
        return 'bg-amber-100 text-amber-800 border-amber-400 dark:bg-gray-700 dark:text-amber-400';
    };

    const statusText = (status) => {
        if (status === 'approved') return 'Aprobado';
        if (status === 'rejected') return 'Rechazado';
        return 'Pendiente';
    };

    const formatDate = (value) => {
        if (!value) return '—';
        const date = new Date(String(value).replace(' ', 'T'));
        if (isNaN(date.getTime())) return '—';
        return new Intl.DateTimeFormat('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(date);
    };

    const destroyTestimony = (id) => {
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
            preConfirm: () => {
                return axios.delete(route('cms_testimonies_destroy', id)).then((res) => {
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
                });
                router.visit(route('cms_testimonies_list'), { replace: true, method: 'get' });
            }
        });
    }

    const changeStatus = (testimony, action) => {
        const isApprove = action === 'approve';

        Swal2.fire({
            title: isApprove ? '¿Aprobar este testimonio?' : '¿Rechazar este testimonio?',
            text: isApprove
                ? 'Se mostrará en la página pública de testimonios.'
                : 'Dejará de mostrarse y el alumno no verá el estado.',
            icon: isApprove ? 'question' : 'warning',
            showCancelButton: true,
            confirmButtonColor: isApprove ? '#059669' : '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: isApprove ? 'Sí, aprobar' : 'Sí, rechazar',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const url = isApprove
                    ? route('cms_testimonies_approve', testimony.id)
                    : route('cms_testimonies_reject', testimony.id);

                return axios.post(url).then((res) => {
                    if (!res.data.success) {
                        Swal2.showValidationMessage(res.data.message);
                    }
                    return res;
                });
            },
            allowOutsideClick: () => !Swal2.isLoading(),
        }).then((result) => {
            if (result.isConfirmed) {
                Swal2.fire({
                    icon: 'success',
                    title: isApprove ? 'Testimonio aprobado' : 'Testimonio rechazado',
                    timer: 1500,
                    showConfirmButton: false,
                });
                router.reload({ only: ['testimonies', 'counters'] });
            }
        });
    };

    const showDetail = (testimony) => {
        Swal2.fire({
            title: testimony.author_name || 'Testimonio',
            html: `
                <div class="text-left text-sm">
                    <p class="mb-2"><strong>Curso:</strong> ${testimony.course ? testimony.course.description : '—'}</p>
                    <p class="mb-2"><strong>Calificación:</strong> ${testimony.rating || 0}/5</p>
                    <p class="whitespace-pre-line">${testimony.description || ''}</p>
                    ${testimony.image ? `<img src="${assetUrl + 'storage/' + testimony.image}" class="mt-3 rounded-lg max-h-64 mx-auto" />` : ''}
                </div>
            `,
            width: 600,
            confirmButtonText: 'Cerrar',
        });
    };
</script>

<template>
    <AppLayout title="Resumen">
        <div class="max-w-screen-2xl  mx-auto p-4 md:p-6 2xl:p-10">
            <!-- Breadcrumb Start -->
            <nav class="flex px-4 py-3 border border-stroke text-gray-700 mb-4 bg-gray-50 dark:bg-gray-800 dark:border-gray-700" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <Link :href="route('dashboard')" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                        <svg aria-hidden="true" class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                        Inicio
                        </Link>
                    </li>
                    <li>
                        <div class="flex items-center">
                        <svg aria-hidden="true" class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">CMS</span>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg aria-hidden="true" class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Testimonios</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- ====== Pestanas Start -->
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <button type="button"
                        @click="goTo({ source: 'cms' })"
                        class="px-4 py-2 rounded-lg text-sm font-semibold border transition"
                        :class="!isStudents
                            ? 'bg-blue-900 text-white border-blue-900'
                            : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600'">
                    Editoriales
                </button>
                <button type="button"
                        @click="goTo({ source: 'student' })"
                        class="px-4 py-2 rounded-lg text-sm font-semibold border transition inline-flex items-center gap-2"
                        :class="isStudents
                            ? 'bg-blue-900 text-white border-blue-900'
                            : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600'">
                    De alumnos
                    <span v-if="counters && counters.pending > 0"
                          class="inline-flex items-center justify-center min-w-[22px] h-[22px] px-1.5 rounded-full text-xs font-bold bg-amber-500 text-white">
                        {{ counters.pending }}
                    </span>
                </button>
            </div>

            <!-- ====== Filtros de estado (solo testimonios de alumnos) ====== -->
            <div v-if="isStudents" class="flex flex-wrap items-center gap-2 mb-4">
                <button type="button" @click="goTo({ source: 'student' })"
                        class="px-3 py-1.5 rounded-full text-xs font-semibold border"
                        :class="!approvalStatus ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600'">
                    Todos ({{ counters.all || 0 }})
                </button>
                <button type="button" @click="goTo({ source: 'student', approval_status: 'pending' })"
                        class="px-3 py-1.5 rounded-full text-xs font-semibold border"
                        :class="approvalStatus === 'pending' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-gray-600 border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600'">
                    Pendientes ({{ counters.pending || 0 }})
                </button>
                <button type="button" @click="goTo({ source: 'student', approval_status: 'approved' })"
                        class="px-3 py-1.5 rounded-full text-xs font-semibold border"
                        :class="approvalStatus === 'approved' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-600 border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600'">
                    Aprobados ({{ counters.approved || 0 }})
                </button>
                <button type="button" @click="goTo({ source: 'student', approval_status: 'rejected' })"
                        class="px-3 py-1.5 rounded-full text-xs font-semibold border"
                        :class="approvalStatus === 'rejected' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-gray-600 border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600'">
                    Rechazados ({{ counters.rejected || 0 }})
                </button>
            </div>

            <!-- ====== Table Section Start -->
            <div class="flex flex-col gap-10">
                <!-- ====== Table One Start -->
                <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
                    <div class="w-full p-4 border-b border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700">
                        <div class="grid grid-cols-3">
                            <div class="col-span-3 sm:col-span-1">
                                <form id="form-search-items" @submit.prevent="form.get(route('cms_items_list'))">
                                    <label for="table-search" class="sr-only">Search</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                                        </div>
                                        <input v-model="form.search" type="text" id="table-search-users" class="block p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Buscar por Descripción">
                                    </div>
                                </form>
                            </div>
                            <div class="col-span-3 sm:col-span-2">
                                <Keypad>
                                    <template #botones>
                                        <Link v-if="isStudents"
                                              v-can="'cms_testimonios_nuevo'"
                                              :href="route('cms_testimonies_students_create')"
                                              class="flex items-center justify-center inline-block px-6 py-2.5 bg-blue-900 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out">
                                            Nuevo testimonio de alumno
                                        </Link>
                                        <Link v-else
                                              v-can="'cms_testimonios_nuevo'"
                                              :href="route('cms_testimonies_create')"
                                              class="flex items-center justify-center inline-block px-6 py-2.5 bg-blue-900 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out">
                                            Nuevo
                                        </Link>
                                    </template>
                                </Keypad>
                            </div>
                        </div>
                    </div>
                    <div class="max-w-full overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead class="border-b border-stroke">
                                <tr class="bg-gray-50 text-left dark:bg-meta-4">
                                    <th  class="py-2 px-4 text-center font-medium text-black dark:text-white">
                                        Acciones
                                    </th>
                                    <template v-if="isStudents">
                                        <th class="py-2 px-4 font-medium text-black dark:text-white">Autor</th>
                                        <th class="py-2 px-4 font-medium text-black dark:text-white">Curso</th>
                                        <th class="py-2 px-4 font-medium text-black dark:text-white">Estrellas</th>
                                        <th class="py-2 px-4 font-medium text-black dark:text-white">Comentario</th>
                                        <th class="py-2 px-4 font-medium text-black dark:text-white">Estado</th>
                                        <th class="py-2 px-4 font-medium text-black dark:text-white">Fecha</th>
                                    </template>
                                    <template v-else>
                                        <th class="py-2 px-4 font-medium text-black dark:text-white">
                                            Título
                                        </th>
                                        <th class="py-2 px-4 font-medium text-black dark:text-white">
                                            Estado
                                        </th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="testimonies.data.length > 0">
                                    <tr v-for="(testimony, index) in testimonies.data" :key="testimony.id" class="border-b border-stroke">
                                        <td class="text-center py-2 dark:border-strokedark">
                                            <div class="flex flex-wrap items-center justify-center gap-1">
                                                <IaCorrectionMenu :loading="iaLoadingId === testimony.id"
                                                                  label="IA"
                                                                  @select="(mode) => correctWithIa(testimony, mode)" />

                                                <button v-can="'cms_testimonios_editar'"
                                                        @click="openModify(testimony)"
                                                        type="button"
                                                        title="Modificar"
                                                        class="text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:outline-none focus:ring-indigo-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center dark:bg-indigo-600 dark:hover:bg-indigo-700">
                                                    <font-awesome-icon :icon="faPencilAlt" />
                                                </button>

                                                <button @click="showDetail(testimony)"
                                                        type="button"
                                                        title="Ver detalle"
                                                        class="text-white bg-gray-700 hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center dark:bg-gray-600 dark:hover:bg-gray-700">
                                                    <font-awesome-icon :icon="faEye" />
                                                </button>

                                                <template v-if="isStudents">
                                                    <button v-if="testimony.approval_status !== 'approved'"
                                                            v-can="'cms_testimonios_aprobar'"
                                                            @click="changeStatus(testimony, 'approve')"
                                                            type="button"
                                                            title="Aprobar"
                                                            class="text-white bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:outline-none focus:ring-emerald-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center dark:bg-emerald-600 dark:hover:bg-emerald-700">
                                                        <font-awesome-icon :icon="faCheck" />
                                                    </button>
                                                    <button v-if="testimony.approval_status !== 'rejected'"
                                                            v-can="'cms_testimonios_aprobar'"
                                                            @click="changeStatus(testimony, 'reject')"
                                                            type="button"
                                                            title="Rechazar"
                                                            class="text-white bg-amber-600 hover:bg-amber-700 focus:ring-4 focus:outline-none focus:ring-amber-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center dark:bg-amber-600 dark:hover:bg-amber-700">
                                                        <font-awesome-icon :icon="faXmark" />
                                                    </button>
                                                </template>

                                                <Link v-else
                                                      v-can="'cms_testimonios_editar'"
                                                      :href="route('cms_testimonies_edit', testimony.id)"
                                                      title="Editor completo"
                                                      class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                                    <font-awesome-icon :icon="faPenToSquare" />
                                                </Link>

                                                <button v-can="'cms_testimonios_eliminar'"
                                                        @click="destroyTestimony(testimony.id)"
                                                        type="button"
                                                        title="Eliminar"
                                                        class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800">
                                                    <font-awesome-icon :icon="faTrashAlt" />
                                                </button>
                                            </div>
                                        </td>

                                        <template v-if="isStudents">
                                            <td class="py-2 px-2 dark:border-strokedark">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-9 h-9 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0">
                                                        <img v-if="testimony.image"
                                                             :src="assetUrl + 'storage/' + testimony.image"
                                                             class="w-full h-full object-cover" alt="" />
                                                    </div>
                                                    <span>{{ testimony.author_name || '—' }}</span>
                                                </div>
                                            </td>
                                            <td class="py-2 px-2 dark:border-strokedark">
                                                <span class="block">{{ testimony.course ? testimony.course.description : '—' }}</span>
                                                <span class="text-xs text-gray-500">{{ testimony.course && testimony.course.category ? testimony.course.category.description : '' }}</span>
                                            </td>
                                            <td class="py-2 px-2 text-center dark:border-strokedark whitespace-nowrap">
                                                <span class="text-amber-500">
                                                    <i v-for="n in 5" :key="n" :class="n <= (testimony.rating || 0) ? 'ri-star-fill' : 'ri-star-line'"></i>
                                                </span>
                                            </td>
                                            <td class="py-2 px-2 dark:border-strokedark max-w-xs">
                                                <span class="line-clamp-2 text-sm">{{ testimony.description }}</span>
                                                <span v-if="testimony.ia_corrected_at"
                                                      title="Comentario corregido con IA"
                                                      class="mt-1 inline-flex items-center gap-1 text-[11px] font-medium px-2 py-0.5 rounded border border-indigo-300 text-indigo-600 dark:text-indigo-300 dark:border-indigo-700">
                                                    <i class="ri-sparkling-2-line"></i> Corregido con IA
                                                </span>
                                            </td>
                                            <td class="py-2 px-2 text-center dark:border-strokedark">
                                                <span class="text-xs font-medium mr-2 px-2.5 py-0.5 rounded border"
                                                      :class="statusBadgeClass(testimony.approval_status)">
                                                    {{ statusText(testimony.approval_status) }}
                                                </span>
                                            </td>
                                            <td class="py-2 px-2 text-center dark:border-strokedark whitespace-nowrap">
                                                {{ formatDate(testimony.created_at) }}
                                            </td>
                                        </template>

                                        <template v-else>
                                            <td class="py-2 px-2 dark:border-strokedark">
                                                <span class="block">{{ testimony.title || '—' }}</span>
                                                <span v-if="testimony.item_label" class="text-xs text-gray-500">{{ testimony.item_label }}</span>
                                            </td>
                                            <td class="text-center py-2 px-2 dark:border-strokedark">
                                                <span v-if="testimony.status" class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-blue-400 border border-blue-400">Activo</span>
                                                <span v-else class="bg-red-100 text-red-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-red-400 border border-red-400">Inactivo</span>
                                            </td>
                                        </template>
                                    </tr>
                                </template>
                                <template v-else>
                                    <td :colspan="isStudents ? 7 : 3" class="p-2 text-center">
                                        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                                            No existen registros para mostrar.
                                        </div>
                                    </td>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :data="testimonies" />
                </div>
            </div>
        </div>

        <ModifyModal :show="modifyOpen"
                     :testimony="modifyTestimony"
                     :initial-comment="modifyInitialComment"
                     :initial-ia-mode="modifyIaMode"
                     @close="closeModify"
                     @saved="onModifySaved" />
    </AppLayout>
</template>
