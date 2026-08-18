<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from "@/Components/vristo/layout/Navigation.vue";
import IconLoader from "@/Components/vristo/icon/icon-loader.vue";
import { Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import Swal2 from "sweetalert2";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faCheckCircle, faFileArchive, faFileCode, faFilePdf, faLink, faPaperPlane, faTrashAlt, faXmarkCircle } from "@fortawesome/free-solid-svg-icons";

const props = defineProps({
    negotiation: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    paymentMethods: { type: Array, default: () => [] },
});

const processing = ref(false);

const statusByValue = (value) => props.statuses.find((item) => item.value === value);

const paymentMethodLabel = (value) => props.paymentMethods.find((item) => item.value === value)?.label || value;

const clientName = computed(() => props.negotiation.client_data?.full_name || props.negotiation.client?.full_name || "Sin cliente");

const publicUrl = computed(() => {
    const url = route("comm_negotiations_public_show", props.negotiation.token);
    return url.startsWith("http") ? url : window.location.origin + url;
});

const voucherUrl = computed(() => props.negotiation.voucher_path ? `/storage/${props.negotiation.voucher_path}` : null);

const canVerify = computed(() => props.negotiation.status === "confirmada");

const saleDocument = computed(() => props.negotiation.sale_document || null);

const documentNumber = computed(() => {
    const doc = saleDocument.value;
    return doc ? `${doc.invoice_serie}-${doc.invoice_correlative}` : null;
});

const documentTypeLabel = computed(() => {
    const doc = saleDocument.value;
    if (!doc) return null;
    return doc.invoice_type_doc === "01" ? "Factura electronica" : "Boleta electronica";
});

const sunatStatus = computed(() => {
    const status = saleDocument.value?.invoice_status;
    if (!status) return null;
    const map = {
        Pendiente: { label: "Pendiente de envio a SUNAT", color: "warning" },
        Aceptada: { label: "Aceptada por SUNAT", color: "success" },
        Rechazada: { label: "Rechazada por SUNAT", color: "danger" },
        registrado: { label: "Registrado", color: "info" },
    };
    return map[status] || { label: status, color: "secondary" };
});

const documentPdfUrl = computed(() => {
    const doc = saleDocument.value;
    return doc ? route("saledocuments_download", [doc.id, doc.invoice_type_doc, "PDF"]) : null;
});

const documentXmlUrl = computed(() => {
    const doc = saleDocument.value;
    return doc ? route("saledocuments_download", [doc.id, doc.invoice_type_doc, "XML"]) : null;
});

const documentCdrUrl = computed(() => {
    const doc = saleDocument.value;
    return doc && doc.invoice_cdr ? route("saledocuments_download", [doc.id, doc.invoice_type_doc, "CDR"]) : null;
});

const copyLink = () => {
    navigator.clipboard.writeText(publicUrl.value).then(() => {
        Swal2.fire({
            title: "Enlace copiado",
            text: "El enlace publico fue copiado al portapapeles.",
            icon: "success",
            padding: "2em",
            customClass: "sweet-alerts",
        });
    });
};

const quoteEmail = computed(() => props.negotiation.email?.trim() || "");

