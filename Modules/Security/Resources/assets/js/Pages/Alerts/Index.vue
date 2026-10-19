<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import Swal2 from 'sweetalert2';
    import { faBell, faPaperPlane, faPencil, faPlus, faTrash, faXmark } from '@fortawesome/free-solid-svg-icons';
    import axios from 'axios';
    import { ref } from 'vue';

    const props = defineProps({
        settings: {
            type: Object,
            default: () => ({
                enabled: true,
                min_level: 'error',
                cooldown_minutes: 5,
                store_context: true,
                retention_days: 30,
                purge_enabled: true,
            }),
        },
        levels: { type: Array, default: () => [] },
        // Interruptores por tipo de aviso, con la etiqueta que arma el backend.
        topics: { type: Array, default: () => [] },
        logStats: { type: Object, default: () => ({ total: 0, size_bytes: 0, oldest_at: null, newest_at: null }) },
        recipients: { type: Array, default: () => [] },
        botConfigured: { type: Boolean, default: false },
        preview: { type: String, default: '' },
    });

    const toast = Swal2.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: 3500,
        padding: '2em',
        customClass: { container: 'toast' },
    });

    const settingsForm = ref({
        enabled: props.settings.enabled,
        min_level: props.settings.min_level,
        cooldown_minutes: props.settings.cooldown_minutes,
        store_context: props.settings.store_context,
        retention_days: props.settings.retention_days,
        purge_enabled: props.settings.purge_enabled,
    });

    // Copia local de los tipos de aviso: se guardan junto con los demás ajustes.
    const topicList = ref(props.topics.map((topic) => ({ ...topic })));

    const logStats = ref({ ...props.logStats });

    const recipientList = ref([...props.recipients]);
    const preview = ref(props.preview);

    const form = ref({ id: null, name: '', chat_id: '', is_active: true });
    const showForm = ref(false);
    const savingSettings = ref(false);
    const savingRecipient = ref(false);
    const sendingTest = ref(false);
    const togglingId = ref(null);

    const errorText = (error, fallback = 'Ocurrió un error inesperado.') => {
        const data = error?.response?.data;
        if (!data) return fallback;
        if (data.errors) {
            const first = Object.values(data.errors)[0];
            return Array.isArray(first) ? first[0] : first;
        }
        return data.message || fallback;
    };

    const saveSettings = async () => {
        savingSettings.value = true;
        try {
            const payload = {
                ...settingsForm.value,
                topics: Object.fromEntries(topicList.value.map((topic) => [topic.key, topic.enabled])),
            };

            const { data } = await axios.put(route('security_alerts_settings_update'), payload);
            settingsForm.value = { ...data.settings };
            topicList.value = (data.topics || []).map((topic) => ({ ...topic }));
            logStats.value = { ...(data.logStats || logStats.value) };
            preview.value = data.preview;
            toast.fire({ icon: 'success', title: data.message || 'Ajustes guardados' });
        } catch (error) {
            Swal2.fire({ title: 'Error', text: errorText(error, 'No se pudieron guardar los ajustes.'), icon: 'error', padding: '2em', customClass: 'sweet-alerts' });
        } finally {
            savingSettings.value = false;
        }
    };

    const openCreate = () => {
        form.value = { id: null, name: '', chat_id: '', is_active: true };
        showForm.value = true;
    };

    const openEdit = (recipient) => {
        form.value = { id: recipient.id, name: recipient.name || '', chat_id: recipient.chat_id, is_active: recipient.is_active };
        showForm.value = true;
    };

    const cancelForm = () => {
        showForm.value = false;
        form.value = { id: null, name: '', chat_id: '', is_active: true };
    };

    const saveRecipient = async () => {
        savingRecipient.value = true;
        try {
            const payload = { name: form.value.name || null, chat_id: form.value.chat_id, is_active: form.value.is_active };

            const { data } = form.value.id
                ? await axios.put(route('security_alerts_recipients_update', form.value.id), payload)
                : await axios.post(route('security_alerts_recipients_store'), payload);

            if (form.value.id) {
                recipientList.value = recipientList.value.map((item) => (item.id === data.recipient.id ? data.recipient : item));
            } else {
                recipientList.value = [...recipientList.value, data.recipient];
            }

            toast.fire({ icon: 'success', title: data.message || 'Destinatario guardado' });
            cancelForm();
        } catch (error) {
            Swal2.fire({ title: 'Error', text: errorText(error, 'No se pudo guardar el destinatario.'), icon: 'error', padding: '2em', customClass: 'sweet-alerts' });
        } finally {
            savingRecipient.value = false;
        }
    };

    const toggleActive = async (recipient) => {
        togglingId.value = recipient.id;
        try {
            const { data } = await axios.put(route('security_alerts_recipients_update', recipient.id), {
                name: recipient.name || null,
                chat_id: recipient.chat_id,
                is_active: !recipient.is_active,
            });
            recipientList.value = recipientList.value.map((item) => (item.id === data.recipient.id ? data.recipient : item));
        } catch (error) {
            Swal2.fire({ title: 'Error', text: errorText(error, 'No se pudo actualizar el destinatario.'), icon: 'error', padding: '2em', customClass: 'sweet-alerts' });
        } finally {
            togglingId.value = null;
        }
    };

    const removeRecipient = (recipient) => {
        Swal2.fire({
            title: '¿Eliminar destinatario?',
            text: `Dejará de recibir las alertas el chat ${recipient.chat_id}.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, eliminar!',
            cancelButtonText: 'Cancelar',
            padding: '2em',
            customClass: 'sweet-alerts',
            allowOutsideClick: () => !Swal2.isLoading(),
        }).then(async (result) => {
            if (!result.isConfirmed) return;

            try {
                await axios.delete(route('security_alerts_recipients_destroy', recipient.id));
                recipientList.value = recipientList.value.filter((item) => item.id !== recipient.id);
                toast.fire({ icon: 'success', title: 'Destinatario eliminado' });
            } catch (error) {
                Swal2.fire({ title: 'Error', text: errorText(error, 'No se pudo eliminar el destinatario.'), icon: 'error', padding: '2em', customClass: 'sweet-alerts' });
            }
        });
    };

    const sendTest = async () => {
        sendingTest.value = true;
        try {
            const { data } = await axios.post(route('security_alerts_test'));
            Swal2.fire({ title: 'Envío de prueba', text: data.message, icon: data.sent > 0 ? 'success' : 'warning', padding: '2em', customClass: 'sweet-alerts' });
        } catch (error) {
            Swal2.fire({ title: 'No se pudo enviar', text: errorText(error, 'Revisa la configuración del bot y los destinatarios.'), icon: 'error', padding: '2em', customClass: 'sweet-alerts' });
        } finally {
            sendingTest.value = false;
        }
    };
</script>

<template>
    <AppLayout title="Alertas">
        <Navigation
            :routeModule="route('security_dashboard')"
            :titleModule="'Seguridad'"
            :data="[{ title: 'Alertas' }]"
        />

        <div class="mt-5 space-y-5">
            <div class="panel p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-800 dark:text-white">
                            <font-awesome-icon :icon="faBell" class="text-primary" />
                            Avisos por Telegram
                        </h2>
                        <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                            Los avisos salen por la integración <span class="font-medium">Telegram_bot</span> hacia los chat_id
                            activos de esta lista, y cada tipo se activa por separado: errores del sistema y mensajes de
                            contacto del sitio.
                        </p>
                    </div>
                    <span
                        class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium"
                        :class="botConfigured ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
                    >
                        <span class="block h-2 w-2 rounded-full" :class="botConfigured ? 'bg-success' : 'bg-danger'"></span>
                        {{ botConfigured ? 'Bot configurado' : 'Falta el token del bot (SC-00002)' }}
                    </span>
                </div>
            </div>

            <div class="panel p-5">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white">Ajustes</h3>

                <div class="mt-4 rounded border border-gray-200 p-4 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white">Avisos que se envían</h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        El interruptor general apaga todos los avisos. Cada tipo se puede silenciar por separado sin borrar
                        destinatarios: al volver a activarlo, los mismos chat_id vuelven a recibirlos.
                    </p>

                    <label class="mt-3 flex cursor-pointer items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input v-model="settingsForm.enabled" type="checkbox" class="form-checkbox mt-0.5" />
                        <span>
                            Avisos activos (general)
                            <span class="block text-xs text-gray-500 dark:text-gray-400">
                                Si se apaga, no se envía ningún aviso por Telegram, sin importar los tipos activados abajo.
                            </span>
                        </span>
                    </label>

                    <div class="mt-3 space-y-2 border-t border-gray-200 pt-3 dark:border-gray-700">
                        <label
                            v-for="topic in topicList"
                            :key="topic.key"
                            class="flex cursor-pointer items-start gap-2 text-sm text-gray-700 dark:text-gray-300"
                            :class="settingsForm.enabled ? '' : 'opacity-50'"
                        >
                            <input
                                v-model="topic.enabled"
                                type="checkbox"
                                class="form-checkbox mt-0.5"
                                :disabled="!settingsForm.enabled"
                            />
                            <span>
                                {{ topic.label }}
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ topic.description }}</span>
                            </span>
                        </label>
                        <p v-if="!topicList.length" class="text-xs text-gray-500 dark:text-gray-400">
                            No hay tipos de aviso configurados.
                        </p>
                    </div>
                </div>

                <div class="mt-5 border-t border-gray-200 pt-5 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white">Detalles de las alertas de errores</h4>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nivel mínimo</label>
                        <select v-model="settingsForm.min_level" class="form-select w-full">
                            <option v-for="level in levels" :key="level" :value="level">{{ level }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Anti-duplicados (min)</label>
                        <input v-model.number="settingsForm.cooldown_minutes" type="number" min="0" max="1440" class="form-input w-full" />
                    </div>
                    </div>

                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Un mismo error (mismo nivel, mensaje, archivo y línea) no se repite dentro de la ventana indicada:
                        solo se suma al contador de repeticiones del error guardado, así que ocupa una única fila. Pasada la
                        ventana se avisa otra vez, con el total acumulado, para saber que el error sigue vivo sin llenar el
                        chat ni el disco. La línea genérica del planificador («failed with exit code») se omite cuando el
                        propio comando ya avisó del motivo real.
                    </p>
                </div>

                <div class="mt-5 border-t border-gray-200 pt-5 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white">Contexto de los errores</h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Además del resumen del chat, cada error se guarda completo (traza, contexto del log y datos de la
                        petición) y el mensaje de Telegram lleva el enlace para abrirlo. Así el diagnóstico no depende de
                        lo que quepa en el mensaje.
                    </p>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="flex items-center gap-3">
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input v-model="settingsForm.store_context" type="checkbox" class="form-checkbox" />
                                Guardar contexto y enlace
                            </label>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Retención (días)</label>
                            <input v-model.number="settingsForm.retention_days" type="number" min="0" max="3650" class="form-input w-full" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">0 = no borrar nunca. Se limpia todos los días a las 02:00.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input v-model="settingsForm.purge_enabled" type="checkbox" class="form-checkbox" />
                                Borrado automático de vencidos
                            </label>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-gray-600 dark:text-gray-300">
                        <span>
                            Guardados: <span class="font-medium">{{ logStats.total }}</span> error(es)
                            <span v-if="logStats.newest_at"> · último {{ logStats.newest_at }}</span>
                        </span>
                        <a :href="route('security_errors')" class="text-primary hover:underline">Ver errores guardados</a>
                    </div>
                </div>

                <div class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <button type="button" class="btn btn-primary" :disabled="savingSettings" @click="saveSettings">
                        <span v-if="savingSettings" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        Guardar ajustes
                    </button>
                </div>
            </div>

            <div class="panel p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">Destinatarios</h3>
                    <button type="button" class="btn btn-primary" @click="openCreate">
                        <font-awesome-icon :icon="faPlus" class="mr-1" /> Agregar destinatario
                    </button>
                </div>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Además de las alertas de error, estos chat_id reciben un aviso cuando alguien
                    envía el formulario de contacto del sitio.
                </p>

                <div v-if="showForm" class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre (opcional)</label>
                            <input v-model="form.name" type="text" class="form-input w-full" placeholder="Ej: Soporte técnico" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Chat ID *</label>
                            <input v-model="form.chat_id" type="text" class="form-input w-full" placeholder="Ej: 123456789 o -100123456789" />
                        </div>
                        <div class="flex items-center gap-2 pt-6">
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input v-model="form.is_active" type="checkbox" class="form-checkbox" />
                                Activo
                            </label>
                        </div>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button type="button" class="btn btn-primary" :disabled="savingRecipient" @click="saveRecipient">
                            {{ form.id ? 'Actualizar' : 'Agregar' }}
                        </button>
                        <button type="button" class="btn btn-outline-secondary" @click="cancelForm">
                            <font-awesome-icon :icon="faXmark" class="mr-1" /> Cancelar
                        </button>
                    </div>
                </div>

                <div class="table-responsive mt-4">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="min-w-[200px]">Nombre</th>
                                <th class="min-w-[180px]">Chat ID</th>
                                <th class="w-32 text-center">Estado</th>
                                <th class="w-40 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="recipientList.length === 0">
                                <td colspan="4" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Aún no hay destinatarios. Agrega el chat_id de la persona encargada.
                                </td>
                            </tr>
                            <tr v-for="recipient in recipientList" :key="recipient.id" class="hover:bg-gray-50/70 dark:hover:bg-gray-800/30">
                                <td class="font-medium text-gray-800 dark:text-gray-100">{{ recipient.name || '—' }}</td>
                                <td class="font-mono text-sm text-gray-600 dark:text-gray-300">{{ recipient.chat_id }}</td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
                                        :class="recipient.is_active ? 'bg-success/10 text-success' : 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-300'"
                                        :disabled="togglingId === recipient.id"
                                        @click="toggleActive(recipient)"
                                    >
                                        {{ recipient.is_active ? 'Activo' : 'Inactivo' }}
                                    </button>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="mr-1 inline-flex h-8 w-8 items-center justify-center rounded-lg border border-primary/40 text-primary hover:bg-primary/10" @click="openEdit(recipient)">
                                        <font-awesome-icon :icon="faPencil" />
                                    </button>
                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-danger/40 text-danger hover:bg-danger/10" @click="removeRecipient(recipient)">
                                        <font-awesome-icon :icon="faTrash" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">Vista previa y prueba</h3>
                    <button type="button" class="btn btn-primary" :disabled="sendingTest" @click="sendTest">
                        <span v-if="sendingTest" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        <font-awesome-icon v-else :icon="faPaperPlane" class="mr-1" />
                        Enviar alerta de prueba
                    </button>
                </div>
                <pre class="mt-4 max-h-80 overflow-auto whitespace-pre-wrap rounded-lg bg-gray-50 p-4 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ preview }}</pre>
            </div>
        </div>
    </AppLayout>
</template>
