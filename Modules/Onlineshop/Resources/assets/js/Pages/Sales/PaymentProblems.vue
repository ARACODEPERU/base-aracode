<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Keypad from '@/Components/Keypad.vue';
    import Swal from "sweetalert2";
    import { ref } from "vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { router } from '@inertiajs/vue3';
    import { TransitionRoot, TransitionChild, Dialog, DialogPanel, DialogOverlay } from '@headlessui/vue';

    const props = defineProps({
        items: {
            type: Array,
            default: () => [],
        }
    });

    const displayModalDetails = ref(false);
    const selectedItem = ref(null);

    const openModalDetails = (item) => {
        selectedItem.value = item;
        displayModalDetails.value = true;
    };

    const closeModalDetails = () => {
        displayModalDetails.value = false;
        selectedItem.value = null;
    };

    const formatCurrency = (value) => {
        if (!value && value !== 0) return 'S/ 0.00';
        return `S/ ${parseFloat(value).toFixed(2)}`;
    };

    const getCoursesList = (item) => {
        if (!item.courses_info) return [];
        if (Array.isArray(item.courses_info)) return item.courses_info;
        if (typeof item.courses_info === 'string') {
            try {
                return JSON.parse(item.courses_info);
            } catch {
                return [];
            }
        }
        return [];
    };

    const typeBadgeClass = (type) => {
        if (type === 'payment_problem') {
            return 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-500';
        }
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
    };

    const typeIcon = (type) => {
        if (type === 'payment_problem') {
            return 'fa-exclamation-triangle';
        }
        return 'fa-cart-plus';
    };

    const getPhoneFull = (item) => {
        if (item.phone_country && item.phone) {
            return `+${item.phone_country} ${item.phone}`;
        }
        return item.phone || '-';
    };

    const cleanOldRecords = () => {
        Swal.fire({
            title: '¿Limpiar registros antiguos?',
            text: 'Se eliminarán todos los registros de pagos sin completar y carritos abandonados con más de 30 días de antigüedad. Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            padding: '2em',
            customClass: 'sweet-alerts',
        }).then((result) => {
            if (result.isConfirmed) {
                axios.post(route('onlineshop_payment_problems_clean'), { days: 30 })
                    .then((response) => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Registros eliminados',
                            text: response.data.message,
                            padding: '2em',
                            customClass: 'sweet-alerts',
                        }).then(() => {
                            router.reload({ preserveScroll: true });
                        });
                    })
                    .catch((error) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudieron eliminar los registros antiguos.',
                            padding: '2em',
                            customClass: 'sweet-alerts',
                        });
                    });
            }
        });
    };
</script>

