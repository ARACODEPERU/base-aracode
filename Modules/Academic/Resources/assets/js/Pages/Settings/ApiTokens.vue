<script setup>
import { computed, ref } from 'vue';
import axios from 'axios';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

/**
 * Tokens de API del servidor MCP del Académico.
 *
 * El token se muestra una sola vez al crearlo: no se guarda en claro. Con él
 * un agente (Claude Code, Claude Desktop, Freebuff) puede crear cursos, que
 * siempre quedan en borrador hasta que una persona los activa en el ERP.
 */
const props = defineProps({
    tokens: { type: Array, default: () => [] },
    abilities: { type: Array, default: () => [] },
    mcpUrl: { type: String, default: '' },
    storeUrl: { type: String, default: '' },
    revokeUrl: { type: String, default: '' },
});

const form = useForm({
    name: '',
    abilities: ['academic:read'],
});

const saving = ref(false);
const nuevoToken = ref('');

const descripciones = {
    'academic:read': 'Consultar cursos, categorías, modalidades y docentes.',
    'academic:write': 'Crear cursos en borrador, con módulos, clases y contenidos.',
    'academic:write_active': 'Editar cursos activos y landings con confirmación. No cambia precios ni elimina cursos.',
    'academic:autonomous': 'Permiso absoluto para borradores: crear y editar sin confirmaciones del servidor. Requiere escritura.',
};

// Se muestran dentro de un desplegable por permiso para no perder el orden
// ni saturar la tarjeta cuando hay varios permisos seleccionados.
const detallesPermisos = {
    'academic:read': [
        'Leer cursos, categorías, modalidades, tipos, sectores y docentes reales.',
        'Consultar la ficha de un curso y su landing.',
        'Revisar estudiantes, suscripciones, vencimientos, avance y métricas.',
        'No crea, edita ni elimina datos.',
    ],
    'academic:write': [
        'Crear cursos nuevos; siempre quedan en estado borrador.',
        'Agregar módulos, clases, contenidos, archivos, enlaces y exámenes.',
        'Editar o borrar los borradores creados por el agente.',
        'Preparar la landing y el brochure del borrador.',
        'No activa el curso ni edita cursos que ya están activos.',
    ],
    'academic:write_active': [
        'Requiere también academic:read para revisar el estado actual antes de cambiar.',
        'Editar datos no financieros del curso: nombre, categoría, modalidad, sector, fecha, docente y certificado.',
        'Reemplazar texto, documentos y enlaces; convertir una videoconferencia en video o en otro tipo de enlace permitido.',
        'Actualizar secciones de la landing: profesional, staff, resultados, testimonios, plan, problema, FAQ, UTM y banner.',
        'Reemplazar o quitar solo la imagen de portada; reemplazar solo el PDF del brochure.',
        'Cada cambio muestra un antes/después y exige confirmación con un motivo.',
        'No cambia precios, descuentos, tipo de curso, estado, slug, publicación ni la inversión de la landing.',
        'No elimina cursos, módulos, clases, contenidos, exámenes ni documentos.',
    ],
    'academic:autonomous': [
        'Omite la confirmación del servidor en las herramientas de borrador.',
        'Requiere academic:write; no funciona por sí solo.',
        'No aplica a cursos activos ni a landings de cursos activos.',
        'Borrar y activar siguen requiriendo una decisión explícita.',
    ],
};

const tieneEscritura = computed(() => form.abilities.includes('academic:write'));
const tieneActivos = computed(() => form.abilities.includes('academic:write_active'));
const tieneAutonomia = computed(() => form.abilities.includes('academic:autonomous'));

function alternarAbility(ability) {
    if (form.abilities.includes(ability)) {
        form.abilities = form.abilities.filter((item) => {
            // Quitar escritura también quita el permiso absoluto: sin escritura
            // no puede crear ni editar nada.
            if (ability === 'academic:write' && item === 'academic:autonomous') {
                return false;
            }

            // La edición de activos necesita leer el estado actual antes de
            // mostrar el antes/después al usuario.
            if (ability === 'academic:read' && item === 'academic:write_active') {
                return false;
            }

            return item !== ability;
        });

        return;
    }

    if (ability === 'academic:write_active' && !form.abilities.includes('academic:read')) {
        form.abilities = [...form.abilities, 'academic:read', 'academic:write_active'];

        return;
    }

    if (ability === 'academic:autonomous' && !form.abilities.includes('academic:write')) {
        form.abilities = [...form.abilities, 'academic:write', 'academic:autonomous'];

        return;
    }

    form.abilities = [...form.abilities, ability];
}

