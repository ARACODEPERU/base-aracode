<script setup>
import { computed } from "vue";
import Swal2 from "sweetalert2";

/**
 * Resumen previo al envio del formulario publico de negociacion.
 *
 * No renderiza nada por si mismo: muestra un dialogo de SweetAlert2 con todos los
 * datos que el cliente escribio (comprobante y pago incluidos), con el email y el
 * DNI resaltados para que los revise. El componente solo construye el resumen y
 * avisa el resultado; quien lo usa decide que hacer con cada respuesta.
 *
 *   <SummaryModal ref="summaryModal" ... @confirm="..." @cancel="..." />
 *   summaryModal.value?.show();
 *
 * Emite `confirm` si el cliente acepta (guardar) y `cancel` con el motivo del cierre
 * en cualquier otro caso (corregir datos, Escape, clic fuera).
 *
 * Las opciones del tipo de documento y si la ubicacion es extranjera llegan ya
 * calculadas porque las necesita el formulario: aqui solo se muestran.
 */
const props = defineProps({
    form: { type: Object, required: true },
    negotiation: { type: Object, default: () => ({}) },
    documentTypeOptions: { type: Array, default: () => [] },
    countries: { type: Array, default: () => [] },
    foreignLocation: { type: Boolean, default: false },
    boletaTercero: { type: Boolean, default: false },
    // Mercado Pago declarando un pago ya hecho (evidencia) en lugar de pagar con tarjeta.
    paymentEvidence: { type: Boolean, default: false },
    totalLabel: { type: String, default: null },
});

const emit = defineEmits(["confirm", "cancel"]);

const isRuc = computed(() => String(props.form.document_type_id) === "6");
const isBoleta = computed(() => props.form.invoice_type === "boleta");
const isFactura = computed(() => props.form.invoice_type === "factura");
const isMercadoPago = computed(() => props.negotiation.payment_method === "mercadopago");

const documentTypeLabel = computed(() =>
    props.documentTypeOptions.find((option) => String(option.value) === String(props.form.document_type_id))?.label
    ?? props.negotiation.document_type_id
    ?? "--"
);

const clientFullName = computed(() => {
    if (isRuc.value) return props.form.full_name || props.form.invoice_razon_social || "--";

    const parts = [props.form.father_lastname, props.form.mother_lastname, props.form.names].filter(Boolean);
    return parts.length ? parts.join(" ") : (props.form.full_name || "--");
});

const occupationLabel = computed(() => props.form.ocupacion?.description ?? props.form.ocupacion ?? null);
const industryLabel = computed(() => props.form.industry_id?.description ?? null);

const countryLabel = computed(() =>
    (props.countries ?? []).find((country) => Number(country.id) === Number(props.form.foreign_country_id))?.description ?? null
);

const locationLabel = computed(() => {
    if (props.foreignLocation) {
        return [countryLabel.value, props.form.foreign_state, props.form.foreign_city].filter(Boolean).join(" / ");
    }

    return props.form.ubigeo_description || null;
});

const genderLabel = computed(() => (props.form.gender === "F" ? "Femenino" : (props.form.gender === "M" ? "Masculino" : null)));

const scheduleRows = computed(() => props.negotiation.schedule || []);

// Los datos del cliente son texto libre: se escapan antes de inyectarlos en el HTML.
const escapeHtml = (value) => String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");

const summaryRow = (label, value, highlight = false) => {
    const text = String(value ?? "").trim();
    const content = text === "" ? "--" : escapeHtml(text);
    const style = highlight
        ? ' style="background:#fef3c7;font-weight:700;"'
        : "";

    return `<tr${style}><td style="padding:6px 10px;text-align:left;color:#6b7280;white-space:nowrap;vertical-align:top;">${escapeHtml(label)}</td><td style="padding:6px 10px;text-align:left;">${content}</td></tr>`;
};

const summarySection = (title) =>
    `<tr><td colspan="2" style="padding:12px 10px 4px;text-align:left;font-weight:700;text-transform:uppercase;font-size:11px;color:#6b7280;">${escapeHtml(title)}</td></tr>`;

