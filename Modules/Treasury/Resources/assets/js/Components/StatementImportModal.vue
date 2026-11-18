<script setup>
    import ModalMedium from '@/Components/ModalMedium.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { computed, ref, watch } from 'vue';
    import {
        faArrowLeft,
        faArrowRight,
        faCircleCheck,
        faCircleInfo,
        faFileArrowUp,
        faRotate,
        faTriangleExclamation,
        faWandMagicSparkles,
    } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        show: { type: Boolean, default: false },
        accountId: { type: Number, default: null },
        until: { type: String, default: null },
        currency: { type: String, default: 'PEN' },
    });

    const emit = defineEmits(['close', 'applied']);

    const paso = ref(1);
    const archivo = ref(null);
    const inputArchivo = ref(null);
    const corte = ref(props.until || new Date().toISOString().slice(0, 10));
    const cargando = ref(false);
    const error = ref('');
    const lectura = ref(null);
    const mapeo = ref({});
    const resultado = ref(null);

    const headers = computed(() => lectura.value?.headers || []);
    const filas = computed(() => lectura.value?.rows || []);
    const campos = computed(() => lectura.value?.fields || {});
    const valida = computed(() => Boolean(mapeo.value.fecha !== null && mapeo.value.fecha !== undefined && mapeo.value.fecha !== -1));

    // La cuenta decide la moneda: el extracto debe venir en esa misma moneda.
    const moneda = computed(() => {
        const codigo = (props.currency || 'PEN').toUpperCase();

        if (codigo === 'PEN') return { nombre: 'Soles (S/)', corto: 'soles' };
        if (codigo === 'USD') return { nombre: 'Dólares (US$)', corto: 'dólares' };

        return { nombre: codigo, corto: codigo };
    });

    const money = (value) => {
        try {
            return new Intl.NumberFormat('es-PE', { style: 'currency', currency: props.currency || 'PEN' }).format(value || 0);
        } catch (e) {
            return `${props.currency} ${Number(value || 0).toFixed(2)}`;
        }
    };

    // Campos que el usuario puede mapear. Si mañana el backend agrega uno nuevo,
    // basta con sumarlo aquí para que «Volver a leer» no borre lo ya elegido.
    const CAMPOS_MAPEO = ['fecha', 'hora', 'descripcion', 'monto', 'cargo', 'abono', 'referencia', 'operacion', 'saldo'];

    const normalizarMapeo = (value) => {
        const base = {};

        CAMPOS_MAPEO.forEach((field) => {
            base[field] = -1;
        });

        Object.keys(value || {}).forEach((field) => {
            if (!(field in base)) base[field] = -1;
        });

        Object.keys(base).forEach((field) => {
            const current = value?.[field];

            base[field] = current === null || current === undefined || current === '' ? -1 : Number(current);
        });

        return base;
    };

    const valoresMapeo = () => {
        const limpio = {};

        Object.entries(mapeo.value).forEach(([field, value]) => {
            limpio[field] = value === -1 || value === null || value === '' ? null : Number(value);
        });

        return limpio;
    };

    const mensajeError = (e) => e?.response?.data?.message
        || e?.response?.data?.errors?.file?.[0]
        || 'No se pudo procesar el archivo. Revisa el formato e inténtalo otra vez.';

    const reiniciar = () => {
        paso.value = 1;
        archivo.value = null;
        lectura.value = null;
        mapeo.value = normalizarMapeo(null);
        resultado.value = null;
        error.value = '';

        if (inputArchivo.value) {
            inputArchivo.value.value = '';
        }
    };

    watch(
        () => props.show,
        (visible) => {
            if (visible) {
                reiniciar();
                corte.value = props.until || new Date().toISOString().slice(0, 10);
            }
        }
    );

    const cerrar = () => {
        if (cargando.value) return;

        emit('close');
    };

    const elegirArchivo = (event) => {
        archivo.value = event.target.files?.[0] || null;
        error.value = '';

        // Con archivo nuevo se vuelve a leer desde cero.
        if (archivo.value) {
            lectura.value = null;
            paso.value = 1;
        }
    };

    const enviar = async (conMapeo) => {
        if (!archivo.value) {
            error.value = 'Elige el extracto antes de continuar.';

            return;
        }

        if (!props.accountId) {
            error.value = 'Selecciona primero la cuenta a conciliar.';

            return;
        }

        const data = new FormData();
        data.append('file', archivo.value);
        data.append('treasury_account_id', props.accountId);

        if (conMapeo) {
            data.append('mapping', JSON.stringify(valoresMapeo()));
        }

        cargando.value = true;
        error.value = '';

        try {
            const response = await window.axios.post(route('treasury_reconciliation_import_preview'), data);
            lectura.value = response.data;
            mapeo.value = normalizarMapeo(response.data.mapping);

            if (!conMapeo) {
                paso.value = 2;
            }
        } catch (e) {
            error.value = mensajeError(e);
        } finally {
            cargando.value = false;
        }
    };

    const aplicar = async () => {
        if (!valida.value) {
            error.value = 'Indica qué columna tiene la fecha.';

            return;
        }

        cargando.value = true;
        error.value = '';

        try {
            const { data } = await window.axios.post(route('treasury_reconciliation_import_confirm'), {
                token: lectura.value.token,
                file_name: lectura.value.file_name,
                treasury_account_id: props.accountId,
                until: corte.value,
                mapping: valoresMapeo(),
            });

            resultado.value = data.result;
            paso.value = 3;
            emit('applied', data.result);
        } catch (e) {
            error.value = mensajeError(e);
        } finally {
            cargando.value = false;
        }
    };
