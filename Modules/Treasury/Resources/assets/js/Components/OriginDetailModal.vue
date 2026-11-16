<script setup>
    import { computed } from 'vue';
    import ModalLarge from '@/Components/ModalLarge.vue';

    const props = defineProps({
        show: { type: Boolean, default: false },
        detail: { type: Object, default: null },
        onClose: { type: Function, default: () => {} },
    });

    // Campos visibles; se omiten los que el origen no trae.
    const fields = computed(() => {
        if (!props.detail) {
            return [];
        }

        const detail = props.detail;
        const comprobante = [detail.comprobante_tipo, detail.comprobante].filter(Boolean).join(' ');

        return [
            { label: 'Tipo', value: detail.tipo },
            { label: 'Comprobante', value: comprobante },
            { label: 'Cliente', value: detail.cliente },
            { label: 'Vencimiento', value: detail.vencimiento },
            { label: 'Concepto', value: detail.concepto },
            { label: 'Método de pago', value: detail.metodo },
            { label: 'Referencia', value: detail.referencia },
        ].filter((field) => field.value);
    });
</script>

<template>
    <ModalLarge :show="show" :on-close="onClose" :icon="'/img/cuenta-bancaria.png'">
        <template #title>Detalle del movimiento</template>
        <template #message>
            <span>Origen del cobro o pago registrado</span>
        </template>
        <template #content>
            <div v-if="fields.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div v-for="field in fields" :key="field.label">
                    <p class="text-xs uppercase tracking-wide text-gray-400">{{ field.label }}</p>
                    <p class="mt-0.5 text-sm font-medium text-gray-800 dark:text-gray-100">{{ field.value }}</p>
                </div>
            </div>
            <p v-else class="text-sm text-gray-500 dark:text-gray-400">
                Este movimiento no tiene un comprobante de origen: fue registrado a mano en tesorería.
            </p>
        </template>
    </ModalLarge>
</template>
