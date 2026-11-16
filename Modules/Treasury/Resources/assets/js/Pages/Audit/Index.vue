<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, router, Link } from '@inertiajs/vue3';
    import { ref } from 'vue';
    import { faMagnifyingGlass, faShieldHalved } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        logs: { type: Object, required: true },
        filters: { type: Object, default: () => ({}) },
        entities: { type: Array, default: () => [] },
        actions: { type: Array, default: () => [] },
    });

    const query = ref({
        entity: props.filters.entity || '',
        action: props.filters.action || '',
        from: props.filters.from || '',
        to: props.filters.to || '',
    });

    const search = () => {
        router.get(route('treasury_audit_index'), query.value, { preserveScroll: true, preserveState: true });
    };

    const entityLabels = {
        period: 'Periodo',
        reconciliation: 'Conciliación',
        reconciliation_item: 'Partida de extracto',
        transfer: 'Transferencia',
        payment_order: 'Orden de pago',
        supplier: 'Proveedor',
        recurring_expense: 'Gasto recurrente',
        cost_center: 'Centro de costo',
    };

    const actionLabels = {
        created: 'Creado',
        updated: 'Actualizado',
        deleted: 'Eliminado',
        voided: 'Anulado',
        closed: 'Cerrado',
        reopened: 'Reabierto',
        approved: 'Aprobado',
        rejected: 'Rechazado',
        paid: 'Pagado',
        executed: 'Ejecutado',
        deactivated: 'Desactivado',
    };
</script>

<template>
    <AppLayout title="Auditoría de tesorería">
        <Head title="Tesorería · Auditoría">
            <meta name="aracodito-view" content="new" />
        </Head>
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Tesorería</span></li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Auditoría</span></li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div class="panel flex flex-col gap-3 p-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Auditoría inmutable</h2>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                        Cada cierre, pago, anulación o cambio queda registrado con usuario, IP y fecha. Los registros no se
                        editan ni se borran.
                    </p>
                </div>
                <div class="rounded-lg bg-primary/10 p-3 text-primary">
                    <font-awesome-icon :icon="faShieldHalved" class="text-xl" />
                </div>
            </div>

            <div class="panel flex flex-col gap-3 p-5 lg:flex-row lg:items-end">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Entidad</label>
                    <select v-model="query.entity" class="form-select sm:w-48">
                        <option value="">Todas</option>
                        <option v-for="entity in entities" :key="entity" :value="entity">
                            {{ entityLabels[entity] || entity }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Acción</label>
                    <select v-model="query.action" class="form-select sm:w-44">
                        <option value="">Todas</option>
                        <option v-for="action in actions" :key="action" :value="action">
                            {{ actionLabels[action] || action }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Desde</label>
                    <input v-model="query.from" type="date" class="form-input sm:w-40" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Hasta</label>
                    <input v-model="query.to" type="date" class="form-input sm:w-40" />
                </div>
                <button type="button" class="btn btn-primary" @click="search">
                    <font-awesome-icon :icon="faMagnifyingGlass" class="mr-1" /> Filtrar
                </button>
            </div>

            <div class="panel overflow-hidden p-0">
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th>Fecha</th>
                                <th>Entidad</th>
                                <th>Acción</th>
                                <th>Usuario</th>
                                <th>IP</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="6" class="py-8 text-center text-sm text-gray-500">Sin registros para el filtro.</td>
                            </tr>
                            <tr v-for="log in logs.data" :key="log.id" class="align-top">
                                <td class="whitespace-nowrap text-sm">{{ log.created_at }}</td>
                                <td class="text-sm">
                                    {{ entityLabels[log.entity] || log.entity }}
                                    <span v-if="log.entity_id" class="text-xs text-gray-500">#{{ log.entity_id }}</span>
                                </td>
                                <td>
                                    <span class="rounded-md bg-primary/10 px-2 py-1 text-xs font-medium text-primary">
                                        {{ actionLabels[log.action] || log.action }}
                                    </span>
                                </td>
                                <td class="text-sm">{{ log.user || 'Sistema' }}</td>
                                <td class="font-mono text-xs text-gray-500">{{ log.ip || '—' }}</td>
                                <td class="text-xs">
                                    <details v-if="log.new_values || log.old_values || log.meta">
                                        <summary class="cursor-pointer text-primary">Ver datos</summary>
                                        <pre class="mt-2 max-w-md overflow-x-auto rounded bg-gray-50 p-2 text-[11px] dark:bg-gray-800">{{ JSON.stringify({ antes: log.old_values, despues: log.new_values, extra: log.meta }, null, 2) }}</pre>
                                    </details>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="logs.links.length > 3" class="flex flex-wrap gap-1 border-t border-gray-200 p-4 dark:border-gray-700">
                    <Link
                        v-for="link in logs.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="rounded px-3 py-1 text-sm"
                        :class="link.active ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
