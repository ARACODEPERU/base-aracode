<script setup>
/**
 * Guía de la credencial OAuth 2.0 de Google Calendar.
 *
 * Es el contenido del modal que abre el botón "?" de los parámetros
 * confidenciales (SC-00011 Client ID, SC-00012 Client Secret y SC-00013
 * refresh token): explica de dónde salen esos datos y, sobre todo, qué poner
 * en "URIs de redireccionamiento autorizados" y en "Orígenes autorizados de
 * JavaScript", para no tener que preguntarlo cada vez.
 *
 * La URL de callback se calcula en vivo con route(), así que sigue siendo la
 * correcta aunque cambie APP_URL. No depende del backend.
 */
import { computed, ref } from 'vue';

const props = defineProps({
    // Código del parámetro que abrió la guía (para resaltar lo que se llena).
    parameterCode: {
        type: String,
        default: '',
    },
});

const LABELS = {
    'SC-00011': 'Client ID (SC-00011)',
    'SC-00012': 'Client Secret (SC-00012)',
    'SC-00013': 'refresh token (SC-00013)',
};

const parameterLabel = computed(() => LABELS[props.parameterCode] ?? null);

// URL exacta que el sistema envía a Google como redirect_uri.
const callbackUrl = computed(() => {
    try {
        return route('heal_google_calendar_callback');
    } catch (error) {
        return '';
    }
});

// Google solo exime de HTTPS a localhost: una URL http:// sobre un dominio no
// se puede registrar tal cual en la consola.
const httpWarning = computed(() => {
    const url = String(callbackUrl.value ?? '');

    return url.startsWith('http://') && !/^http:\/\/(localhost|127\.0\.0\.1)/.test(url);
});

const copied = ref(false);

const copyCallbackUrl = async () => {
    try {
        await navigator.clipboard.writeText(callbackUrl.value);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2500);
    } catch (error) {
        copied.value = false;
    }
};
</script>

