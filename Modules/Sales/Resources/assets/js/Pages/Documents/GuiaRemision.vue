<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Pagination from '@/Components/Pagination.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { router } from '@inertiajs/vue3';
    import { reactive, ref } from 'vue';
    import Swal from 'sweetalert2';

    const props = defineProps({
        guias: { type: Object, default: () => ({}) },
        filters: { type: Object, default: () => ({}) },
        motivos: { type: Object, default: () => ({}) },
        modalidades: { type: Object, default: () => ({}) },
    });

    const search = ref(props.filters.search ?? '');
    const statusFilter = ref(props.filters.status ?? '');
    const showModal = ref(false);
    const saving = ref(false);

    const emptyDetail = () => ({ codigo: '', descripcion: '', unidad: 'NIU', cantidad: 1, cod_prod_sunat: '' });

    const form = reactive({
        serie: 'T001',
        correlativo: '',
        fecha_emision: new Date().toISOString().slice(0, 16),
        cod_traslado: '01',
        des_traslado: '',
        mod_traslado: '02',
        peso_total: 1,
        und_peso_total: 'KGM',
        num_bultos: 1,
        partida_ubigeo: '',
        partida_direccion: '',
        llegada_ubigeo: '',
        llegada_direccion: '',
        destinatario_tipo_doc: '6',
        destinatario_num_doc: '',
        destinatario_razon_social: '',
        destinatario_email: '',
        transportista_tipo_doc: '6',
        transportista_num_doc: '',
        transportista_razon_social: '',
        transportista_nro_mtc: '',
        vehiculo_placa: '',
        conductor_tipo_doc: '1',
        conductor_num_doc: '',
        conductor_nombres: '',
        conductor_apellidos: '',
        conductor_licencia: '',
        doc_relacionado_tipo: '01',
        doc_relacionado_numero: '',
        observacion: '',
        details: [emptyDetail()],
    });

    const errors = ref({});

    const statusClass = (status) => ({
        aceptado: 'bg-green-100 text-green-700',
        enviado: 'bg-blue-100 text-blue-700',
        contingencia: 'bg-yellow-100 text-yellow-700',
        rechazado: 'bg-red-100 text-red-700',
        registrado: 'bg-gray-100 text-gray-700',
    }[status] ?? 'bg-gray-100 text-gray-700');

    const searchGuias = () => {
        router.get(route('guias_remision_list'), { search: search.value || undefined, status: statusFilter.value || undefined }, { preserveState: true });
    };

    const addDetail = () => form.details.push(emptyDetail());

    const removeDetail = (index) => {
        if (form.details.length > 1) {
            form.details.splice(index, 1);
        }
    };

    const resetForm = () => {
        form.details = [emptyDetail()];
        errors.value = {};
    };

    const save = () => {
        saving.value = true;
        errors.value = {};

        axios.post(route('guias_remision_store'), form)
            .then((res) => {
                saving.value = false;

                if (res.data.success) {
                    showModal.value = false;
                    resetForm();
                    Swal.fire({
                        title: res.data.queued ? 'Guía registrada en contingencia' : 'Guía generada',
                        text: res.data.message ?? 'La guía se registró correctamente.',
                        icon: res.data.queued ? 'warning' : 'success',
                    });
                    router.reload();
                } else {
                    Swal.fire({ title: 'SUNAT rechazó la guía', text: res.data.message ?? '', icon: 'error' });
                }
            })
            .catch((error) => {
                saving.value = false;
                errors.value = error.response?.data?.errors ?? {};

                const firstError = Object.values(errors.value)[0];
                Swal.fire({
                    title: 'Revisa los datos',
                    text: Array.isArray(firstError) ? firstError[0] : (error.response?.data?.message ?? 'No se pudo registrar la guía.'),
                    icon: 'warning',
                });
            });
    };

    const send = (guia) => {
        Swal.fire({
            title: 'Enviar guía a SUNAT',
            text: `${guia.serie}-${guia.correlativo}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Enviar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (!result.isConfirmed) return;

            axios.get(route('guias_remision_send', guia.id)).then((res) => {
                Swal.fire({
                    title: res.data.success ? 'Envío procesado' : 'Envío rechazado',
                    text: res.data.message ?? '',
                    icon: res.data.success ? 'success' : 'error',
                });
                router.reload();
            }).catch((error) => {
                Swal.fire({ title: 'Error', text: error.response?.data?.message ?? 'No se pudo enviar la guía.', icon: 'error' });
            });
        });
    };

    const check = (guia) => {
        axios.get(route('guias_remision_check', guia.id)).then((res) => {
            Swal.fire({
                title: 'Consulta de ticket',
                text: res.data.message ?? '',
                icon: res.data.success ? 'success' : 'info',
            });
            router.reload();
        });
    };

    const download = (guia, type) => {
        window.open(route('guias_remision_download', [guia.id, type]), '_blank');
    };

    const destroy = (guia) => {
        Swal.fire({
            title: '¿Eliminar guía?',
            text: `${guia.serie}-${guia.correlativo}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (!result.isConfirmed) return;

            axios.get(route('guias_remision_destroy', guia.id)).then((res) => {
                Swal.fire({ title: 'Guía eliminada', text: res.data.message ?? '', icon: 'success' });
                router.reload();
            }).catch((error) => {
                Swal.fire({ title: 'No se puede eliminar', text: error.response?.data?.message ?? '', icon: 'error' });
            });
        });
    };
