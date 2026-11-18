<script setup>
    import { computed } from 'vue';
    import { Link } from '@inertiajs/vue3';
    import ModalLarge from '@/Components/ModalLarge.vue';

    const props = defineProps({
        show: { type: Boolean, default: false },
        detail: { type: Object, default: null },
        onClose: { type: Function, default: () => {} },
    });

    const money = (value, currency = 'PEN') => {
        try {
            return new Intl.NumberFormat('es-PE', { style: 'currency', currency }).format(value || 0);
        } catch (e) {
            return `${currency} ${Number(value || 0).toFixed(2)}`;
        }
    };

    // Campos visibles; se omiten los que el origen no trae.
    const fields = computed(() => {
        if (!props.detail) {
            return [];
        }

        const detail = props.detail;
        const comprobante = [detail.comprobante_tipo, detail.comprobante].filter(Boolean).join(' ');
        const moneda = detail.moneda || 'PEN';
        const total = detail.total ? money(detail.total, moneda) : null;
        const detraccion = detail.detraccion
            ? `${money(detail.detraccion, moneda)}${detail.detraccion_porcentaje ? ` (${Number(detail.detraccion_porcentaje)} %)` : ''}`
            : null;

        return [
            { label: 'Tipo', value: detail.tipo },
            { label: 'Comprobante', value: comprobante },
            { label: 'Total del comprobante', value: total },
            { label: 'Detracción', value: detraccion },
            { label: 'Auto-detracción', value: detail.detraccion_auto ? 'Sí' : null },
            { label: 'Cliente', value: detail.cliente },
            { label: 'Vencimiento', value: detail.vencimiento },
            { label: 'Concepto', value: detail.concepto },
            { label: 'Método de pago', value: detail.metodo },
            { label: 'Referencia', value: detail.referencia },
        ].filter((field) => field.value);
    });

    // Datos propios de transferencias, comisiones, impuestos y órdenes de pago.
    const extraFields = computed(() =>
        (props.detail?.meta || []).filter((field) => field && field.value)
    );

    const conciliacion = computed(() => {
        const detail = props.detail;

        if (!detail || detail.conciliado === undefined) return null;
        if (detail.conciliacion_detalle) return detail.conciliacion_detalle;
        if (detail.conciliacion_cerrada) return 'Conciliado (conciliación cerrada)';

        return detail.conciliado ? 'Conciliado' : 'Sin conciliar';
    });

    const estado = computed(() => {
        const detail = props.detail;

        if (!detail) return null;
        if (detail.anulado) return 'Movimiento anulado: no cuenta para el saldo.';

        return detail.estado || null;
    });
</script>

<template>
    <ModalLarge :show="show" :on-close="onClose" :icon="'/img/cuenta-bancaria.png'">
        <template #title>Detalle del movimiento</template>
        <template #message>
            <span>Origen del cobro o pago registrado</span>
        </template>
        <template #content>
            <div class="space-y-4">
                <div v-if="estado" class="rounded-lg bg-danger/10 px-3 py-2 text-sm font-medium text-danger">
                    {{ estado }}
                </div>

                <div v-if="fields.length || extraFields.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div v-for="field in [...fields, ...extraFields]" :key="field.label">
                        <p class="text-xs uppercase tracking-wide text-gray-400">{{ field.label }}</p>
                        <p class="mt-0.5 text-sm font-medium text-gray-800 dark:text-gray-100">{{ field.value }}</p>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400">
                    Este movimiento no tiene un comprobante de origen: fue registrado a mano en tesorería.
                </p>

                <p v-if="conciliacion" class="text-xs text-gray-500 dark:text-gray-400">
                    Conciliación bancaria: {{ conciliacion }}
                </p>

                <Link
                    v-if="detail?.url"
                    :href="detail.url"
                    class="btn btn-sm btn-outline-primary"
                >
                    {{ detail.url_label || 'Ver el origen' }}
                </Link>
            </div>
        </template>
    </ModalLarge>
</template>
