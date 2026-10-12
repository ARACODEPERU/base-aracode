<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from "@/Components/vristo/layout/Navigation.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faFloppyDisk, faLink } from "@fortawesome/free-solid-svg-icons";
import Swal2 from "sweetalert2";

const props = defineProps({
    legalLinks: { type: Object, default: () => ({}) },
    legalLinkDefaults: { type: Object, default: () => ({}) },
    legalLinkLabels: { type: Object, default: () => ({}) },
});

// Pestanas de la configuracion del modulo. Hoy solo la de enlaces legales; queda
// armada para sumar mas pestanas sin cambiar la ruta ni el menu.
const tabs = [
    { key: "legal", label: "Enlaces legales", icon: faLink },
];
const activeTab = ref("legal");

// Estos enlaces alimentan los checks obligatorios del formulario publico de una
// negociacion. Un valor propio del sitio se guarda como ruta (/terminos-condiciones)
// y uno externo como URL completa (https://...).
const form = useForm({
    terminos: props.legalLinks.terminos ?? "",
    devoluciones: props.legalLinks.devoluciones ?? "",
    privacidad: props.legalLinks.privacidad ?? "",
});

const fields = [
    { key: "terminos", label: "Términos y condiciones" },
    { key: "devoluciones", label: "Política de devoluciones y Reembolsos - CPA Academy" },
    { key: "privacidad", label: "Política de privacidad" },
];

const defaultFor = (key) => props.legalLinkDefaults[key] ?? "";
const checkLabelFor = (key) => props.legalLinkLabels[key] ?? "";

const submit = () => {
    form.post(route("comm_settings_legal_update"), {
        preserveScroll: true,
        onSuccess: () => {
            Swal2.fire({
                title: "Configuración guardada",
                text: "Los enlaces se usan en los checks del formulario público de negociación.",
                icon: "success",
                padding: "2em",
                customClass: "sweet-alerts",
            });
        },
        onError: (errors) => {
            Swal2.fire({
                title: "Revisa los enlaces",
                text: Object.values(errors).flat().join(" ") || "No se pudo guardar la configuración.",
                icon: "error",
                padding: "2em",
                customClass: "sweet-alerts",
            });
        },
    });
};
</script>

<template>
    <AppLayout title="Configuración">
        <Navigation
            :routeModule="route('comm_dashboard')"
            titleModule="Comercial"
            :data="[{ title: 'Configuración' }]"
        />

        <div class="mt-5 panel p-0">
            <!-- Pestanas de la configuracion -->
            <div class="flex flex-wrap gap-2 border-b border-gray-200 p-3 dark:border-gray-700">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="btn"
                    :class="activeTab === tab.key ? 'btn-primary' : 'btn-outline-primary'"
                    @click="activeTab = tab.key"
                >
                    <FontAwesomeIcon :icon="tab.icon" class="mr-2 h-4 w-4" />
                    {{ tab.label }}
                </button>
            </div>

            <!-- Enlaces legales de la negociacion publica -->
            <div v-show="activeTab === 'legal'" class="p-6">
                <h2 class="mb-1 text-lg font-semibold dark:text-white">Enlaces legales de la negociación pública</h2>
                <p class="mb-6 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                    El cliente debe aceptar los tres documentos antes de enviar sus datos. Cada enlace puede ser una ruta
                    del propio sitio (por ejemplo /terminos-condiciones) o una URL completa (https://...).
                </p>

                <div class="grid grid-cols-1 gap-5">
                    <div v-for="field in fields" :key="field.key">
                        <InputLabel :for="field.key" :value="field.label" />
                        <TextInput
                            :id="field.key"
                            v-model="form[field.key]"
                            type="text"
                            class="mt-1 block w-full"
                            :placeholder="defaultFor(field.key)"
                        />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Por defecto: <span class="font-medium">{{ defaultFor(field.key) }}</span>
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Texto del check: <span class="font-medium">{{ checkLabelFor(field.key) }}</span>
                        </p>
                        <InputError :message="form.errors[field.key]" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        type="button"
                        class="btn btn-primary"
                        :class="{ 'opacity-50': form.processing }"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        <FontAwesomeIcon :icon="faFloppyDisk" class="mr-2 h-4 w-4" />
                        Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
