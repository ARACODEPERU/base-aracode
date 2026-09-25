<script setup>
    // Botón "Cambio de moneda" del header: consulta el TC SUNAT (via Migo)
    // y lo guarda en sales_exchange_rates. Requiere permiso invo_tipo_cambio.
    import { ref } from 'vue';
    import axios from 'axios';
    import ModalSmall from '@/Components/ModalSmall.vue';

    // route() es la funcion global de Ziggy (inyectada via @routes en app.blade.php)

    const props = defineProps({
        show: { type: Boolean, default: false },
        onClose: { type: Function, default: () => {} },
    });

    const currencies = [
        { code: 'USD', label: 'Dólares Americanos' },
    ];

    const selectedCurrency = ref('USD');
    const loading = ref(false);
    const currentRate = ref(null);
    const multiCurrencyEnabled = ref(false);

    const loadCurrent = async () => {
        loading.value = true;
        try {
            const response = await axios.get(route('sale_exchange_rates_current'), {
                params: { currency: selectedCurrency.value },
            });
            currentRate.value = response.data.rates[selectedCurrency.value] || null;
            multiCurrencyEnabled.value = response.data.multi_currency_enabled;
        } catch (error) {
            currentRate.value = null;
        } finally {
            loading.value = false;
        }
    };

    const fetchFromSunat = async () => {
        loading.value = true;
        try {
            const response = await axios.post(route('sale_exchange_rates_fetch'), {});
            if (response.data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Tipo de cambio actualizado',
                    text: response.data.message,
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                await loadCurrent();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.data.message || 'No se pudo consultar el tipo de cambio',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }
        } catch (error) {
            const message = error.response?.data?.message || 'No se pudo conectar con el servicio de tipo de cambio';
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        } finally {
            loading.value = false;
        }
    };

    const onCurrencyChange = () => {
        loadCurrent();
    };

    const formatRate = (value) => {
        if (value === null || value === undefined) return '-';
        return parseFloat(value).toFixed(4);
    };

    defineExpose({ loadCurrent });
</script>
<template>
    <ModalSmall :show="show" :onClose="onClose">
        <template #title>
            <h3 class="text-lg font-semibold dark:text-white">Cambio actual de moneda</h3>
        </template>
        <template #message>
            Consulta el tipo de cambio oficial de SUNAT y guárdalo para facturación en dólares.
        </template>
        <template #content>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">
                        Convertir a soles desde
                    </label>
                    <select v-model="selectedCurrency" @change="onCurrencyChange" class="form-select w-full">
                        <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                            {{ currency.label }}
                        </option>
                    </select>
                </div>

                <div class="rounded-lg border border-slate-200 dark:border-slate-700 p-4 bg-slate-50 dark:bg-slate-800">
                    <div v-if="loading" class="text-sm text-slate-500 dark:text-slate-400">
                        Consultando...
                    </div>
                    <template v-else-if="currentRate">
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Precio compra:</span>
                                <span class="font-bold text-slate-800 dark:text-white ml-1">S/ {{ formatRate(currentRate.purchase) }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Precio venta:</span>
                                <span class="font-bold text-slate-800 dark:text-white ml-1">S/ {{ formatRate(currentRate.sale) }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Fecha SUNAT:</span>
                                <span class="font-medium text-slate-800 dark:text-white ml-1">{{ currentRate.date }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Fuente:</span>
                                <span class="font-medium text-slate-800 dark:text-white ml-1 capitalize">{{ currentRate.source }}</span>
                            </div>
                        </div>
                        <div v-if="currentRate.is_stale" class="mt-3 text-xs text-amber-600 dark:text-amber-400">
                            ⚠️ Este tipo de cambio es del último día disponible (SUNAT aún no publica el de hoy).
                        </div>
                    </template>
                    <div v-else class="text-sm text-slate-500 dark:text-slate-400">
                        No hay tipo de cambio registrado todavía. Usa el botón «Consultar a SUNAT».
                    </div>
                </div>

                <div v-if="!multiCurrencyEnabled" class="text-xs text-slate-500 dark:text-slate-400">
                    ℹ️ El sistema está en modo solo soles (parámetro PTM0004 desactivado). El TC se consulta y queda guardado para cuando se active el trabajo con dólares.
                </div>
            </div>
        </template>
        <template #buttons>
            <button type="button" class="btn btn-primary px-4 py-2" :disabled="loading" @click="fetchFromSunat">
                <svg v-if="loading" class="animate-spin w-4 h-4 mr-1" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Consultar a SUNAT
            </button>
        </template>
    </ModalSmall>
</template>