async function crearToken() {
    if (!form.name.trim()) {
        window.alertaSistema('Falta el nombre', 'Escribe un nombre para reconocer el token después.', 'warning');
        return;
    }

    if (form.abilities.length === 0) {
        window.alertaSistema('Falta un permiso', 'Elige al menos un permiso para el token.', 'warning');
        return;
    }

    saving.value = true;

    try {
        const { data } = await axios.post(props.storeUrl, {
            name: form.name.trim(),
            abilities: form.abilities,
        });

        nuevoToken.value = data.token;
        form.reset('name');
        window.alertaSistema('Token creado', data.message || 'Cópialo ahora: no se vuelve a mostrar.', 'success');
    } catch (error) {
        const mensaje = error?.response?.data?.message
            || Object.values(error?.response?.data?.errors || {})?.[0]?.[0]
            || 'No se pudo crear el token.';
        window.alertaSistema('No se pudo crear', mensaje, 'error');
    } finally {
        saving.value = false;
    }
}

async function copiarToken() {
    try {
        await navigator.clipboard.writeText(nuevoToken.value);
        window.alertaSistema('Copiado', 'El token está en el portapapeles.', 'success');
    } catch (error) {
        window.alertaSistema('Copia manual', 'Selecciona el token y cópialo con Ctrl+C.', 'info');
    }
}

async function revocar(token) {
    const confirmacion = await window.alertaSistema(
        'Revocar token',
        `El agente que use «${token.name}» dejará de poder conectarse. Esta acción no se puede deshacer.`,
        'warning',
        {
            duration: 0,
            showCancelButton: true,
            confirmButtonText: 'Sí, revocar',
            cancelButtonText: 'Cancelar',
        }
    );

    if (!confirmacion) {
        return;
    }

    try {
        const { data } = await axios.delete(`${props.revokeUrl}/${token.id}`);
        window.alertaSistema('Token revocado', data.message || 'El token ya no sirve.', 'success');

        // La lista se recarga desde el servidor para no mostrar un token muerto.
        window.location.reload();
    } catch (error) {
        window.alertaSistema('No se pudo revocar', error?.response?.data?.message || 'Inténtalo de nuevo.', 'error');
    }
}
</script>