<template>
    <AppLayout title="Problemas de Pago">
        <Navigation :routeModule="route('onlineshop_dashboard')" :titleModule="'Comercio Online'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Problemas de Pago</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="panel p-0">
                <div class="w-full p-4">
                    <div class="grid grid-cols-2">
                        <div class="col-span-2 sm:col-span-1">
                            <div class="flex items-center gap-2">
                                <svg class="w-6 h-6 text-red-500" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path d="M256 32c14.2 0 27.3 7.5 34.5 19.8l216 368c7.3 12.4 7.3 27.7 .2 40.1S486.3 480 472 480L40 480c-14.3 0-27.6-7.7-34.7-20.1s-7-27.8 .2-40.1l216-368C228.7 39.5 241.8 32 256 32zm0 128c-13.3 0-24 10.7-24 24l0 112c0 13.3 10.7 24 24 24s24-10.7 24-24l0-112c0-13.3-10.7-24-24-24zm32 224a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/>
                                </svg>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Problemas de Pago</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Pagos aprobados que no completaron el registro y carritos abandonados</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <Keypad>
                                <template #botones>
                                    <button @click="cleanOldRecords" class="btn btn-warning uppercase text-xs">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                            <path d="M135.2 17.7L128 32 32 32C14.3 32 0 46.3 0 64S14.3 96 32 96l384 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-96 0-7.2-14.3C307.4 6.8 296.3 0 284.2 0L163.8 0c-12.1 0-23.2 6.8-28.6 17.7zM416 128L32 128 53.2 467c1.6 25.3 22.6 45 47.9 45l245.8 0c25.3 0 46.3-19.7 47.9-45L416 128z"/>
                                        </svg>
                                        Limpiar registros +30 días
                                    </button>
                                </template>
                            </Keypad>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th class="w-12">Tipo</th>
                                <th>Nombre / Email</th>
                                <th>Teléfono</th>
                                <th>Monto</th>
                                <th>Método de pago</th>
                                <th>Fecha</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="items.length > 0">
                                <tr v-for="(item, index) in items" :key="index" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap" :class="typeBadgeClass(item.type)">
                                            <i :class="'fa ' + typeIcon(item.type)"></i>
                                            {{ item.type_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex flex-col">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ item.clie_full_name || '—' }}</span>
                                            <span class="text-xs text-gray-500">{{ item.email || '—' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-sm">{{ getPhoneFull(item) }}</span>
                                    </td>
                                    <td>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(item.amount) }}</span>
                                    </td>
                                    <td>
                                        <span v-if="item.payment_method" class="text-sm capitalize">{{ item.payment_method }}</span>
                                        <span v-else class="text-sm text-gray-400">—</span>
                                    </td>
                                    <td>
                                        <span class="text-sm whitespace-nowrap">{{ item.created_at }}</span>
                                    </td>
                                    <td class="text-center">
                                        <button @click="openModalDetails(item)" class="btn btn-outline-primary px-3 py-1 text-xs">
                                            <i class="fa fa-eye mr-1"></i> Ver
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <template v-else>
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="w-12 h-12 text-gray-300" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                                                <path d="M96 32C60.7 32 32 60.7 32 96l0 256c0 35.3 28.7 64 64 64l224 0 0-64-224 0 0-256 160 0 0 80c0 17.7 14.3 32 32 32l80 0 0 48 64 0 0-80c0-8.5-3.4-16.6-9.4-22.6L337.6 41.4c-6-6-14.1-9.4-22.6-9.4L96 32zm288 80l0-48 0 48 48 0-48 0zM352 368c0-8.8 7.2-16 16-16l208 0 0-64L288 288l0 160 0 32 288 0c17.7 0 32-14.3 32-32l0-96c0-17.7-14.3-32-32-32l-208 0c-8.8 0-16 7.2-16 16l0 80-16 0z"/>
                                            </svg>
                                            <span class="text-base font-medium">No hay registros pendientes</span>
                                            <span class="text-sm">Todos los pagos han sido completados o no hay carritos abandonados.</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal de Detalles -->
        <TransitionRoot appear :show="displayModalDetails" as="template">
            <Dialog as="div" @close="closeModalDetails" class="relative z-50">
                <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                    <DialogOverlay class="fixed inset-0 bg-[black]/60" />
                </TransitionChild>
                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-start justify-center px-4 py-8">
                        <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                            <DialogPanel class="relative overflow-hidden w-full max-w-2xl rounded-lg bg-white dark:bg-gray-800 shadow-xl">
                                <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium" :class="typeBadgeClass(selectedItem?.type)">
                                            <i :class="'fa ' + typeIcon(selectedItem?.type)"></i>
                                            {{ selectedItem?.type_label }}
                                        </span>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detalles</h3>
                                    </div>
                                    <button @click="closeModalDetails" type="button" class="text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                                        <svg width="24" height="24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                            <path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="p-6 space-y-6" v-if="selectedItem">
                                    <!-- Información del contacto -->
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Información de contacto</h4>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <span class="block text-xs text-gray-500">Nombre</span>
                                                <span class="block font-medium text-gray-900 dark:text-white">{{ selectedItem.clie_full_name || '—' }}</span>
                                            </div>
                                            <div>
                                                <span class="block text-xs text-gray-500">Email</span>
                                                <span class="block font-medium text-gray-900 dark:text-white">{{ selectedItem.email || '—' }}</span>
                                            </div>
                                            <div>
                                                <span class="block text-xs text-gray-500">Teléfono</span>
                                                <span class="block font-medium text-gray-900 dark:text-white">{{ getPhoneFull(selectedItem) }}</span>
                                            </div>
                                            <div>
                                                <span class="block text-xs text-gray-500">Monto</span>
                                                <span class="block font-medium text-gray-900 dark:text-white">{{ formatCurrency(selectedItem.amount) }}</span>
                                            </div>
                                            <div v-if="selectedItem.payment_method">
                                                <span class="block text-xs text-gray-500">Método de pago</span>
                                                <span class="block font-medium text-gray-900 dark:text-white capitalize">{{ selectedItem.payment_method }}</span>
                                            </div>
                                            <div>
                                                <span class="block text-xs text-gray-500">Fecha</span>
                                                <span class="block font-medium text-gray-900 dark:text-white">{{ selectedItem.created_at }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cursos -->
                                    <div v-if="getCoursesList(selectedItem).length > 0">
                                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Cursos</h4>
                                        <div class="space-y-2">
                                            <div v-for="(course, cIndex) in getCoursesList(selectedItem)" :key="cIndex"
                                                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                                <img v-if="course.image" :src="course.image" alt="" class="w-12 h-12 rounded-lg object-cover flex-shrink-0" />
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ course.name || course.title || 'Curso' }}</p>
                                                    <p v-if="course.price" class="text-xs text-gray-500">{{ formatCurrency(course.price) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Datos de pago (solo para payment_problem) -->
                                    <div v-if="selectedItem.type === 'payment_problem' && selectedItem.payment_data">
                                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Datos del pago (MercadoPago)</h4>
                                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                            <pre class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-wrap overflow-auto max-h-48">{{ JSON.stringify(selectedItem.payment_data, null, 2) }}</pre>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-end p-4 border-t border-gray-200 dark:border-gray-700">
                                    <button @click="closeModalDetails" type="button" class="btn btn-primary">Cerrar</button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>

<style scoped>
.table-responsive {
    overflow-x: auto;
}
</style>