<template>
    <div class="flex flex-col gap-5 text-sm text-gray-700 dark:text-white-light">
        <p v-if="parameterLabel" class="rounded bg-primary/10 px-3 py-2 text-primary">
            Estás completando el <strong>{{ parameterLabel }}</strong>.
        </p>

        <p>
            Es la credencial OAuth 2.0 del sistema ante Google. Se registra <strong>una sola vez</strong>: después,
            cualquiera que administre la clínica solo pulsa «Continuar con Google» en Salud → Google Calendar.
        </p>

        <!-- 1. De dónde salen los datos -->
        <div>
            <h4 class="mb-2 font-semibold text-gray-800 dark:text-white">1. Crea la credencial en Google Cloud Console</h4>
            <ol class="list-decimal space-y-1 pl-5">
                <li>
                    Entra a <strong>console.cloud.google.com</strong> con la cuenta de Google del consultorio y crea (o
                    elige) un proyecto.
                </li>
                <li>
                    <strong>APIs y servicios → Biblioteca</strong>: busca <strong>Google Calendar API</strong> y pulsa
                    «Habilitar».
                </li>
                <li>
                    <strong>APIs y servicios → Credenciales → Crear credenciales → ID de cliente de OAuth</strong>.
                </li>
                <li>
                    Tipo de aplicación: <strong>Aplicación web</strong>. Ponle un nombre, por ejemplo «Agenda Salud».
                </li>
                <li>
                    Completa los dos campos que se explican abajo y pulsa <strong>Guardar</strong>. Google te mostrará el
                    <strong>Client ID</strong> y el <strong>Client Secret</strong>.
                </li>
            </ol>

            <p class="mt-2 text-xs text-gray-500">
                Cómo reconocerlos: el <strong>Client ID</strong> termina en
                <code>.apps.googleusercontent.com</code> y el <strong>Client Secret</strong> empieza con
                <code>GOCSPX-</code>.
            </p>
        </div>

        <!-- 2. Qué poner en cada campo de Google -->
        <div>
            <h4 class="mb-2 font-semibold text-gray-800 dark:text-white">2. Qué poner en cada campo de Google</h4>

            <div class="flex flex-col gap-3">
                <div class="rounded border border-gray-200 p-3 dark:border-gray-600">
                    <span class="block font-semibold text-gray-800 dark:text-white">
                        URIs de redireccionamiento autorizados
                    </span>
                    <p class="mt-1">
                        Aquí va, <strong>tal cual</strong>, esta dirección: es la que el sistema usa para recibir el
                        permiso de Google.
                    </p>
                    <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
                        <code class="break-all rounded bg-gray-100 px-2 py-1 text-xs dark:bg-gray-800">{{ callbackUrl || '—' }}</code>
                        <button
                            type="button"
                            class="whitespace-nowrap rounded bg-blue-900 px-3 py-1.5 text-xs font-medium uppercase text-white hover:bg-blue-700"
                            @click="copyCallbackUrl"
                        >
                            {{ copied ? '¡Copiado!' : 'Copiar' }}
                        </button>
                    </div>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-gray-500">
                        <li>Sin barra final y con el mismo <code>https</code> y el mismo <code>www</code> del dominio desde el que abres el sistema.</li>
                        <li>Si el sistema se abre con más de un dominio (con y sin <code>www</code>, o local y producción), agrega una URI por cada uno con «Agregar URI».</li>
                        <li>Google <strong>exige HTTPS</strong>: solo admite <code>http://</code> en <code>localhost</code> y <code>127.0.0.1</code>.</li>
                        <li v-if="httpWarning">
                            Tu sistema está configurado en una dirección <code>http://</code> que Google no va a aceptar
                            tal cual. Para probar en local usa un túnel HTTPS y ajusta <code>APP_URL</code> a esa
                            dirección (el mismo túnel sirve para las notificaciones push con
                            <code>HEALTH_GOOGLE_WEBHOOK_URL</code>); o conecta directamente desde el sistema publicado.
                        </li>
                    </ul>
                </div>

                <div class="rounded border border-gray-200 p-3 dark:border-gray-600">
                    <span class="block font-semibold text-gray-800 dark:text-white">
                        Orígenes autorizados de JavaScript
                    </span>
                    <p class="mt-1">
                        <strong>Déjalo vacío</strong> (no hace falta agregar nada). Ese campo es solo para aplicaciones
                        que piden el permiso desde el navegador, como «Iniciar sesión con Google»; este sistema lo pide
                        desde el servidor, así que no lo usa.
                    </p>
                </div>

                <div class="rounded border border-gray-200 p-3 dark:border-gray-600">
                    <span class="block font-semibold text-gray-800 dark:text-white">
                        Use this client for an AI-powered agent
                    </span>
                    <p class="mt-1">Sin marcar. No aplica a esta integración.</p>
                </div>
            </div>
        </div>

        <!-- 3. Después de guardar -->
        <div>
            <h4 class="mb-2 font-semibold text-gray-800 dark:text-white">3. Después de guardar en Google</h4>
            <ol class="list-decimal space-y-1 pl-5">
                <li>
                    Registra aquí el <strong>Client ID</strong> en <code>SC-00011</code> y el
                    <strong>Client Secret</strong> en <code>SC-00012</code>. Se guardan y
                    <strong>no se vuelven a mostrar</strong>; si necesitas reemplazarlos, escribe el valor nuevo.
                </li>
                <li>
                    El <strong>refresh token</strong> (<code>SC-00013</code>) <strong>no se llena a mano</strong>: lo
                    guarda el sistema cuando pulsas «Continuar con Google» en Salud → Google Calendar y aceptas los
                    permisos del calendario.
                </li>
                <li>
                    Si la pantalla de consentimiento de Google queda en modo <strong>Prueba</strong>, el permiso caduca
                    <strong>a los 7 días</strong>. Publícala (<em>En producción</em>) o usa una aplicación
                    <em>Interna</em> de Workspace para conectarla una sola vez.
                </li>
                <li>
                    Si algo no funciona, en Salud → Google Calendar pulsa <strong>«Probar conexión»</strong>: te dice qué
                    corregir (credenciales, permiso de la API, URL de callback o el correo de la cuenta).
                </li>
            </ol>
        </div>
    </div>
</template>