const summaryHtml = () => {
    const form = props.form;
    const rows = [];

    rows.push(summarySection("Tus datos"));
    rows.push(summaryRow("Tipo de documento", documentTypeLabel.value));
    rows.push(summaryRow(isRuc.value ? "RUC" : "DNI", form.number, true));
    rows.push(summaryRow(isRuc.value ? "Razon social" : "Nombres y apellidos", clientFullName.value));
    rows.push(summaryRow("Email", form.email, true));
    rows.push(summaryRow("Telefono", form.telephone));
    rows.push(summaryRow("Genero", genderLabel.value));
    rows.push(summaryRow("Fecha de nacimiento", form.birthdate ? String(form.birthdate).slice(0, 10) : null));
    rows.push(summaryRow("Direccion", form.address));
    rows.push(summaryRow(props.foreignLocation ? "Pais / Estado / Ciudad" : "Ubicacion", locationLabel.value));
    rows.push(summaryRow("Cargo u ocupacion", occupationLabel.value));
    rows.push(summaryRow("Empresa", form.company));
    rows.push(summaryRow("Industria", industryLabel.value));

    rows.push(summarySection("Comprobante"));
    rows.push(summaryRow("Tipo de comprobante", isFactura.value ? "Factura electronica" : "Boleta electronica"));

    if (isFactura.value) {
        rows.push(summaryRow("RUC", form.ruc));
        rows.push(summaryRow("Razon social", form.invoice_razon_social));
        rows.push(summaryRow("Direccion fiscal", form.invoice_direccion));
        rows.push(summaryRow("Estado / Condicion", [form.invoice_estado, form.invoice_condicion].filter(Boolean).join(" / ")));
        rows.push(summaryRow("Ubicacion fiscal", [form.invoice_departamento, form.invoice_provincia, form.invoice_distrito].filter(Boolean).join(" - ")));
    }

    if (isBoleta.value && props.boletaTercero) {
        rows.push(summaryRow("Boleta a nombre de", [form.boleta_nombre, form.boleta_numero ? `DNI ${form.boleta_numero}` : null].filter(Boolean).join(" - "), true));
    }

    rows.push(summarySection("Acuerdo y pago"));
    rows.push(summaryRow("Modalidad", props.negotiation.payment_type === "installments" ? `${scheduleRows.value.length} cuotas` : "Pago unico"));
    rows.push(summaryRow("Monto total", props.totalLabel));

    if (props.negotiation.payment_type === "installments" && scheduleRows.value.length) {
        rows.push(summaryRow("Primera cuota", `${props.negotiation.currency} ${Number(scheduleRows.value[0]?.amount || 0).toFixed(2)}`));
        rows.push(summaryRow("Vencimiento 1ra cuota", scheduleRows.value[0]?.due_date ?? null));
    }

    if (isMercadoPago.value) {
        rows.push(summaryRow("Forma de pago", props.paymentEvidence ? "Declaro que ya pague (adjunto voucher)" : "Pago ahora con tarjeta"));
    }

    if (form.voucher?.name) {
        rows.push(summaryRow("Voucher adjunto", form.voucher.name));
    }

    return `<div style="text-align:left;font-size:13px;max-height:55vh;overflow:auto;"><table style="width:100%;border-collapse:collapse;">${rows.join("")}</table></div>`;
};

/** Muestra el resumen y avisa si el cliente acepta o vuelve al formulario. */
const show = () => {
    Swal2.fire({
        title: "Revisa tus datos",
        html: summaryHtml(),
        icon: "question",
        width: 640,
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Si, mis datos son correctos",
        cancelButtonText: "No, corregir datos",
        reverseButtons: true,
        padding: "2em",
        customClass: "sweet-alerts",
    }).then((result) => {
        if (result.isConfirmed) {
            emit("confirm");

            return;
        }

        emit("cancel", result.dismiss);
    });
};

defineExpose({ show });
</script>

<template>
    <!-- El dialogo lo muestra SweetAlert2: este componente no tiene interfaz propia. -->
    <span v-if="false" aria-hidden="true" />
</template>
