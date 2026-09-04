<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from "@/Components/vristo/layout/Navigation.vue";
import IconLoader from "@/Components/vristo/icon/icon-loader.vue";
import { Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import Swal2 from "sweetalert2";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
    faCheckCircle,
    faCircleNotch,
    faClipboardCheck,
    faPlay,
    faRotate,
    faXmarkCircle,
} from "@fortawesome/free-solid-svg-icons";

const props = defineProps({
    negotiation: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
    paymentMethods: { type: Array, default: () => [] },
    stepsStatus: { type: Object, default: () => ({}) },
});

const hasCourses = computed(() => props.negotiation.items?.some((item) => item.item_type === "course") ?? false);
const hasSubscriptions = computed(() => props.negotiation.items?.some((item) => item.item_type === "subscription") ?? false);
const isSinglePayment = computed(() => props.negotiation.payment_type === "single");
const isInstallments = computed(() => props.negotiation.payment_type === "installments");

// Estado inicial de cada paso, tomado del progreso ya guardado en la base de datos.
const initialStatus = (key, fallback = "pending") => props.stepsStatus?.[key] ?? fallback;

const steps = ref([
    {
        key: "person",
        label: "Datos del cliente",
        description: "Guardar los datos del cliente en la tabla people",
        status: initialStatus("person"),
        skipped: false,
    },
    {
        key: "user",
        label: "Crear usuario",
        description: "Crear el usuario de acceso con rol Alumno",
        status: initialStatus("user"),
        skipped: false,
    },
    {
        key: "student",
        label: "Registrar estudiante",
        description: "Crear el estudiante en aca_students",
        status: initialStatus("student"),
        skipped: false,
    },
    {
        key: "registrations",
        label: "Matriculas de cursos",
        description: "Registrar los cursos en aca_cap_registrations",
        status: initialStatus("registrations"),
        skipped: !hasCourses.value,
    },
    {
        key: "subscriptions",
        label: "Suscripciones",
        description: "Registrar las suscripciones segun el plan",
        status: initialStatus("subscriptions"),
        skipped: !hasSubscriptions.value,
    },
    {
        key: "installments",
        label: "Cuentas por cobrar (cuotas)",
        description: "Registrar la venta en cuotas con su cronograma en cuentas por cobrar",
        status: initialStatus("installments"),
        skipped: isSinglePayment.value,
    },
    {
        key: "document",
        label: "Comprobante de venta",
        description: "Generar la boleta o factura",
        status: initialStatus("document"),
        skipped: false,
    },
    {
        key: "email",
        label: "Envio de correo",
        description: "Enviar al cliente su comprobante en PDF, usuario y contraseña de acceso",
        status: initialStatus("email"),
        skipped: false,
    },
    {
        key: "complete",
        label: "Finalizar",
        description: "Marcar la negociacion como aprobada",
        status: initialStatus("complete"),
        skipped: false,
    },
]);

const processing = ref(false);
const allStepsDone = computed(() => steps.value.filter((s) => !s.skipped).every((s) => s.status === "done"));
const finished = ref(allStepsDone.value);

const visibleSteps = computed(() => steps.value.filter((step) => !step.skipped));

// Pasos efectivamente completados (avance real del proceso).
const completedCount = computed(() => steps.value.filter((step) => !step.skipped && step.status === "done").length);

const progress = computed(() => {
    const total = visibleSteps.value.length;
    return total ? Math.round((completedCount.value / total) * 100) : 0;
});

const hasErrors = computed(() => steps.value.some((step) => step.status === "error"));

const primaryLabel = computed(() => {
    if (finished.value) return "Proceso completado";
    return hasErrors.value ? "Reintentar proceso" : "Iniciar proceso de aprobacion";
});

const statusByValue = (value) => props.statuses.find((item) => item.value === value);

const clientName = computed(() => props.negotiation.client_data?.full_name || props.negotiation.client?.full_name || "Sin cliente");

const stepBadge = (status) => {
    if (status === "done") return "success";
    if (status === "skipped") return "secondary";
    if (status === "error") return "danger";
    if (status === "running") return "primary";
    return "dark";
};

const stepLabel = (status) => {
    if (status === "done") return "Completado";
    if (status === "skipped") return "No aplica";
    if (status === "error") return "Error";
    if (status === "running") return "Procesando...";
    return "Pendiente";
};

const toast = (message, icon = "success") => {
    Swal2.fire({
        toast: true,
        position: "top-end",
        title: message,
        icon,
        timer: 2200,
        timerProgressBar: true,
        showConfirmButton: false,
        padding: "1em",
        customClass: "sweet-alerts",
    });
};