const sendQuote = () => {
    // Si aun no hay correo registrado, pedirlo en la alerta para enviar.
    if (!quoteEmail.value) {
        Swal2.fire({
            title: "Enviar cotizacion?",
            text: "No hay un correo registrado. Escribe el correo del cliente al que se enviara la cotizacion:",
            icon: "info",
            input: "email",
            inputPlaceholder: "correo@cliente.com",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#6b7280",
            confirmButtonText: "Enviar",
            cancelButtonText: "Cancelar",
            showLoaderOnConfirm: true,
            padding: "2em",
            customClass: "sweet-alerts",
            preConfirm: (email) => {
                const value = (email || "").trim();

                if (!value) {
                    Swal2.showValidationMessage("Debes ingresar un correo para enviar la cotizacion.");
                    return false;
                }

                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    Swal2.showValidationMessage("Ingresa un correo valido.");
                    return false;
                }

                return axios.post(route("comm_negotiations_send_quote", props.negotiation.id), { email: value })
                    .then((res) => {
                        return {
                            message: res.data?.message || "Cotizacion enviada correctamente.",
                        };
                    })
                    .catch((error) => {
                        const msg = error.response?.data?.errors?.email?.[0]
                            || error.response?.data?.message
                            || "No se pudo enviar la cotizacion.";
                        Swal2.showValidationMessage(msg);
                        return false;
                    });
            },
            allowOutsideClick: () => !Swal2.isLoading(),
        }).then((result) => {
            if (!result.isConfirmed || !result.value) return;

            Swal2.fire({
                title: "Enviado",
                text: result.value.message,
                icon: "success",
                confirmButtonText: "Perfecto",
                padding: "2em",
                customClass: "sweet-alerts",
            });
            refresh();
        });

        return;
    }

    Swal2.fire({
        title: "Enviar cotizacion?",
        text: `Se enviara el enlace de la cotizacion a ${quoteEmail.value}.`,
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#6b7280",
        confirmButtonText: "Si, enviar",
        cancelButtonText: "Cancelar",
        showLoaderOnConfirm: true,
        padding: "2em",
        customClass: "sweet-alerts",
        preConfirm: () => axios.post(route("comm_negotiations_send_quote", props.negotiation.id))
            .then((res) => ({
                message: res.data?.message || "Cotizacion enviada correctamente.",
            }))
            .catch((error) => {
                const msg = error.response?.data?.errors?.email?.[0]
                    || error.response?.data?.message
                    || "No se pudo enviar la cotizacion.";
                Swal2.showValidationMessage(msg);
                return false;
            }),
        allowOutsideClick: () => !Swal2.isLoading(),
    }).then((result) => {
        if (!result.isConfirmed || !result.value) return;

        Swal2.fire({
            title: "Enviado",
            text: result.value.message,
            icon: "success",
            confirmButtonText: "Perfecto",
            padding: "2em",
            customClass: "sweet-alerts",
        });
        refresh();
    });
};

const refresh = () => {
    router.reload({ only: ["negotiation"] });
};

const approve = () => {
    Swal2.fire({
        title: "Aprobar negociacion?",
        text: "Se abrira el proceso de aprobacion: se registrara al cliente, usuario, estudiante, matriculas o suscripciones y se generara el comprobante.",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Si, procesar",
        cancelButtonText: "Cancelar",
        padding: "2em",
        customClass: "sweet-alerts",
    }).then((result) => {
        if (result.isConfirmed) {
            router.visit(route("comm_negotiations_process", props.negotiation.id));
        }
    });
};

const reject = () => {
    Swal2.fire({
        title: "Rechazar negociacion",
        input: "textarea",
        inputPlaceholder: "Indica el motivo del rechazo...",
        inputAttributes: {
            rows: "4",
        },
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Rechazar",
        cancelButtonText: "Cancelar",
        showLoaderOnConfirm: true,
        padding: "2em",
        customClass: "sweet-alerts",
        preConfirm: (reason) => {
            if (!reason || !reason.trim()) {
                Swal2.showValidationMessage("Debe indicar el motivo del rechazo.");
                return;
            }

            return axios.post(route("comm_negotiations_reject", props.negotiation.id), { reason }).then((res) => {
                if (res.data && !res.data.success) {
                    Swal2.showValidationMessage(res.data.message || "Error al rechazar");
                }

                return res;
            }).catch((error) => {
                Swal2.showValidationMessage(error.response?.data?.message || "Error de conexion");
            });
        },
        allowOutsideClick: () => !Swal2.isLoading(),
    }).then((result) => {
        if (!result.isConfirmed) return;

        Swal2.fire({
            title: "Rechazada",
            text: "El cliente podra reintentar con un nuevo voucher.",
            icon: "warning",
            padding: "2em",
            customClass: "sweet-alerts",
        });

        refresh();
    });
};

