<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, Link, router, usePage } from '@inertiajs/vue3';
    import { computed, ref, watch } from 'vue';

    const props = defineProps({
        tipo: { type: String, default: 'ventas' },
        period: { type: String, default: null },
        rows: { type: Array, default: () => [] },
        rowsTotal: { type: Number, default: 0 },
        previewLimit: { type: Number, default: null },
        totals: { type: Object, default: () => ({}) },
        observations: { type: Array, default: () => [] },
        errors: { type: Array, default: () => [] },
        canDownload: { type: Boolean, default: false },
        fileName: { type: String, default: null },
        ready: { type: Boolean, default: false },
        companyRuc: { type: String, default: '' },
        mode: { type: String, default: 'txt' },
        credentialsReady: { type: Boolean, default: false },
    });

    const page = usePage();

    const esVentas = computed(() => props.tipo === 'ventas');

    const nombre = computed(() => (esVentas.value ? 'Registro de Ventas' : 'Registro de Compras'));

    const titulo = computed(() =>
        esVentas.value ? 'Registro de Ventas e Ingresos Electrónico' : 'Registro de Compras Electrónico'
    );

    const descripcion = computed(() =>
        esVentas.value
            ? 'Archivo plano del período para reemplazar la propuesta de SUNAT en SIRE (Anexo N.° 3). Incluye facturas, boletas y notas; los comprobantes anulados o rechazados se anotan en cero.'
            : 'Archivo plano del período para reemplazar la propuesta de SUNAT en SIRE (Anexo N.° 11). Incluye las compras y notas registradas; los comprobantes anulados no se anotan.'
    );

    const rutas = computed(() =>
        esVentas.value
            ? {
                index: 'sire_rvie_ventas_index',
                txt: 'sire_rvie_ventas_txt',
                zip: 'sire_rvie_ventas_zip',
                propuesta: 'sire_rvie_ventas_propuesta',
                comparar: 'sire_rvie_ventas_comparar',
                reemplazar: 'sire_rvie_ventas_reemplazar',
                aceptar: 'sire_rvie_ventas_aceptar',
                estado: 'sire_rvie_ventas_estado',
            }
            : {
                index: 'sire_rce_compras_index',
                txt: 'sire_rce_compras_txt',
                zip: 'sire_rce_compras_zip',
                propuesta: 'sire_rce_compras_propuesta',
                comparar: 'sire_rce_compras_comparar',
                reemplazar: 'sire_rce_compras_reemplazar',
                aceptar: 'sire_rce_compras_aceptar',
                estado: 'sire_rce_compras_estado',
            }
    );

    const form = ref({ period: props.period });

    const tab = ref(props.mode === 'api' ? 'api' : 'txt');

    const puedeConfigurar = computed(() => (page.props.auth?.permissions || []).includes('sire_api'));

    const resultado = ref(null);
    const consultando = ref(false);

    const money = (value) =>
        new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(value || 0);

    const changePeriod = () => {
        router.get(route(rutas.value.index), { period: form.value.period }, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const download = (format) => {
        if (! props.ready) {
            window.alertaSistema(
                'Falta el RUC de la empresa',
                'Registra el RUC en la configuración de la empresa antes de generar el registro.',
                'warning'
            );
            return;
        }

        if (! props.canDownload) {
            window.alertaSistema(
                'El archivo tiene errores',
                'Corrige los ' + props.errors.length + ' punto(s) marcados abajo antes de descargar el registro.',
                'error'
            );
            return;
        }

        window.location.href = route(format === 'zip' ? rutas.value.zip : rutas.value.txt, { period: props.period });
    };

    const descargarPropuesta = async () => {
        consultando.value = true;
        resultado.value = null;

        try {
            const { data } = await axios.post(route(rutas.value.propuesta), { period: props.period });

            resultado.value = data;

            if (data.descargado) {
                window.alertaSistema('Propuesta descargada', data.message, 'success');
            } else {
                window.alertaSistema('SUNAT sigue procesando', data.message, 'warning');
            }
        } catch (error) {
            const mensaje =
                error?.response?.data?.message || 'No se pudo descargar la propuesta de SUNAT. Revisa la bitácora.';

            if (error?.response?.data?.ticket) {
                resultado.value = {
                    descargado: false,
                    ticket: error.response.data.ticket,
                    estado_etiqueta: 'Error',
                    message: mensaje,
                };
            }

            window.alertaSistema('No se pudo descargar', mensaje, 'error');
        } finally {
            consultando.value = false;
        }
    };

    const comparacion = ref(null);
    const reemplazando = ref(false);
    const aceptando = ref(false);
    const ultimoEnvio = ref(null);

    // El reemplazo solo se habilita con el registro sin errores y la
    // comparación ya hecha (o pendiente de diferencias, que se confirman aparte).
    const puedeReemplazar = computed(
        () =>
            props.credentialsReady &&
            props.canDownload &&
            comparacion.value !== null &&
            !comparacion.value.bloqueado &&
            !comparacion.value.pendiente
    );

    const comparar = async () => {
        consultando.value = true;

        try {
            const { data } = await axios.post(route(rutas.value.comparar), { period: props.period });
            comparacion.value = data;
            ultimoEnvio.value = null;

            if (data.bloqueado) {
                window.alertaSistema('Corrige el registro', data.message, 'error', { duration: 0 });
            } else if (data.pendiente) {
                window.alertaSistema('SUNAT sigue procesando', data.message, 'warning');
            } else if (data.ok) {
                window.alertaSistema('Coincide con SUNAT', data.message, 'success');
            } else {
                window.alertaSistema('Hay diferencias', data.message, 'warning', { duration: 0 });
            }
        } catch (error) {
            window.alertaSistema(
                'No se pudo comparar',
                error?.response?.data?.message || 'Revisa la bitácora del SIRE e inténtalo de nuevo.',
                'error',
                { duration: 0 }
            );
        } finally {
            consultando.value = false;
        }
    };

    const reemplazar = async (conDiferencias = false) => {
        const confirmacion = await window.alertaSistema(
            conDiferencias ? 'Enviar con diferencias' : 'Reemplazar la propuesta de SUNAT',
            conDiferencias
                ? 'El registro del ERP no coincide con la propuesta de SUNAT. Se enviará igual y reemplazará lo que SUNAT tiene publicado en el período. Esta acción no se puede deshacer.'
                : 'Se enviará el registro del período y SUNAT reemplazará su propuesta. El SIRE no tiene ambiente de pruebas: esta acción afecta el registro real.',
            'warning',
            {
                duration: 0,
                showCancelButton: true,
                confirmButtonText: conDiferencias ? 'Enviar igual' : 'Reemplazar',
                cancelButtonText: 'Cancelar',
            }
        );

        if (!confirmacion?.isConfirmed) return;

        reemplazando.value = true;

        try {
            const { data } = await axios.post(route(rutas.value.reemplazar), {
                period: props.period,
                aceptar_diferencias: conDiferencias,
            });

            ultimoEnvio.value = data;

            window.alertaSistema(
                data.procesado ? 'Reemplazo terminado' : 'Reemplazo en proceso',
                data.message,
                data.procesado ? 'success' : 'warning',
                { duration: 0 }
            );
        } catch (error) {
            const data = error?.response?.data;

            // El servidor devuelve la comparación cuando bloquea el envío por
            // diferencias: se muestran antes de ofrecer el envío igual.
            if (data?.comparacion) {
                comparacion.value = data.comparacion;
            }

            window.alertaSistema(
                'No se pudo reemplazar',
                data?.message || 'Revisa la bitácora del SIRE e inténtalo de nuevo.',
                'error',
                { duration: 0 }
            );
        } finally {
            reemplazando.value = false;
        }
    };

    const aceptar = async () => {
        const confirmacion = await window.alertaSistema(
            'Aceptar la propuesta de SUNAT',
            'Se aceptará la propuesta que SUNAT tiene publicada para el período. El SIRE no tiene ambiente de pruebas: esta acción afecta el registro real y no se puede deshacer.',
            'warning',
            {
                duration: 0,
                showCancelButton: true,
                confirmButtonText: 'Aceptar propuesta',
                cancelButtonText: 'Cancelar',
            }
        );

        if (!confirmacion?.isConfirmed) return;

        aceptando.value = true;

        try {
            const { data } = await axios.post(route(rutas.value.aceptar), { period: props.period });

            ultimoEnvio.value = data;

            window.alertaSistema(
                data.procesado ? 'Propuesta aceptada' : 'Aceptación en proceso',
                data.message,
                data.procesado ? 'success' : 'warning',
                { duration: 0 }
            );
        } catch (error) {
            window.alertaSistema(
                'No se pudo aceptar',
                error?.response?.data?.message || 'Revisa la bitácora del SIRE e inténtalo de nuevo.',
                'error',
                { duration: 0 }
            );
        } finally {
            aceptando.value = false;
        }
    };

    watch(
        () => page.props.flash?.error,
        (mensaje) => {
            if (mensaje) {
                window.alertaSistema('Descarga bloqueada', mensaje, 'error');
            }
        }
    );
</script>

<template>
    <AppLayout :title="`SIRE · ${nombre}`">
        <Head :title="`SIRE · ${nombre}`">
            <meta name="aracodito-view" content="new" />
        </Head>

        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Facturación Electrónica</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>SIRE · {{ nombre }}</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div class="panel flex flex-col gap-3 p-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">{{ titulo }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ descripcion }}</p>
                    <p v-if="companyRuc" class="mt-2 text-xs text-gray-400">RUC de la empresa: {{ companyRuc }}</p>
                </div>

                <span
                    class="badge shrink-0"
                    :class="mode === 'api' ? 'bg-primary/10 text-primary' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                >
                    Modo {{ mode === 'api' ? 'API automática' : 'TXT / ZIP' }}
                </span>
            </div>

            <div class="panel flex flex-wrap items-center gap-2 p-2">
                <button
                    type="button"
                    class="btn"
                    :class="tab === 'txt' ? 'btn-primary' : 'btn-outline-primary'"
                    @click="tab = 'txt'"
                >
                    Descarga manual (TXT / ZIP)
                </button>
                <button
                    type="button"
                    class="btn"
                    :class="tab === 'api' ? 'btn-primary' : 'btn-outline-primary'"
                    @click="tab = 'api'"
                >
                    Envío automático (API)
                </button>

                <Link
                    v-if="puedeConfigurar"
                    :href="route('sire_configuracion')"
                    class="btn btn-outline-secondary sm:ml-auto"
                >
                    Configurar SIRE
                </Link>
            </div>

            <div class="panel flex flex-col gap-4 p-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-500 dark:text-gray-400">Período tributario</label>
                        <input v-model="form.period" type="month" class="form-input w-full sm:w-48" />
                    </div>
                    <button type="button" class="btn btn-primary" @click="changePeriod">Consultar</button>
                </div>

                <div v-if="tab === 'txt'" class="flex flex-col gap-2 sm:flex-row">
                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        :disabled="!canDownload"
                        :title="canDownload ? 'Descargar el TXT del período' : 'Corrige los errores del archivo para descargar'"
                        :class="{ 'cursor-not-allowed opacity-50': !canDownload }"
                        @click="download('txt')"
                    >
                        Descargar TXT
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        :disabled="!canDownload"
                        :title="canDownload ? 'Descargar el ZIP del período' : 'Corrige los errores del archivo para descargar'"
                        :class="{ 'cursor-not-allowed opacity-50': !canDownload }"
                        @click="download('zip')"
                    >
                        Descargar ZIP
                    </button>
                </div>

                <div v-else class="flex flex-col items-start gap-2 sm:flex-row sm:items-center">
                    <button
                        type="button"
                        class="btn btn-primary"
                        :disabled="!credentialsReady || consultando"
                        :class="{ 'cursor-not-allowed opacity-50': !credentialsReady || consultando }"
                        @click="descargarPropuesta"
                    >
                        {{ consultando ? 'Consultando SUNAT…' : 'Descargar propuesta de SUNAT' }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        :disabled="!credentialsReady || consultando"
                        :class="{ 'cursor-not-allowed opacity-50': !credentialsReady || consultando }"
                        :title="credentialsReady ? 'Compara el registro del ERP con la propuesta de SUNAT' : 'Carga las credenciales para comparar'"
                        @click="comparar"
                    >
                        Comparar con el ERP
                    </button>
                </div>
            </div>

            <div v-if="errors.length" class="panel border-l-4 border-danger p-5">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white">El archivo no se puede descargar</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Estos puntos harían que SUNAT rechace el registro. Corrige los comprobantes y vuelve a consultar el período.
                </p>
                <ul class="mt-3 max-h-64 space-y-1 overflow-y-auto text-sm text-gray-700 dark:text-gray-300">
                    <li v-for="(item, index) in errors" :key="index" class="flex gap-2">
                        <span class="shrink-0 text-danger">•</span>
                        <span>{{ item }}</span>
                    </li>
                </ul>
            </div>

            <div v-if="tab === 'api'" class="panel p-5">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white">Propuesta de SUNAT del período</h3>

                <template v-if="! credentialsReady">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Para usar el modo API carga las credenciales de SUNAT. Se generan en SOL → Empresas → Credenciales de
                        API SUNAT y se guardan cifradas en el ERP.
                    </p>
                    <Link v-if="puedeConfigurar" :href="route('sire_configuracion')" class="btn btn-primary mt-3">
                        Cargar credenciales
                    </Link>
                    <p v-else class="mt-3 text-xs text-gray-400">
                        Pide a un administrador que cargue las credenciales en Configuración → SIRE.
                    </p>
                </template>

                <p v-else class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Solicita la exportación, espera el ticket de SUNAT y guarda el TXT de la propuesta. Es una consulta de
                    solo lectura.
                </p>

                <div v-if="resultado" class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="badge"
                            :class="resultado.descargado ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'"
                        >
                            {{ resultado.estado_etiqueta || 'En proceso' }}
                        </span>
                        <span class="text-xs text-gray-500">Ticket {{ resultado.ticket }}</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ resultado.message }}</p>
                    <div v-if="resultado.descargado" class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <p class="text-xs uppercase text-gray-400">Archivo</p>
                            <p class="break-all text-gray-700 dark:text-gray-200">{{ resultado.archivo }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-400">Líneas</p>
                            <p class="text-gray-700 dark:text-gray-200">{{ resultado.lineas }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-xs uppercase text-gray-400">Hash SHA-256</p>
                            <p class="break-all font-mono text-xs text-gray-500">{{ resultado.hash }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="credentialsReady" class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white">Presentar el período</h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Compara antes de reemplazar: se envía el registro del ERP y SUNAT lo usa en lugar de su propuesta.
                        El SIRE no tiene ambiente de pruebas, así que cada envío afecta el registro real.
                    </p>

                    <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                        <button
                            v-if="!comparacion || comparacion.ok"
                            type="button"
                            class="btn btn-outline-primary"
                            :disabled="!puedeReemplazar || reemplazando"
                            :class="{ 'cursor-not-allowed opacity-50': !puedeReemplazar || reemplazando }"
                            :title="puedeReemplazar ? 'Envía el registro del período y reemplaza la propuesta de SUNAT' : 'Compara el período para habilitar el reemplazo'"
                            @click="reemplazar(false)"
                        >
                            {{ reemplazando ? 'Enviando…' : 'Reemplazar propuesta' }}
                        </button>
                        <button
                            v-else
                            type="button"
                            class="btn btn-outline-danger"
                            :disabled="reemplazando"
                            :class="{ 'cursor-not-allowed opacity-50': reemplazando }"
                            title="Envía el registro aunque no coincida con la propuesta de SUNAT"
                            @click="reemplazar(true)"
                        >
                            {{ reemplazando ? 'Enviando…' : 'Enviar con diferencias' }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            :disabled="aceptando"
                            :class="{ 'cursor-not-allowed opacity-50': aceptando }"
                            title="Acepta la propuesta que SUNAT tiene publicada para el período"
                            @click="aceptar"
                        >
                            {{ aceptando ? 'Aceptando…' : 'Aceptar propuesta' }}
                        </button>
                    </div>

                    <p v-if="!comparacion" class="mt-2 text-xs text-gray-400">
                        Compara el período para habilitar el reemplazo.
                    </p>
                    <p v-else-if="comparacion.bloqueado" class="mt-2 text-xs text-danger">
                        Corrige los errores del registro antes de presentarlo.
                    </p>
                </div>

                <div v-if="comparacion && !comparacion.bloqueado" class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="badge"
                            :class="comparacion.ok ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'"
                        >
                            {{ comparacion.ok ? 'Coincide con SUNAT' : 'Con diferencias' }}
                        </span>
                        <span v-if="comparacion.archivo_sunat" class="text-xs text-gray-500">
                            Propuesta {{ comparacion.archivo_sunat }}
                        </span>
                    </div>

                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ comparacion.message }}</p>

                    <div v-if="comparacion.resumen" class="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3 xl:grid-cols-6">
                        <div>
                            <p class="text-xs uppercase text-gray-400">ERP</p>
                            <p class="font-semibold text-gray-800 dark:text-white">{{ comparacion.resumen.erp_lineas }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-400">SUNAT</p>
                            <p class="font-semibold text-gray-800 dark:text-white">{{ comparacion.resumen.sunat_lineas }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-400">Iguales</p>
                            <p class="font-semibold text-success">{{ comparacion.resumen.iguales }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-400">Diferencias</p>
                            <p class="font-semibold text-warning">{{ comparacion.resumen.diferencias }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-400">Solo en SUNAT</p>
                            <p class="font-semibold text-danger">{{ comparacion.resumen.faltan_en_erp }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-400">Solo en el ERP</p>
                            <p class="font-semibold text-danger">{{ comparacion.resumen.faltan_en_sunat }}</p>
                        </div>
                    </div>

                    <div v-if="comparacion.faltan_en_erp && comparacion.faltan_en_erp.length" class="mt-4">
                        <h5 class="text-sm font-semibold text-gray-800 dark:text-white">SUNAT tiene y el ERP no</h5>
                        <ul class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-300">
                            <li v-for="item in comparacion.faltan_en_erp" :key="'erp-' + item.clave" class="flex flex-wrap gap-x-2">
                                <span class="font-semibold">{{ item.numero }}</span>
                                <span class="text-xs text-gray-500">{{ item.fecha }}</span>
                                <span class="text-xs text-gray-500">{{ item.nombre }}</span>
                                <span class="text-xs text-gray-500">{{ item.moneda }} {{ item.total }}</span>
                            </li>
                        </ul>
                    </div>

                    <div v-if="comparacion.faltan_en_sunat && comparacion.faltan_en_sunat.length" class="mt-4">
                        <h5 class="text-sm font-semibold text-gray-800 dark:text-white">El ERP tiene y SUNAT no</h5>
                        <ul class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-300">
                            <li v-for="item in comparacion.faltan_en_sunat" :key="'sunat-' + item.clave" class="flex flex-wrap gap-x-2">
                                <span class="font-semibold">{{ item.numero }}</span>
                                <span class="text-xs text-gray-500">{{ item.fecha }}</span>
                                <span class="text-xs text-gray-500">{{ item.nombre }}</span>
                                <span class="text-xs text-gray-500">{{ item.moneda }} {{ item.total }}</span>
                            </li>
                        </ul>
                    </div>

                    <div v-if="comparacion.diferencias && comparacion.diferencias.length" class="mt-4">
                        <h5 class="text-sm font-semibold text-gray-800 dark:text-white">Diferencias de datos</h5>
                        <div class="table-responsive mt-2 max-h-72">
                            <table class="table-hover w-full text-sm">
                                <thead>
                                    <tr class="border-t border-white-light text-left dark:border-dark">
                                        <th class="px-3 py-2">Comprobante</th>
                                        <th class="px-3 py-2">Campo</th>
                                        <th class="px-3 py-2">SUNAT</th>
                                        <th class="px-3 py-2">ERP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(diferencia, index) in comparacion.diferencias"
                                        :key="'dif-' + index"
                                        class="border-t border-white-light dark:border-dark"
                                    >
                                        <td class="px-3 py-2 font-semibold text-gray-800 dark:text-white">{{ diferencia.numero }}</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ diferencia.campo_etiqueta }}</td>
                                        <td class="px-3 py-2 text-gray-500">{{ diferencia.sunat || '—' }}</td>
                                        <td class="px-3 py-2 text-gray-500">{{ diferencia.erp || '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-if="comparacion.truncado" class="mt-2 text-xs text-gray-400">
                            La lista se corta en las primeras diferencias; el conteo del resumen es el total.
                        </p>
                    </div>
                </div>

                <div v-if="ultimoEnvio" class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="badge"
                            :class="ultimoEnvio.procesado ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'"
                        >
                            {{ ultimoEnvio.estado_etiqueta || 'En proceso' }}
                        </span>
                        <span class="text-xs text-gray-500">Ticket {{ ultimoEnvio.ticket }}</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ ultimoEnvio.message }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Comprobantes</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ totals.count || 0 }}</p>
                    <p v-if="esVentas" class="mt-1 text-xs text-gray-400">{{ totals.zeroed || 0 }} anotados en cero</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Base gravada</p>
                    <p class="mt-2 text-2xl font-bold text-primary">{{ money(totals.gravada) }}</p>
                    <p class="mt-1 text-xs text-gray-400">IGV {{ money(totals.igv) }}</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No gravada</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">
                        {{ money(totals.no_gravada ?? ((totals.exonerada || 0) + (totals.inafecta || 0))) }}
                    </p>
                    <p v-if="esVentas" class="mt-1 text-xs text-gray-400">Exportación {{ money(totals.exportacion) }}</p>
                </div>
                <div class="panel bg-gradient-to-br from-primary to-primary-dark p-5 text-white">
                    <p class="text-sm opacity-80">Total del período</p>
                    <p class="mt-2 text-2xl font-bold">{{ money(totals.total) }}</p>
                    <p class="mt-1 break-all text-xs opacity-70">{{ fileName }}</p>
                </div>
            </div>

            <div v-if="observations.length" class="panel border-l-4 border-warning p-5">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white">Observaciones antes de presentar</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    El archivo se genera igual, pero SUNAT puede rechazarlo o mostrarlo como diferencia. Corrige estos puntos si aplican.
                </p>
                <ul class="mt-3 max-h-64 space-y-1 overflow-y-auto text-sm text-gray-600 dark:text-gray-300">
                    <li v-for="(item, index) in observations" :key="index" class="flex gap-2">
                        <span class="text-warning">•</span>
                        <span>{{ item }}</span>
                    </li>
                </ul>
            </div>

            <div class="panel overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 p-5">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">Vista previa del registro</h3>
                    <span class="text-xs text-gray-400">
                        Mostrando {{ rows.length }} de {{ rowsTotal }} líneas · {{ esVentas ? 33 : 37 }} campos por línea
                    </span>
                </div>

                <p v-if="previewLimit && rowsTotal > rows.length" class="px-5 pb-3 text-xs text-gray-500 dark:text-gray-400">
                    La vista previa muestra las primeras {{ previewLimit }} líneas. El archivo TXT y el ZIP incluyen las
                    {{ rowsTotal }} del período.
                </p>

                <div class="table-responsive">
                    <table class="table-hover w-full text-sm">
                        <thead>
                            <tr class="border-t border-white-light text-left dark:border-dark">
                                <th class="px-5 py-3">Fecha</th>
                                <th class="px-5 py-3">Comprobante</th>
                                <th class="px-5 py-3">{{ esVentas ? 'Cliente' : 'Proveedor' }}</th>
                                <th class="px-5 py-3">Moneda</th>
                                <th class="px-5 py-3 text-right">Total</th>
                                <th class="px-5 py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in rows" :key="row.id" class="border-t border-white-light dark:border-dark">
                                <td class="px-5 py-3">{{ row.fecha }}</td>
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-gray-800 dark:text-white">{{ row.tipo_etiqueta }}</p>
                                    <p class="text-xs text-gray-500">{{ row.numero }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="text-gray-800 dark:text-white">{{ row.cliente || '—' }}</p>
                                    <p class="text-xs text-gray-500">{{ row.cliente_documento }}</p>
                                </td>
                                <td class="px-5 py-3">{{ row.moneda }}</td>
                                <td class="px-5 py-3 text-right">{{ money(row.total) }}</td>
                                <td class="px-5 py-3">
                                    <span v-if="row.en_cero" class="badge bg-danger/10 text-danger">En cero</span>
                                    <span v-else class="badge bg-success/10 text-success">Incluido</span>
                                    <p v-for="(note, index) in row.observaciones" :key="index" class="mt-1 text-xs text-warning">
                                        {{ note }}
                                    </p>
                                </td>
                            </tr>
                            <tr v-if="! rows.length">
                                <td colspan="6" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No hay comprobantes en el período seleccionado. Si no hubo operaciones, igual debes presentar el
                                    archivo sin información.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
