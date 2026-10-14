<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import Swal2 from 'sweetalert2';
    import { faArrowLeft, faBug, faCopy, faTrash } from '@fortawesome/free-solid-svg-icons';
    import axios from 'axios';
    import { computed, ref } from 'vue';

    const props = defineProps({
        error_log: { type: Object, required: true },
        canDelete: { type: Boolean, default: false },
    });

    const toast = Swal2.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: 3500,
        padding: '2em',
        customClass: { container: 'toast' },
    });

    const deleting = ref(false);
    const showRaw = ref(false);

    const log = computed(() => props.error_log);

    /** Contexto listo para mostrar: el JSON decodificado o el texto tal cual. */
    const contextText = computed(() => {
        if (log.value.context === null || log.value.context === undefined) {
            return '';
        }

        if (typeof log.value.context === 'string') {
            return log.value.context;
        }

        return JSON.stringify(log.value.context, null, 2);
    });

    const logContext = computed(() => (log.value.context && typeof log.value.context === 'object' ? log.value.context.log : null));
    const requestContext = computed(() => (log.value.context && typeof log.value.context === 'object' ? log.value.context.request : null));

    const requestText = computed(() => (requestContext.value ? JSON.stringify(requestContext.value, null, 2) : ''));
    const logDataText = computed(() => (logContext.value ? JSON.stringify(logContext.value, null, 2) : ''));

    /** Volcado completo en texto, para pegarlo en el chat o en un correo. */
    const fullDump = () => {
        const lines = [
            `Error ${log.value.reference} · ${log.value.level}`,
            `Fecha: ${log.value.created_full}`,
            `Mensaje: ${log.value.message}`,
            `Excepción: ${log.value.exception_class || '—'}`,
            `Archivo: ${log.value.origin || '—'}`,
            `Entorno: ${log.value.environment || '—'}`,
            `Petición: ${log.value.method || ''} ${log.value.url || ''}`,
            `Usuario: ${log.value.user_label || '—'} (id ${log.value.user_id ?? '—'})`,
            `IP: ${log.value.ip || '—'}`,
            `Navegador: ${log.value.user_agent || '—'}`,
            `Repeticiones: ${log.value.occurrences}`,
            `Última vez: ${log.value.last_seen_at || '—'}`,
            '',
            'Traza:',
            log.value.trace || '(sin traza)',
            '',
            'Contexto del log:',
            logDataText.value || '(sin contexto)',
            '',
            'Petición:',
            requestText.value || '(sin datos de la petición)',
        ];

        return lines.join('\n');
    };

    const copyContext = async () => {
        try {
            await navigator.clipboard.writeText(fullDump());
            toast.fire({ icon: 'success', title: 'Contexto copiado' });
        } catch (error) {
            Swal2.fire({
                title: 'No se pudo copiar',
                text: 'Selecciona el texto y cópialo manualmente.',
                icon: 'warning',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }
    };

    const removeLog = () => {
        Swal2.fire({
            title: '¿Eliminar este error?',
            text: 'Se borrará el error con toda su traza y contexto. Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '¡Sí, eliminar!',
            cancelButtonText: 'Cancelar',
            padding: '2em',
            customClass: 'sweet-alerts',
            allowOutsideClick: () => !Swal2.isLoading(),
        }).then(async (result) => {
            if (!result.isConfirmed) return;

            deleting.value = true;

            try {
                await axios.delete(route('security_errors_destroy', log.value.id));
                window.location.href = route('security_errors');
            } catch (error) {
                const message = error?.response?.data?.message || 'No se pudo eliminar el error.';

                Swal2.fire({
                    title: 'Error',
                    text: message,
                    icon: 'error',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            } finally {
                deleting.value = false;
            }
        });
    };

    const levelClass = (level) => {
        const value = String(level || '').toLowerCase();

        if (value === 'emergency' || value === 'alert' || value === 'critical') {
            return 'bg-danger/10 text-danger';
        }
        if (value === 'error') {
            return 'bg-warning/10 text-warning';
        }
        return 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
    };
</script>

<template>
    <AppLayout :title="`Error ${log.reference}`">
        <Navigation
            :routeModule="route('security_dashboard')"
            :titleModule="'Seguridad'"
            :data="[{ title: 'Errores', route: route('security_errors') }, { title: log.reference }]"
        />

        <div class="mt-5 space-y-5">
            <div class="panel p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-800 dark:text-white">
                            <font-awesome-icon :icon="faBug" class="text-primary" />
                            Error {{ log.reference }}
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="levelClass(log.level)">
                                {{ log.level }}
                            </span>
                        </h2>
                        <p class="mt-1 max-w-3xl break-words text-sm text-gray-700 dark:text-gray-200">{{ log.message }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ log.created_full }} · {{ log.occurrences }} repetición(es) · última vez {{ log.last_seen_at || '—' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a :href="route('security_errors')" class="btn btn-outline-secondary">
                            <font-awesome-icon :icon="faArrowLeft" class="mr-1" /> Volver
                        </a>
                        <button type="button" class="btn btn-outline-primary" @click="copyContext">
                            <font-awesome-icon :icon="faCopy" class="mr-1" /> Copiar contexto
                        </button>
                        <button
                            v-if="canDelete"
                            type="button"
                            class="btn btn-outline-danger"
                            :disabled="deleting"
                            @click="removeLog"
                        >
                            <IconLoader v-if="deleting" class="mr-2 h-4 w-4" />
                            <font-awesome-icon v-else :icon="faTrash" class="mr-1" />
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>

            <div class="panel p-5">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white">Datos del error</h3>
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <span class="block text-xs uppercase text-gray-500 dark:text-gray-400">Excepción</span>
                        <span class="mt-0.5 block break-all text-sm text-gray-800 dark:text-gray-100">{{ log.exception_class || '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs uppercase text-gray-500 dark:text-gray-400">Archivo y línea</span>
                        <span class="mt-0.5 block break-all font-mono text-sm text-gray-800 dark:text-gray-100">{{ log.origin || '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs uppercase text-gray-500 dark:text-gray-400">Petición</span>
                        <span class="mt-0.5 block break-all text-sm text-gray-800 dark:text-gray-100">
                            {{ log.method || '' }} {{ log.url || '—' }}
                        </span>
                    </div>
                    <div>
                        <span class="block text-xs uppercase text-gray-500 dark:text-gray-400">Entorno</span>
                        <span class="mt-0.5 block text-sm text-gray-800 dark:text-gray-100">{{ log.environment || '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs uppercase text-gray-500 dark:text-gray-400">Usuario</span>
                        <span class="mt-0.5 block text-sm text-gray-800 dark:text-gray-100">
                            {{ log.user_label || '—' }}
                            <span v-if="log.user_id" class="text-gray-500 dark:text-gray-400">(id {{ log.user_id }})</span>
                        </span>
                    </div>
                    <div>
                        <span class="block text-xs uppercase text-gray-500 dark:text-gray-400">IP</span>
                        <span class="mt-0.5 block text-sm text-gray-800 dark:text-gray-100">{{ log.ip || '—' }}</span>
                    </div>
                    <div class="md:col-span-2">
                        <span class="block text-xs uppercase text-gray-500 dark:text-gray-400">Navegador</span>
                        <span class="mt-0.5 block break-all text-sm text-gray-800 dark:text-gray-100">{{ log.user_agent || '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="panel p-5">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white">Traza</h3>
                <pre class="mt-3 max-h-96 overflow-auto whitespace-pre-wrap rounded-lg bg-gray-50 p-4 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ log.trace || 'Sin traza registrada.' }}</pre>
            </div>

            <div class="panel p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">Contexto del log</h3>
                    <button
                        v-if="typeof log.context === 'string'"
                        type="button"
                        class="btn btn-outline-secondary"
                        @click="showRaw = !showRaw"
                    >
                        {{ showRaw ? 'Ver formateado' : 'Ver tal cual' }}
                    </button>
                </div>
                <pre v-if="logDataText && !showRaw" class="mt-3 max-h-96 overflow-auto whitespace-pre-wrap rounded-lg bg-gray-50 p-4 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ logDataText }}</pre>
                <pre v-else-if="showRaw" class="mt-3 max-h-96 overflow-auto whitespace-pre-wrap rounded-lg bg-gray-50 p-4 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ log.context_raw }}</pre>
                <p v-else-if="!logDataText" class="mt-3 text-sm text-gray-500 dark:text-gray-400">Este error no guardó contexto adicional.</p>
            </div>

            <div class="panel p-5">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white">Datos de la petición</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Los campos sensibles (contraseñas, tokens, cookies) se guardan ocultos.
                </p>
                <pre v-if="requestText" class="mt-3 max-h-96 overflow-auto whitespace-pre-wrap rounded-lg bg-gray-50 p-4 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ requestText }}</pre>
                <p v-else class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    El error no vino de una petición web (o no se pudo leer sus datos).
                </p>
            </div>
        </div>
    </AppLayout>
</template>
