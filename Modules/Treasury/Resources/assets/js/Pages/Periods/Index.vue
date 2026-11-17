<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, router } from '@inertiajs/vue3';
    import ModalMedium from '@/Components/ModalMedium.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { ref } from 'vue';
    import { faLock, faLockOpen, faShieldHalved } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        periods: { type: Array, default: () => [] },
        canClose: { type: Boolean, default: false },
        canReopen: { type: Boolean, default: false },
    });

    const notifyFlash = (page) => {
        const flash = page?.props?.flash || {};
        if (flash.error) window.alertaSistema('No se pudo completar', flash.error, 'error');
        else if (flash.success) window.alertaSistema('Listo', flash.success, 'success');
    };

    const closePeriod = async (period) => {
        const result = await window.alertaSistema(
            'Cerrar periodo',
            `Vas a cerrar ${period.label}. No se podrán registrar, editar ni anular movimientos con fecha de ese mes. La foto de saldos quedará guardada.`,
            'warning',
            { duration: 0, showCancelButton: true, confirmButtonText: 'Sí, cerrar', cancelButtonText: 'Cancelar' }
        );

        if (!result?.isConfirmed) return;

        router.post(route('treasury_periods_close'), { year: period.year, month: period.month }, {
            preserveScroll: true,
            onSuccess: notifyFlash,
        });
    };

    const reopenModal = ref(false);
    const selected = ref(null);
    const reason = ref('');
    const errors = ref({});

    const openReopen = (period) => {
        selected.value = period;
        reason.value = '';
        errors.value = {};
        reopenModal.value = true;
    };

    const confirmReopen = () => {
        router.post(route('treasury_periods_reopen'), {
            year: selected.value.year,
            month: selected.value.month,
            reason: reason.value,
        }, {
            preserveScroll: true,
            onSuccess: (page) => {
                notifyFlash(page);
                reopenModal.value = false;
            },
            onError: (pageErrors) => {
                errors.value = pageErrors;
                window.alertaSistema('Revisa el motivo', 'El motivo es obligatorio y debe explicar la reapertura.', 'warning');
            },
        });
    };
</script>

<template>
    <AppLayout title="Periodos de tesorería">
        <Head title="Tesorería · Periodos">
            <meta name="aracodito-view" content="new" />
        </Head>
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Tesorería</span></li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Periodos</span></li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div class="panel flex flex-col gap-3 p-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Cierre de periodos</h2>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                        Un periodo cerrado bloquea altas, ediciones y anulaciones con fecha de ese mes. El cierre guarda una foto
                        por cuenta y moneda. La reapertura exige motivo y queda registrada en auditoría.
                    </p>
                </div>
                <div class="rounded-lg bg-primary/10 p-3 text-primary">
                    <font-awesome-icon :icon="faShieldHalved" class="text-xl" />
                </div>
            </div>

            <div class="panel overflow-hidden p-0">
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th>Periodo</th>
                                <th>Estado</th>
                                <th>Cerró</th>
                                <th>Reabrió</th>
                                <th class="text-center">Movimientos</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="period in periods" :key="period.year + '-' + period.month">
                                <td class="font-medium text-gray-800 dark:text-gray-100">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="whitespace-nowrap">{{ period.label }}</span>
                                        <span v-if="period.is_current" class="rounded bg-primary/10 px-2 py-0.5 text-xs text-primary">En curso</span>
                                    </div>
                                </td>
                                <td>
                                    <span
                                        class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium"
                                        :class="period.status === 'closed' ? 'bg-danger/10 text-danger' : 'bg-success/10 text-success'"
                                    >
                                        <font-awesome-icon :icon="period.status === 'closed' ? faLock : faLockOpen" />
                                        {{ period.status === 'closed' ? 'Cerrado' : 'Abierto' }}
                                    </span>
                                </td>
                                <td class="text-sm">
                                    <span v-if="period.closed_at">{{ period.closed_at }}<br /><span class="text-xs text-gray-500">{{ period.closed_by }}</span></span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="text-sm">
                                    <span v-if="period.reopened_at">{{ period.reopened_at }}</span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="text-center text-sm">
                                    <span :class="period.has_movements ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400'">
                                        {{ period.has_movements ? 'Sí' : 'Sin datos' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            v-if="canClose && period.status !== 'closed'"
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            @click="closePeriod(period)"
                                        >
                                            Cerrar
                                        </button>
                                        <button
                                            v-if="canReopen && period.status === 'closed'"
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            @click="openReopen(period)"
                                        >
                                            Reabrir
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ModalMedium :show="reopenModal" :on-close="() => (reopenModal = false)" :icon="'/img/cuenta-bancaria.png'">
            <template #title>Reabrir periodo</template>
            <template #message>
                <span>La reapertura permite registrar movimientos en {{ selected?.label }}. Queda auditada con tu usuario.</span>
            </template>
            <template #content>
                <div>
                    <InputLabel value="Motivo *" class="mb-1.5" />
                    <textarea v-model="reason" rows="3" class="form-textarea w-full" placeholder="Ej: ajuste solicitado por contabilidad por una factura omitida"></textarea>
                    <InputError :message="errors.reason" />
                </div>
            </template>
            <template #buttons>
                <PrimaryButton :disabled="!reason.trim()" @click="confirmReopen">Reabrir periodo</PrimaryButton>
            </template>
        </ModalMedium>
    </AppLayout>
</template>
