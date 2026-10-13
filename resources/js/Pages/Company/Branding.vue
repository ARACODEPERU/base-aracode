<script setup>
import { computed } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from "@/Components/vristo/layout/Navigation.vue";
import { canalesRgb, variablesDeMarca } from "@/utils/colorMarca";

/*
|--------------------------------------------------------------------------
| Configuraciones → Branding
|--------------------------------------------------------------------------
|
| Los colores de marca de esta instalación. Lo que se guarde aquí se aplica a
| todo el ERP: el layout inyecta las variables `--brand-*` definidas en
| utils/colorMarca.js y los tokens de Tailwind (botones, badges, tablas,
| menú) se pintan con la identidad del cliente.
|
| Sin colores guardados se usa la paleta de fábrica. Dejarla como está y
| guardar devuelve la instalación a esa paleta.
*/

/** Valores de fábrica: los mismos de resources/css/app.css. */
const COLOR_PRIMARIO_FABRICA = "#4361ee";
const COLOR_SECUNDARIO_FABRICA = "#805dca";

const props = defineProps({
    colores: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    brand_primary: (props.colores?.primary || COLOR_PRIMARIO_FABRICA).toLowerCase(),
    brand_secondary: (props.colores?.secondary || COLOR_SECUNDARIO_FABRICA).toLowerCase(),
});

/** La tarjeta de muestra se pinta con los colores que hay en el formulario. */
const variablesVistaPrevia = computed(() =>
    variablesDeMarca({
        primary: form.brand_primary,
        secondary: form.brand_secondary,
    })
);

const primarioValido = computed(() => canalesRgb(form.brand_primary) !== null);
const secundarioValido = computed(() => canalesRgb(form.brand_secondary) !== null);
const puedeGuardar = computed(
    () => primarioValido.value && secundarioValido.value && !form.processing
);

/** Aviso venido de la sesión tras guardar (flash.message). */
const aviso = computed(() => usePage().props.flash?.message ?? "");

/** Deja los dos colores como los de fábrica; se confirman con «Guardar». */
const restaurarFabrica = () => {
    form.brand_primary = COLOR_PRIMARIO_FABRICA;
    form.brand_secondary = COLOR_SECUNDARIO_FABRICA;
};

/**
 * Guarda la marca. Si los colores son los de fábrica se envían como nulos:
 * así la instalación queda "sin marca propia" y el ERP usa su paleta original
 * (que es exactamente lo mismo, pero sin columnas ocupadas por duplicado).
 */
const guardar = () => {
    form
        .transform((datos) => ({
            brand_primary:
                datos.brand_primary.toLowerCase() === COLOR_PRIMARIO_FABRICA
                    ? null
                    : datos.brand_primary,
            brand_secondary:
                datos.brand_secondary.toLowerCase() === COLOR_SECUNDARIO_FABRICA
                    ? null
                    : datos.brand_secondary,
        }))
        .post(route("branding_update"), { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Branding">
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Configuraciones</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Branding</span>
            </li>
        </Navigation>

        <div
            v-if="aviso"
            class="mt-5 rounded-md bg-success/10 px-4 py-3 text-sm font-semibold text-success"
        >
            {{ aviso }}
        </div>

        <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-2">
            <div class="panel">
                <h5 class="mb-1 text-lg font-semibold dark:text-white-light">
                    Colores de la marca
                </h5>
                <p class="mb-5 text-sm text-white-dark">
                    Personaliza el color primario y el secundario de esta
                    instalación. Todo el ERP (menú, botones, badges y tablas) se
                    pinta con ellos.
                </p>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold" for="brand_primary">
                            Color primario
                        </label>
                        <div class="flex items-center gap-3">
                            <input
                                id="brand_primary"
                                v-model="form.brand_primary"
                                type="color"
                                class="h-10 w-14 cursor-pointer rounded border border-[#ebedf2] bg-white p-1 dark:border-[#191e3a] dark:bg-[#0e1726]"
                            />
                            <input
                                :value="form.brand_primary"
                                type="text"
                                class="form-input"
                                maxlength="7"
                                spellcheck="false"
                                @input="form.brand_primary = $event.target.value.toLowerCase()"
                            />
                        </div>
                        <p v-if="!primarioValido" class="mt-2 text-xs font-semibold text-danger">
                            Escribe un color en formato #RRGGBB.
                        </p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold" for="brand_secondary">
                            Color secundario
                        </label>
                        <div class="flex items-center gap-3">
                            <input
                                id="brand_secondary"
                                v-model="form.brand_secondary"
                                type="color"
                                class="h-10 w-14 cursor-pointer rounded border border-[#ebedf2] bg-white p-1 dark:border-[#191e3a] dark:bg-[#0e1726]"
                            />
                            <input
                                :value="form.brand_secondary"
                                type="text"
                                class="form-input"
                                maxlength="7"
                                spellcheck="false"
                                @input="form.brand_secondary = $event.target.value.toLowerCase()"
                            />
                        </div>
                        <p v-if="!secundarioValido" class="mt-2 text-xs font-semibold text-danger">
                            Escribe un color en formato #RRGGBB.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        class="btn btn-primary"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="!puedeGuardar"
                        @click="guardar"
                    >
                        Guardar colores
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        @click="restaurarFabrica"
                    >
                        Restaurar de fábrica
                    </button>
                </div>

                <p
                    v-if="form.errors.brand_primary || form.errors.brand_secondary"
                    class="mt-3 text-sm font-semibold text-danger"
                >
                    {{ form.errors.brand_primary || form.errors.brand_secondary }}
                </p>
            </div>

            <div class="panel">
                <h5 class="mb-1 text-lg font-semibold dark:text-white-light">Vista previa</h5>
                <p class="mb-5 text-sm text-white-dark">
                    Así se verán los controles del sistema con estos colores. La
                    tarjeta es solo una muestra: al guardar, la marca se aplica a
                    todo el ERP.
                </p>

                <div
                    class="brand-vista-previa rounded-md border border-dashed border-[#ebedf2] p-4 dark:border-[#191e3a]"
                    :style="variablesVistaPrevia"
                >
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" class="btn btn-primary">Guardar cambios</button>
                        <button type="button" class="btn btn-outline-primary">Cancelar</button>
                        <button type="button" class="btn btn-secondary">Secundario</button>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <span class="badge bg-primary">Nuevo</span>
                        <span class="badge badge-outline-primary">Pendiente</span>
                        <input
                            type="text"
                            class="form-input max-w-[12rem]"
                            placeholder="Campo de texto"
                        />
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <span class="text-sm font-semibold text-primary">Enlace primario</span>
                        <span class="text-sm font-semibold text-secondary">Enlace secundario</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