</script>

<template>
    <ModalMedium :show="show" :on-close="cerrar" :icon="'/img/cuenta-bancaria.png'">
        <template #title>Importar extracto bancario</template>
        <template #message>
            <span>El sistema lee los encabezados y tú confirmas a qué corresponde cada columna</span>
        </template>

        <template #content>
            <div class="space-y-4">
                <!-- Pasos -->
                <div class="flex items-center gap-2 text-xs font-medium">
                    <span :class="paso >= 1 ? 'text-primary' : 'text-gray-400'">1. Archivo</span>
                    <span class="text-gray-300">—</span>
                    <span :class="paso >= 2 ? 'text-primary' : 'text-gray-400'">2. Revisar columnas</span>
                    <span class="text-gray-300">—</span>
                    <span :class="paso >= 3 ? 'text-primary' : 'text-gray-400'">3. Resultado</span>
                </div>

                <div v-if="error" class="rounded-lg border border-danger/40 bg-danger/10 px-3 py-2 text-sm text-danger">
                    <font-awesome-icon :icon="faTriangleExclamation" class="mr-1" /> {{ error }}
                </div>

                <!-- Paso 1: archivo -->
                <div v-if="paso === 1" class="space-y-4">
                    <div class="flex items-start gap-2 rounded-lg border border-info/40 bg-info/5 px-3 py-2 text-xs text-gray-600 dark:text-gray-300">
                        <font-awesome-icon :icon="faCircleInfo" class="mt-0.5 text-info" />
                        <p>
                            La cuenta seleccionada es en <span class="font-semibold">{{ moneda.nombre }}</span>. Confirma que
                            el extracto que vas a importar también esté en {{ moneda.corto }}: los montos se registran en la
                            moneda de la cuenta.
                        </p>
                    </div>

                    <div>
                        <InputLabel value="Extracto del banco *" class="mb-1.5" />
                        <input
                            ref="inputArchivo"
                            type="file"
                            accept=".csv,.txt,.xlsx,.xls,.ofx,.qfx"
                            class="hidden"
                            @change="elegirArchivo"
                        />
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <button type="button" class="btn btn-outline-primary" @click="inputArchivo?.click()">
                                <font-awesome-icon :icon="faFileArrowUp" class="mr-1" /> Elegir archivo
                            </button>
                            <span class="truncate text-sm text-gray-500 dark:text-gray-400">
                                {{ archivo?.name || 'CSV, TXT, Excel (.xlsx) u OFX · hasta 10 MB' }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Conciliar hasta el *" class="mb-1.5" />
                        <input v-model="corte" type="date" class="form-input w-full sm:w-48" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Las líneas del extracto se comparan con los movimientos del libro hasta esta fecha.
                        </p>
                    </div>
                </div>

                <!-- Paso 2: mapeo -->
                <div v-if="paso === 2 && lectura" class="space-y-4">
                    <div class="flex flex-col gap-1 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600 sm:flex-row sm:items-center sm:justify-between dark:bg-gray-800/60 dark:text-gray-300">
                        <span>
                            {{ lectura.file_name }} · {{ lectura.format.toUpperCase() }}
                            <template v-if="lectura.delimiter"> · separador «{{ lectura.delimiter === '\t' ? 'TAB' : lectura.delimiter }}»</template>
                            · {{ lectura.total }} fila(s) · Cuenta en {{ moneda.corto }}
                        </span>
                        <span v-if="lectura.invalid" class="font-medium text-warning">
                            {{ lectura.invalid }} fila(s) sin fecha o monto reconocido
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <div v-for="(label, field) in campos" :key="field" class="flex items-center gap-2">
                            <label class="w-32 shrink-0 text-xs font-medium text-gray-600 dark:text-gray-300">{{ label }}</label>
                            <select v-model.number="mapeo[field]" class="form-select flex-1 text-sm">
                                <option :value="-1">— No usar —</option>
                                <option v-for="(header, index) in headers" :key="index" :value="index">
                                    {{ header || `Columna ${index + 1}` }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-start gap-2 rounded-lg border border-info/40 bg-info/5 px-3 py-2 text-xs text-gray-600 dark:text-gray-300">
                        <font-awesome-icon :icon="faWandMagicSparkles" class="mt-0.5 text-info" />
                        <p>
                            Si hay columnas de cargo y abono, úsalas en vez de «Monto con signo». Corrige lo que haga falta y
                            pulsa «Volver a leer» para ver el resultado antes de aplicar.
                        </p>
                    </div>

                    <div class="max-h-56 overflow-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full text-xs">
                            <thead class="sticky top-0 bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="text-left">Fecha</th>
                                    <th class="text-left">Descripción</th>
                                    <th class="text-left">Referencia</th>
                                    <th class="text-left">N° operación</th>
                                    <th class="text-right">Monto</th>
                                    <th class="text-left">Tipo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, index) in filas" :key="index" :class="row.error ? 'bg-warning/5' : ''">
                                    <td class="whitespace-nowrap">{{ row.date || '—' }}</td>
                                    <td class="max-w-[220px] truncate">{{ row.description || '—' }}</td>
                                    <td class="max-w-[100px] truncate text-gray-500">{{ row.reference || '—' }}</td>
                                    <td class="whitespace-nowrap text-gray-500">{{ row.operation_number || '—' }}</td>
                                    <td class="text-right font-medium">{{ money(row.amount) }}</td>
                                    <td>
                                        <span v-if="row.error" class="text-warning">{{ row.error }}</span>
                                        <span v-else :class="row.type === 'income' ? 'text-success' : 'text-danger'">
                                            {{ row.type === 'income' ? 'Ingreso' : 'Egreso' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Paso 3: resultado -->
                <div v-if="paso === 3 && resultado" class="space-y-3">
                    <div class="flex items-center gap-2 rounded-lg border border-success/40 bg-success/10 px-3 py-2 text-sm text-success">
                        <font-awesome-icon :icon="faCircleCheck" />
                        Extracto aplicado a la conciliación.
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <p class="text-xs text-gray-500">Líneas leídas</p>
                            <p class="text-xl font-bold">{{ resultado.rows_total }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <p class="text-xs text-gray-500">Emparejadas</p>
                            <p class="text-xl font-bold text-success">{{ resultado.rows_matched }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <p class="text-xs text-gray-500">Partidas nuevas</p>
                            <p class="text-xl font-bold text-warning">{{ resultado.rows_created }}</p>
                        </div>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Las líneas que coinciden en monto y fecha quedan aceptadas. Las que no tienen par aparecen en la
                        comparación: desde ahí puedes registrarlas como movimiento del libro. Los movimientos del libro
                        que el extracto no trae se listan aparte para revisarlos.
                    </p>
                </div>
            </div>
        </template>

        <template #buttons>
            <div class="flex flex-wrap items-center gap-2">
                <button v-if="paso === 2" type="button" class="btn btn-outline-primary" :disabled="cargando" @click="paso = 1">
                    <font-awesome-icon :icon="faArrowLeft" class="mr-1" /> Atrás
                </button>
                <button v-if="paso === 2" type="button" class="btn btn-outline-primary" :disabled="cargando" @click="enviar(true)">
                    <font-awesome-icon :icon="faRotate" class="mr-1" /> Volver a leer
                </button>
                <PrimaryButton v-if="paso === 1" :disabled="cargando || !archivo" @click="enviar(false)">
                    {{ cargando ? 'Leyendo…' : 'Leer extracto' }}
                </PrimaryButton>
                <PrimaryButton v-if="paso === 2" :disabled="cargando" @click="aplicar">
                    {{ cargando ? 'Aplicando…' : 'Aplicar importación' }}
                    <font-awesome-icon :icon="faArrowRight" class="ml-1" />
                </PrimaryButton>
                <button v-if="paso === 3" type="button" class="btn btn-primary" @click="cerrar">Cerrar</button>
            </div>
        </template>
    </ModalMedium>
</template>