const cancel = () => {
    Swal2.fire({
        title: "Cancelar negociacion?",
        text: "La negociacion quedara cancelada y el enlace publico dejara de aceptar envios.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Si, cancelar",
        cancelButtonText: "No",
        showLoaderOnConfirm: true,
        padding: "2em",
        customClass: "sweet-alerts",
        preConfirm: () => {
            return axios.post(route("comm_negotiations_cancel", props.negotiation.id)).then((res) => {
                if (res.data && !res.data.success) {
                    Swal2.showValidationMessage(res.data.message || "Error al cancelar");
                }

                return res;
            }).catch((error) => {
                Swal2.showValidationMessage(error.response?.data?.message || "Error de conexion");
            });
        },
        allowOutsideClick: () => !Swal2.isLoading(),
    }).then((result) => {
        if (!result.isConfirmed) return;

        Swal2.fire({
            title: "Cancelada",
            text: "Negociacion cancelada correctamente",
            icon: "success",
            padding: "2em",
            customClass: "sweet-alerts",
        });

        refresh();
    });
};
</script>

<template>
    <AppLayout title="Detalle de negociacion">
        <Navigation :routeModule="route('comm_dashboard')" titleModule="Comercial"
            :data="[
                {route: route('comm_negotiations'), title: 'Negociaciones'},
                {title: 'Detalle'}
            ]"
        />

        <div class="mt-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex flex-col gap-2">
                <h2 class="text-xl font-semibold dark:text-white">{{ negotiation.title }}</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge" :class="`bg-${statusByValue(negotiation.status)?.color || 'secondary'}`">
                        {{ statusByValue(negotiation.status)?.label || negotiation.status }}
                    </span>
                    <span class="text-sm text-gray-500">
                        {{ negotiation.currency }} {{ negotiation.total_price }}
                    </span>
                    <span class="text-sm text-gray-500">
                        {{ paymentMethodLabel(negotiation.payment_method) }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="btn btn-secondary" @click="copyLink">
                    <FontAwesomeIcon :icon="faLink" class="mr-2 h-4 w-4" />
                    Copiar enlace publico
                </button>
                <button type="button" class="btn btn-primary" @click="sendQuote">
                    <FontAwesomeIcon :icon="faPaperPlane" class="mr-2 h-4 w-4" />
                    Enviar cotizacion
                </button>
                <Link :href="route('comm_negotiations')" class="btn btn-success">
                    Ir al listado
                </Link>
            </div>
        </div>

        <div v-if="canVerify" class="mt-5">
            <div class="rounded-md border border-amber-200 border-l-4 border-l-amber-500 bg-amber-50 p-4 dark:border-amber-900/60 dark:border-l-amber-400 dark:bg-amber-950/30">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold text-amber-800 dark:text-amber-200">Negociacion confirmada por el cliente</p>
                        <p class="text-sm text-amber-700 dark:text-amber-300">
                            Revisa el voucher y los datos del cliente para aprobar o rechazar.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success" :disabled="processing" @click="approve">
                            <FontAwesomeIcon :icon="faCheckCircle" class="mr-2 h-4 w-4" />
                            Aprobar
                        </button>
                        <button type="button" class="btn btn-danger" :disabled="processing" @click="reject">
                            <FontAwesomeIcon :icon="faXmarkCircle" class="mr-2 h-4 w-4" />
                            Rechazar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="negotiation.status === 'rechazada' && negotiation.rejected_reason" class="mt-5">
            <div class="rounded-md border border-red-200 border-l-4 border-l-red-500 bg-red-50 p-4 dark:border-red-900/60 dark:border-l-red-400 dark:bg-red-950/30">
                <p class="font-semibold text-red-800 dark:text-red-200">Motivo del rechazo</p>
                <p class="text-sm text-red-700 dark:text-red-300">{{ negotiation.rejected_reason }}</p>
            </div>
        </div>

        <div v-if="saleDocument" class="mt-5">
            <div class="panel">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold dark:text-white">Comprobante de venta</h3>
                        <p class="text-sm text-gray-500">
                            {{ documentTypeLabel }} {{ documentNumber }}
                            <span v-if="saleDocument.invoice_broadcast_date" class="text-gray-400">- {{ saleDocument.invoice_broadcast_date }}</span>
                        </p>
                    </div>
                    <span v-if="sunatStatus" class="badge" :class="`bg-${sunatStatus.color}`">{{ sunatStatus.label }}</span>
                </div>

                <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/40">
                        <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Monto</p>
                        <p class="mt-1 text-sm font-semibold dark:text-white">
                            {{ saleDocument.invoice_type_currency }} {{ saleDocument.invoice_mto_imp_sale ?? saleDocument.overall_total }}
                        </p>
                    </div>
                    <div class="rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/40">
                        <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Estado SUNAT</p>
                        <p class="mt-1 text-sm dark:text-white">{{ saleDocument.invoice_status || 'Pendiente de envio' }}</p>
                    </div>
                    <div class="rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/40">
                        <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Respuesta SUNAT</p>
                        <p class="mt-1 text-sm dark:text-white">
                            <template v-if="saleDocument.invoice_response_description">
                                {{ saleDocument.invoice_response_code ? `${saleDocument.invoice_response_code} - ` : '' }}{{ saleDocument.invoice_response_description }}
                            </template>
                            <template v-else>Sin respuesta aun</template>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a :href="documentPdfUrl" target="_blank" class="btn btn-primary btn-sm">
                        <FontAwesomeIcon :icon="faFilePdf" class="mr-2 h-4 w-4" />
                        Ver / descargar PDF
                    </a>
                    <a v-if="documentXmlUrl" :href="documentXmlUrl" class="btn btn-secondary btn-sm">
                        <FontAwesomeIcon :icon="faFileCode" class="mr-2 h-4 w-4" />
                        Descargar XML
                    </a>
                    <a v-if="documentCdrUrl" :href="documentCdrUrl" class="btn btn-secondary btn-sm">
                        <FontAwesomeIcon :icon="faFileArchive" class="mr-2 h-4 w-4" />
                        Descargar CDR
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="panel lg:col-span-2">
                <h3 class="mb-4 font-semibold dark:text-white">Detalle del acuerdo</h3>

                <div class="mb-4 overflow-hidden rounded-md border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Item</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Precio</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="item in negotiation.items" :key="`${item.item_type}-${item.id}`">
                                <td class="px-4 py-3 text-sm">{{ item.title }}</td>
                                <td class="px-4 py-3 text-right text-sm">{{ item.price !== null ? `${negotiation.currency} ${item.price}` : '--' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/40">
                        <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Tipo de pago</p>
                        <p class="mt-1 text-sm font-semibold dark:text-white">
                            {{ negotiation.payment_type === 'installments' ? 'Cuotas' : 'Pago unico' }}
                        </p>
                    </div>
                    <div class="rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/40">
                        <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                            {{ negotiation.payment_type === 'installments' ? 'Cuotas' : 'Plazo de pago' }}
                        </p>
                        <p class="mt-1 text-sm font-semibold dark:text-white">
                            {{ negotiation.payment_type === 'installments' ? `${negotiation.schedule?.length || '--'} cuotas` : `${negotiation.single_payment_days ?? '--'} dias` }}
                        </p>
                    </div>
                </div>

                <div v-if="negotiation.payment_type === 'installments' && negotiation.schedule?.length" class="mb-4 overflow-hidden rounded-md border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Vencimiento</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="(row, index) in negotiation.schedule" :key="index">
                                <td class="px-4 py-3 text-sm">{{ index + 1 }}</td>
                                <td class="px-4 py-3 text-sm">{{ row.due_date }}</td>
                                <td class="px-4 py-3 text-right text-sm">{{ negotiation.currency }} {{ row.amount }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="negotiation.body" class="prose-sm max-w-none rounded-md border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-800/40" v-html="negotiation.body"></div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/40">
                        <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Canal de contacto</p>
                        <p class="mt-1 text-sm dark:text-white">{{ negotiation.contact_channel || '--' }}</p>
                        <p v-if="negotiation.contact_detail" class="text-sm text-gray-500">{{ negotiation.contact_detail }}</p>
                    </div>
                    <div class="rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/40">
                        <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Enlace de pago</p>
                        <p v-if="negotiation.payment_link" class="mt-1 text-sm break-all">
                            <a :href="negotiation.payment_link" target="_blank" class="text-primary underline">{{ negotiation.payment_link }}</a>
                        </p>
                        <p v-else class="mt-1 text-sm text-gray-500">--</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <div class="panel">
                    <h3 class="mb-4 font-semibold dark:text-white">Datos del cliente</h3>
                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Nombre</dt>
                            <dd class="font-semibold dark:text-white">{{ clientName }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Documento</dt>
                            <dd class="dark:text-white">{{ negotiation.client_data?.number || negotiation.client?.number || '--' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Comprobante</dt>
                            <dd class="dark:text-white">{{ negotiation.invoice?.invoice_type === 'factura' ? 'Factura electronica' : 'Boleta electronica' }}</dd>
                        </div>
                        <div v-if="negotiation.invoice?.invoice_type === 'factura'">
                            <dt class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">RUC</dt>
                            <dd class="dark:text-white">{{ negotiation.invoice?.ruc || '--' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Email</dt>
                            <dd class="dark:text-white">{{ negotiation.email || negotiation.client_data?.email || negotiation.client?.email || '--' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Telefono</dt>
                            <dd class="dark:text-white">{{ negotiation.client_data?.telephone || negotiation.client?.telephone || '--' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Ocupacion</dt>
                            <dd class="dark:text-white">{{ negotiation.client_data?.ocupacion || '--' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Especializacion</dt>
                            <dd class="dark:text-white">{{ negotiation.client_data?.profession || '--' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="panel">
                    <h3 class="mb-4 font-semibold dark:text-white">Voucher de pago</h3>
                    <p class="mb-3 text-xs text-gray-500">
                        {{ negotiation.status === 'rechazada' ? 'El voucher fue rechazado; el cliente puede volver a enviar uno.' : (negotiation.status === 'aprobada' || negotiation.status === 'completada') ? 'Voucher aceptado por el asesor.' : 'Voucher enviado por el cliente.' }}
                    </p>
                    <a v-if="voucherUrl" :href="voucherUrl" target="_blank">
                        <img :src="voucherUrl" alt="Voucher" class="w-full rounded-md border border-gray-200 object-contain dark:border-gray-700" />
                    </a>
                    <p v-else class="text-sm text-gray-500">Sin voucher cargado.</p>
                </div>

                <div class="panel">
                    <h3 class="mb-4 font-semibold dark:text-white">Informacion del registro</h3>
                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Creado por</dt>
                            <dd class="dark:text-white">{{ negotiation.creator?.name || '--' }}</dd>
                        </div>
                        <div v-if="negotiation.verified_at">
                            <dt class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Verificado por</dt>
                            <dd class="dark:text-white">{{ negotiation.verifier?.name || '--' }} - {{ new Date(negotiation.verified_at).toLocaleString() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Enlace publico</dt>
                            <dd class="break-all text-xs text-gray-500">{{ publicUrl }}</dd>
                        </div>
                    </dl>

                    <button
                        v-if="negotiation.status !== 'cancelada' && negotiation.status !== 'aprobada' && negotiation.status !== 'completada'"
                        type="button"
                        v-can="'comm_negociaciones_editar'"
                        class="btn btn-outline-danger btn-sm mt-4 w-full"
                        @click="cancel"
                    >
                        <FontAwesomeIcon :icon="faTrashAlt" class="mr-2 h-4 w-4" />
                        Cancelar negociacion
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