<template>
    <AppLayout title="Tokens de API para IA">
        <Head>
            <meta name="aracodito-view" content="new" />
        </Head>

        <Navigation
            :routeModule="route('aca_dashboard')"
            :titleModule="'Académico'"
            :data="[{ title: 'Tokens de API' }]"
        />

        <div class="mt-5 flex flex-col gap-6">
            <div class="panel">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h5 class="text-lg font-semibold dark:text-white-light">Conectar un agente de IA</h5>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            Con un token, Claude Code, Claude Desktop o Freebuff pueden crear cursos usando el mismo
                            catálogo y las mismas reglas que este formulario. Todo curso queda en
                            <strong>borrador</strong>: activarlo sigue siendo una decisión humana.
                        </p>
                    </div>
                    <div class="rounded border border-gray-200 px-3 py-2 text-xs dark:border-gray-700">
                        <span class="block uppercase text-gray-400">URL del servidor</span>
                        <code class="text-sm text-primary">{{ mcpUrl }}</code>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="rounded border border-gray-200 p-4 dark:border-gray-700">
                        <h6 class="text-sm font-semibold dark:text-white-light">1. Nombre del token</h6>
                        <p class="mt-1 text-xs text-gray-500">Por ejemplo «Claude Code – laptop», para saber cuál revocar.</p>
                        <input
                            v-model="form.name"
                            type="text"
                            maxlength="80"
                            class="form-input mt-3"
                            placeholder="Claude Code – laptop"
                        />
                    </div>

                    <div class="rounded border border-gray-200 p-4 dark:border-gray-700">
                        <h6 class="text-sm font-semibold dark:text-white-light">2. Permisos</h6>
                        <div class="mt-3 flex flex-col gap-2">
                            <div
                                v-for="(ability, indice) in abilities"
                                :key="ability"
                                class="rounded border border-gray-200 p-2.5 dark:border-gray-700"
                            >
                                <div class="flex items-start gap-2 text-sm">
                                    <input
                                        :id="`ability-${indice}`"
                                        type="checkbox"
                                        class="form-checkbox mt-0.5"
                                        :checked="form.abilities.includes(ability)"
                                        @change="alternarAbility(ability)"
                                    />
                                    <div class="min-w-0">
                                        <label :for="`ability-${indice}`" class="cursor-pointer">
                                            <code class="text-xs text-primary">{{ ability }}</code>
                                        </label>
                                        <span class="mt-0.5 block text-xs text-gray-500">{{ descripciones[ability] }}</span>
                                    </div>
                                </div>

                                <details class="mt-2 border-t border-gray-100 pt-2 text-xs text-gray-500 dark:border-gray-700">
                                    <summary class="cursor-pointer select-none font-medium text-primary">
                                        Ver qué puede hacer
                                    </summary>
                                    <ul class="mt-2 list-disc space-y-1 pl-4">
                                        <li v-for="aviso in (detallesPermisos[ability] || [])" :key="aviso">
                                            {{ aviso }}
                                        </li>
                                    </ul>
                                </details>
                            </div>
                        </div>

                        <p v-if="tieneEscritura" class="mt-3 text-xs text-gray-500">
                            El agente podrá crear cursos en borrador, módulos, clases, contenidos y exámenes,
                            y publicar landings mientras el curso siga en borrador. No puede activar el curso.
                        </p>

                        <p v-if="tieneActivos" class="mt-3 rounded border border-warning/40 bg-warning/10 p-3 text-xs text-warning">
                            <strong>Edición de activos.</strong> El agente podrá cambiar datos no financieros,
                            contenidos, enlaces, archivos, imágenes y secciones de landings de cursos que ya tienen
                            alumnos, siempre mostrando un antes/después y pidiendo confirmación. No puede cambiar
                            precios, descuentos, tipo de curso, estado, slug ni publicación, y no puede eliminar
                            cursos, módulos, clases, contenidos, exámenes ni documentos. Una portada sí se puede
                            reemplazar o quitar.
                        </p>

                        <p v-if="tieneAutonomia" class="mt-3 rounded border border-warning/40 bg-warning/10 p-3 text-xs text-warning">
                            <strong>Permiso absoluto solo para borradores.</strong> El agente trabajará por lotes sin
                            pedirte confirmación al servidor al crear o editar borradores. En cursos activos y
                            landings siempre se pedirá confirmación y motivo. Riesgos: puede crear hasta 10 cursos
                            por hora, subir archivos, cambiar textos y publicar landings de borradores. Todo queda
                            auditado; borrar y activar siguen requiriendo una decisión explícita. Tu cliente MCP
                            puede seguir mostrando sus propios avisos.
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-gray-500">El token vence a los 90 días. Se muestra una sola vez.</p>
                    <button type="button" class="btn btn-primary" :disabled="saving" @click="crearToken">
                        {{ saving ? 'Creando…' : 'Crear token' }}
                    </button>
                </div>

                <div v-if="nuevoToken" class="mt-4 rounded border border-success/40 bg-success/10 p-4">
                    <p class="text-sm font-semibold text-success">Copia el token ahora</p>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                        No se vuelve a mostrar. Guárdalo en el cliente MCP, no en el navegador.
                    </p>
                    <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                        <input :value="nuevoToken" readonly class="form-input font-mono text-xs" />
                        <button type="button" class="btn btn-outline-primary" @click="copiarToken">Copiar</button>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="flex items-center justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">Tokens activos</h5>
                    <span class="badge bg-primary">{{ tokens.length }}</span>
                </div>

                <div v-if="tokens.length === 0" class="mt-6 rounded border border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                    <p class="text-sm font-semibold dark:text-white-light">Todavía no hay tokens</p>
                    <p class="mt-1 text-xs text-gray-500">Crea el primero con el formulario de arriba.</p>
                </div>

                <div v-else class="mt-4 overflow-x-auto">
                    <table class="table-hover w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Nombre</th>
                                <th class="text-left">Permisos</th>
                                <th class="text-left">Creado</th>
                                <th class="text-left">Último uso</th>
                                <th class="text-left">Vence</th>
                                <th class="text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="token in tokens" :key="token.id">
                                <td class="font-semibold dark:text-white-light">{{ token.name }}</td>
                                <td>
                                    <span
                                        v-for="ability in token.abilities"
                                        :key="ability"
                                        class="badge mr-1 bg-primary/10 text-primary"
                                    >{{ ability }}</span>
                                </td>
                                <td class="text-gray-500">{{ token.created_at }}</td>
                                <td class="text-gray-500">{{ token.last_used_at || 'Sin usar' }}</td>
                                <td class="text-gray-500">{{ token.expires_at || 'Sin vencimiento' }}</td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-sm btn-outline-danger" @click="revocar(token)">
                                        Revocar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