const run = async () => {
    if (processing.value || finished.value) return;

    processing.value = true;

    for (const step of steps.value) {
        if (step.skipped || step.status === "done") continue;

        step.status = "running";

        try {
            // El paso de correo genera el PDF del comprobante y puede tardar mas; le damos mas timeout.
            const timeout = step.key === "email" ? 120000 : 60000;
            const res = await axios.post(route(`comm_negotiations_process_${step.key}`, props.negotiation.id), {}, { timeout });

            if (!res.data || !res.data.success) {
                throw new Error(res.data?.message || "Error al procesar el paso");
            }

            step.status = res.data.skipped ? "skipped" : "done";

            if (!res.data.skipped) {
                toast(res.data.message || `${step.label} completado`);
            }
        } catch (error) {
            step.status = "error";
            processing.value = false;

            Swal2.fire({
                title: "Error en el paso",
                text: error.response?.data?.message || error.message || "Error de conexion",
                icon: "error",
                confirmButtonText: "Entendido",
                padding: "2em",
                customClass: "sweet-alerts",
            });

            return;
        }
    }

    processing.value = false;
    finished.value = true;

    Swal2.fire({
        title: "Enhorabuena",
        text: "La negociacion fue aprobada y todos los procesos se completaron.",
        icon: "success",
        showCancelButton: true,
        confirmButtonText: "Ir al detalle",
        cancelButtonText: "Ir al listado",
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#6b7280",
        padding: "2em",
        customClass: "sweet-alerts",
    }).then((result) => {
        if (result.isConfirmed) {
            router.visit(route("comm_negotiations_show", props.negotiation.id));
        } else {
            router.visit(route("comm_negotiations"));
        }
    });
};
</script>

<template>
    <AppLayout title="Proceso de aprobacion">
        <Navigation :routeModule="route('comm_dashboard')" titleModule="Comercial"
            :data="[
                {route: route('comm_negotiations'), title: 'Negociaciones'},
                {title: 'Proceso de aprobacion'}
            ]"
        />

        <div class="mt-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex flex-col gap-2">
                <h2 class="text-xl font-semibold dark:text-white">{{ negotiation.title }}</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge" :class="`bg-${statusByValue(negotiation.status)?.color || 'secondary'}`">
                        {{ statusByValue(negotiation.status)?.label || negotiation.status }}
                    </span>
                    <span class="text-sm text-gray-500">{{ negotiation.currency }} {{ negotiation.total_price }}</span>
                    <span class="text-sm text-gray-500">{{ clientName }}</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Link :href="route('comm_negotiations_show', negotiation.id)" class="btn btn-secondary">
                    Volver al detalle
                </Link>
            </div>
        </div>

        <div class="mt-5 panel">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-semibold dark:text-white">Progreso del proceso</h3>
                    <p class="text-sm text-gray-500">{{ completedCount }} de {{ visibleSteps.length }} pasos</p>
                </div>
                <span class="text-lg font-bold text-primary">{{ progress }}%</span>
            </div>

            <div class="mb-6 flex h-3 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div
                    class="h-full rounded-full bg-primary transition-all duration-500"
                    :style="{ width: `${progress}%` }"
                ></div>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-3 md:grid-cols-2">
                <div
                    v-for="(step, index) in visibleSteps"
                    :key="step.key"
                    class="flex items-start gap-3 rounded-md border border-gray-200 p-3 dark:border-gray-700"
                >
                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                        :class="{
                            'bg-green-100 text-green-600': step.status === 'done',
                            'bg-gray-100 text-gray-400': step.status === 'skipped',
                            'bg-red-100 text-red-600': step.status === 'error',
                            'bg-blue-100 text-blue-600': step.status === 'running',
                            'bg-gray-100 text-gray-500': step.status === 'pending',
                        }"
                    >
                        <FontAwesomeIcon v-if="step.status === 'done'" :icon="faCheckCircle" class="h-4 w-4" />
                        <FontAwesomeIcon v-else-if="step.status === 'error'" :icon="faXmarkCircle" class="h-4 w-4" />
                        <FontAwesomeIcon v-else-if="step.status === 'running'" :icon="faCircleNotch" class="h-4 w-4 animate-spin" />
                        <span v-else class="text-sm font-semibold">{{ index + 1 }}</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-semibold dark:text-white">{{ step.label }}</p>
                            <span class="badge" :class="`bg-${stepBadge(step.status)}`">{{ stepLabel(step.status) }}</span>
                        </div>
                        <p class="text-sm text-gray-500">{{ step.description }}</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <Link :href="route('comm_negotiations')" class="btn btn-outline-dark">
                    Ir al listado
                </Link>
                <button
                    type="button"
                    class="btn btn-success"
                    :disabled="processing || finished"
                    @click="run"
                >
                    <IconLoader v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                    <FontAwesomeIcon v-else-if="hasErrors" :icon="faRotate" class="mr-2 h-4 w-4" />
                    <FontAwesomeIcon v-else :icon="finished ? faClipboardCheck : faPlay" class="mr-2 h-4 w-4" />
                    {{ primaryLabel }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>