</script>

<template>
    <AppLayout title="Guías de Remisión">
        <div class="p-4">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800 dark:text-white">Guías de Remisión Electrónicas</h1>
                    <p class="text-sm text-gray-500">Traslado de bienes por venta ordinaria (remitente).</p>
                </div>
                <PrimaryButton @click="showModal = true">Nueva guía</PrimaryButton>
            </div>

            <div class="flex flex-wrap items-end gap-3 mb-4">
                <div>
                    <label class="text-xs text-gray-500">Buscar</label>
                    <input v-model="search" @keyup.enter="searchGuias" type="text" placeholder="Serie, correlativo o destinatario"
                        class="form-input w-72" />
                </div>
                <div>
                    <label class="text-xs text-gray-500">Estado</label>
                    <select v-model="statusFilter" @change="searchGuias" class="form-select w-44">
                        <option value="">Todos</option>
                        <option value="registrado">Registrado</option>
                        <option value="enviado">Enviado</option>
                        <option value="aceptado">Aceptado</option>
                        <option value="rechazado">Rechazado</option>
                        <option value="contingencia">Contingencia</option>
                    </select>
                </div>
                <PrimaryButton @click="searchGuias">Filtrar</PrimaryButton>
            </div>

            <div class="overflow-x-auto bg-white dark:bg-[#0e1726] rounded-md shadow">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b dark:border-gray-700">
                            <th class="p-3">Guía</th>
                            <th class="p-3">Fecha</th>
                            <th class="p-3">Destinatario</th>
                            <th class="p-3">Motivo</th>
                            <th class="p-3">Bultos / Peso</th>
                            <th class="p-3">Estado</th>
                            <th class="p-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="guia in guias.data" :key="guia.id" class="border-b dark:border-gray-700">
                            <td class="p-3 font-medium">{{ guia.serie }}-{{ guia.correlativo }}</td>
                            <td class="p-3">{{ String(guia.fecha_emision).slice(0, 10) }}</td>
                            <td class="p-3">
                                <div>{{ guia.destinatario_razon_social }}</div>
                                <div class="text-xs text-gray-500">{{ guia.destinatario_num_doc }}</div>
                            </td>
                            <td class="p-3">
                                <div>{{ motivos[guia.cod_traslado] ?? guia.cod_traslado }}</div>
                                <div class="text-xs text-gray-500">{{ modalidades[guia.mod_traslado] ?? '' }}</div>
                            </td>
                            <td class="p-3">{{ guia.num_bultos ?? '-' }} / {{ guia.peso_total }} KGM</td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded text-xs capitalize" :class="statusClass(guia.status)">{{ guia.status }}</span>
                                <div v-if="guia.ticket" class="text-xs text-gray-500 mt-1">Ticket: {{ guia.ticket }}</div>
                            </td>
                            <td class="p-3 text-right space-x-2 whitespace-nowrap">
                                <button v-if="guia.status !== 'aceptado'" class="text-blue-600" @click="send(guia)">Enviar</button>
                                <button v-if="guia.ticket" class="text-indigo-600" @click="check(guia)">Consultar</button>
                                <button v-if="guia.xml" class="text-gray-600" @click="download(guia, 'xml')">XML</button>
                                <button v-if="guia.cdr" class="text-gray-600" @click="download(guia, 'cdr')">CDR</button>
                                <button v-if="guia.status !== 'aceptado'" class="text-red-600" @click="destroy(guia)">Eliminar</button>
                            </td>
                        </tr>
                        <tr v-if="!guias.data || guias.data.length === 0">
                            <td colspan="7" class="p-6 text-center text-gray-500">Todavía no hay guías de remisión registradas.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <Pagination :links="guias.links" />
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-start justify-center bg-black/50 overflow-y-auto p-4">
            <div class="bg-white dark:bg-[#0e1726] rounded-md w-full max-w-5xl p-5 my-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">Nueva guía de remisión (remitente)</h2>
                    <button class="text-gray-500" @click="showModal = false">✕</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="text-xs">Serie</label>
                        <input v-model="form.serie" class="form-input w-full" />
                        <p v-if="errors.serie" class="text-xs text-red-600">{{ errors.serie[0] }}</p>
                    </div>
                    <div>
                        <label class="text-xs">Correlativo (automático si se deja vacío)</label>
                        <input v-model="form.correlativo" class="form-input w-full" />
                    </div>
                    <div>
                        <label class="text-xs">Fecha de emisión</label>
                        <input v-model="form.fecha_emision" type="datetime-local" class="form-input w-full" />
                        <p v-if="errors.fecha_emision" class="text-xs text-red-600">{{ errors.fecha_emision[0] }}</p>
                    </div>
                    <div>
                        <label class="text-xs">Modalidad</label>
                        <select v-model="form.mod_traslado" class="form-select w-full">
                            <option v-for="(label, code) in modalidades" :key="code" :value="code">{{ code }} - {{ label }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs">Motivo de traslado</label>
                        <select v-model="form.cod_traslado" class="form-select w-full">
                            <option v-for="(label, code) in motivos" :key="code" :value="code">{{ code }} - {{ label }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs">Descripción del motivo (opcional)</label>
                        <input v-model="form.des_traslado" class="form-input w-full" />
                    </div>
                </div>

                <h3 class="mt-5 mb-2 font-semibold">Puntos de partida y llegada</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="text-xs">Ubigeo partida</label>
                        <input v-model="form.partida_ubigeo" maxlength="6" class="form-input w-full" />
                        <p v-if="errors.partida_ubigeo" class="text-xs text-red-600">{{ errors.partida_ubigeo[0] }}</p>
                    </div>
                    <div class="md:col-span-3">
                        <label class="text-xs">Dirección partida</label>
                        <input v-model="form.partida_direccion" class="form-input w-full" />
                    </div>
                    <div>
                        <label class="text-xs">Ubigeo llegada</label>
                        <input v-model="form.llegada_ubigeo" maxlength="6" class="form-input w-full" />
                        <p v-if="errors.llegada_ubigeo" class="text-xs text-red-600">{{ errors.llegada_ubigeo[0] }}</p>
                    </div>
                    <div class="md:col-span-3">
                        <label class="text-xs">Dirección llegada</label>
                        <input v-model="form.llegada_direccion" class="form-input w-full" />
                    </div>
                    <div>
                        <label class="text-xs">Peso total</label>
                        <input v-model="form.peso_total" type="number" step="0.001" class="form-input w-full" />
                    </div>
                    <div>
                        <label class="text-xs">Bultos</label>
                        <input v-model="form.num_bultos" type="number" min="1" class="form-input w-full" />
                    </div>
                    <div>
                        <label class="text-xs">Unidad de peso</label>
                        <select v-model="form.und_peso_total" class="form-select w-full">
                            <option value="KGM">KGM</option>
                            <option value="TNE">TNE</option>
                        </select>
                    </div>
                </div>

                <h3 class="mt-5 mb-2 font-semibold">Destinatario</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="text-xs">Tipo doc.</label>
                        <select v-model="form.destinatario_tipo_doc" class="form-select w-full">
                            <option value="6">RUC</option>
                            <option value="1">DNI</option>
                            <option value="4">Carné de extranjería</option>
                            <option value="0">Doc. tributario no domiciliado</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs">Número</label>
                        <input v-model="form.destinatario_num_doc" class="form-input w-full" />
                        <p v-if="errors.destinatario_num_doc" class="text-xs text-red-600">{{ errors.destinatario_num_doc[0] }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs">Razón social</label>
                        <input v-model="form.destinatario_razon_social" class="form-input w-full" />
                        <p v-if="errors.destinatario_razon_social" class="text-xs text-red-600">{{ errors.destinatario_razon_social[0] }}</p>
                    </div>
                </div>

                <h3 class="mt-5 mb-2 font-semibold">Transportista, vehículo y conductor</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="text-xs">Tipo doc. transportista</label>
                        <select v-model="form.transportista_tipo_doc" class="form-select w-full">
                            <option value="6">RUC</option>
                            <option value="1">DNI</option>
                            <option value="4">Carné de extranjería</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs">Documento</label>
                        <input v-model="form.transportista_num_doc" class="form-input w-full" />
                        <p v-if="errors.transportista_num_doc" class="text-xs text-red-600">{{ errors.transportista_num_doc[0] }}</p>
                    </div>
                    <div>
                        <label class="text-xs">Razón social</label>
                        <input v-model="form.transportista_razon_social" class="form-input w-full" />
                    </div>
                    <div>
                        <label class="text-xs">Registro MTC</label>
                        <input v-model="form.transportista_nro_mtc" class="form-input w-full" />
                    </div>
                    <div>
                        <label class="text-xs">Placa</label>
                        <input v-model="form.vehiculo_placa" class="form-input w-full" />
                        <p v-if="errors.vehiculo_placa" class="text-xs text-red-600">{{ errors.vehiculo_placa[0] }}</p>
                    </div>
                    <div>
                        <label class="text-xs">Doc. conductor</label>
                        <input v-model="form.conductor_num_doc" class="form-input w-full" />
                        <p v-if="errors.conductor_num_doc" class="text-xs text-red-600">{{ errors.conductor_num_doc[0] }}</p>
                    </div>
                    <div>
                        <label class="text-xs">Nombres</label>
                        <input v-model="form.conductor_nombres" class="form-input w-full" />
                    </div>
                    <div>
                        <label class="text-xs">Apellidos</label>
                        <input v-model="form.conductor_apellidos" class="form-input w-full" />
                    </div>
                    <div>
                        <label class="text-xs">Licencia</label>
                        <input v-model="form.conductor_licencia" class="form-input w-full" />
                    </div>
                </div>

                <h3 class="mt-5 mb-2 font-semibold">Comprobante que sustenta el traslado (opcional)</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="text-xs">Tipo</label>
                        <select v-model="form.doc_relacionado_tipo" class="form-select w-full">
                            <option value="01">Factura</option>
                            <option value="03">Boleta</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs">Número (F001-123)</label>
                        <input v-model="form.doc_relacionado_numero" class="form-input w-full" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs">Observación</label>
                        <input v-model="form.observacion" class="form-input w-full" />
                    </div>
                </div>

                <h3 class="mt-5 mb-2 font-semibold">Bienes a trasladar</h3>
                <div class="space-y-2">
                    <div v-for="(detail, index) in form.details" :key="index" class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end">
                        <div class="md:col-span-2">
                            <label class="text-xs">Código</label>
                            <input v-model="detail.codigo" class="form-input w-full" />
                        </div>
                        <div class="md:col-span-4">
                            <label class="text-xs">Descripción</label>
                            <input v-model="detail.descripcion" class="form-input w-full" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs">Unidad</label>
                            <select v-model="detail.unidad" class="form-select w-full">
                                <option value="NIU">NIU (unidad)</option>
                                <option value="ZZ">ZZ (servicio)</option>
                                <option value="KGM">KGM (kilogramo)</option>
                                <option value="BX">BX (caja)</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs">Cantidad</label>
                            <input v-model="detail.cantidad" type="number" step="0.0001" class="form-input w-full" />
                        </div>
                        <div class="md:col-span-2 flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-danger w-full" @click="removeDetail(index)">Quitar</button>
                        </div>
                    </div>
                </div>

                <button type="button" class="mt-2 text-sm text-blue-600" @click="addDetail">+ Agregar bien</button>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" class="btn btn-outline-secondary" @click="showModal = false">Cancelar</button>
                    <PrimaryButton :disabled="saving" @click="save">{{ saving ? 'Guardando...' : 'Registrar y enviar' }}</PrimaryButton>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
