<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, router } from '@inertiajs/vue3';
    import { faRotate, faTriangleExclamation, faCircleCheck } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        movements: { type: Array, default: () => [] },
        counts: { type: Object, default: () => ({}) },
    });

    const money = (value, currency = 'PEN') => {
        try {
            return new Intl.NumberFormat('es-PE', { style: 'currency', currency }).format(value || 0);
        } catch (e) {
            return `${currency} ${Number(value || 0).toFixed(2)}`;
        }
    };

    const notifyFlash = (page) => {
        const flash = page?.props?.flash || {};
        if (flash.error) window.alertaSistema('Quedaron movimientos con error', flash.error, 'error');
        else if (flash.success) window.alertaSistema('Reintento completado', flash.success, 'success');
    };

    const retry = (movement) => {
        router.post(route('treasury_integration_retry'), { movement_id: movement.id }, {
            preserveScroll: true,
            onSuccess: notifyFlash,
        });
    };

    const retryAll = async () => {
        const result = await window.alertaSistema(
            'Reintentar todo',
            'Se reprocesarán los movimientos fallidos y pendientes. La clave única impide duplicar el libro.',
            'question',
            { duration: 0, showCancelButton: true, confirmButtonText: 'Sí, reintentar', cancelButtonText: 'Cancelar' }
        );

        if (!result?.isConfirmed) return;

        router.post(route('treasury_integration_retry'), {}, {
            preserveScroll: true,
            onSuccess: notifyFlash,
        });
    };

    const statusClass = (status) => ({
        failed: 'bg-danger/10 text-danger',
        pending: 'bg-warning/10 text-warning',
        processing: 'bg-primary/10 text-primary',
    }[status] || 'bg-gray-100 text-gray-600');
</script>

<template>
    <AppLayout title="Integración de tesorería">
        <Head title="Tesorería · Integración">
            <meta name="aracodito-view" content="new" />
        </Head>
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Tesorería</span></li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Integración</span></li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div class="panel flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Movimientos que no llegaron al libro</h2>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                        Ventas, cuotas o pagos rechazados por periodo cerrado, moneda sin tipo de cambio o error de escritura.
                        Reintentar es seguro: la clave única evita duplicados.
                    </p>
                </div>
                <button type="button" class="btn btn-primary" :disabled="movements.length === 0" @click="retryAll">
                    <font-awesome-icon :icon="faRotate" class="mr-1" /> Reintentar todo
                </button>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Con error</p>
                    <p class="mt-2 text-2xl font-bold text-danger">{{ counts.failed || 0 }}</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pendientes</p>
                    <p class="mt-2 text-2xl font-bold text-warning">{{ counts.pending || 0 }}</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Procesados</p>
                    <p class="mt-2 text-2xl font-bold text-success">{{ counts.processed || 0 }}</p>
                </div>
            </div>

            <div class="panel overflow-hidden p-0">
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th>Origen</th>
                                <th>Fecha</th>
                                <th class="text-right">Monto</th>
                                <th>Estado</th>
                                <th class="min-w-[260px]">Error</th>
                                <th class="text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="movements.length === 0">
                                <td colspan="6" class="py-8 text-center text-sm text-gray-500">
                                    <font-awesome-icon :icon="faCircleCheck" class="mr-1 text-success" />
                                    Todo está registrado en el libro.
                                </td>
                            </tr>
                            <tr v-for="movement in movements" :key="movement.id" class="align-top">
                                <td class="text-sm">
                                    <p class="font-medium">{{ movement.module }} · {{ movement.source_type }} #{{ movement.source_id }}</p>
                                    <p class="text-xs text-gray-500">{{ movement.operation }} · {{ movement.description || movement.reference || 'Sin detalle' }}</p>
                                </td>
                                <td class="whitespace-nowrap text-sm">{{ movement.movement_date }}</td>
                                <td class="text-right font-semibold">{{ money(movement.amount, movement.currency) }}</td>
                                <td>
                                    <span class="rounded-md px-2 py-1 text-xs font-medium" :class="statusClass(movement.status)">
                                        {{ movement.status }}
                                    </span>
                                </td>
                                <td class="text-xs">
                                    <span v-if="movement.error" class="inline-flex items-start gap-1 text-danger">
                                        <font-awesome-icon :icon="faTriangleExclamation" class="mt-0.5" />
                                        {{ movement.error }}
                                    </span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="retry(movement)">
                                        <font-awesome-icon :icon="faRotate" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
