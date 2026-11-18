<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
    import { computed, ref, watch } from 'vue';

    const props = defineProps({
        company: { type: Object, default: null },
        mode: { type: String, default: 'txt' },
        credentials: { type: Object, default: () => ({}) },
        credentialsReady: { type: Boolean, default: false },
        logs: { type: Array, default: () => [] },
        submissions: { type: Array, default: () => [] },
    });

    const page = usePage();

    const form = useForm({
        mode: props.mode,
        client_id: '',
        client_secret: '',
        sol_user: '',
        sol_key: '',
    });

    const probando = ref(false);

    const modoApi = computed(() => form.mode === 'api');

    const pistaClientId = computed(() =>
        props.credentials.client_id
            ? `Configurado: ${props.credentials.client_id} — deja vacío para conservar`
            : 'El client_id que genera SOL'
    );

    const pistaSecreto = (configurado, ejemplo) =>
        configurado ? 'Configurado — deja vacío para conservar' : ejemplo;

    const guardar = () => {
        form.post(route('sire_configuracion_update'), {
            preserveScroll: true,
            onSuccess: () => form.reset('client_id', 'client_secret', 'sol_user', 'sol_key'),
        });
    };

    const probar = async () => {
        probando.value = true;

        try {
            const { data } = await axios.post(route('sire_configuracion_probar'));

            window.alertaSistema('Conexión correcta', data.message, 'success');
        } catch (error) {
            window.alertaSistema(
                'No se pudo conectar',
                error?.response?.data?.message || 'Revisa las credenciales y la salida a internet del servidor.',
                'error'
            );
        } finally {
            probando.value = false;
        }
    };

    watch(
        () => page.props.flash?.message,
        (mensaje) => {
            if (mensaje) {
                window.alertaSistema('Listo', mensaje, 'success');
            }
        }
    );

    watch(
        () => page.props.flash?.error,
        (mensaje) => {
            if (mensaje) {
                window.alertaSistema('Atención', mensaje, 'error');
            }
        }
    );
</script>

<template>
    <AppLayout title="SIRE · Configuración API">
        <Head title="SIRE · Configuración API">
            <meta name="aracodito-view" content="new" />
        </Head>

        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Facturación Electrónica</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>SIRE · Configuración API</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div class="panel flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">SIRE por API</h2>
                    <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                        Credenciales que SUNAT entrega en SOL y modo de trabajo para el SIRE. Los cuatro valores se guardan
                        cifrados y nunca se muestran completos en pantalla.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="badge"
                        :class="modoApi ? 'bg-primary/10 text-primary' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                    >
                        Modo {{ modoApi ? 'API automática' : 'TXT / ZIP' }}
                    </span>
                    <span
                        class="badge"
                        :class="credentialsReady ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'"
                    >
                        {{ credentialsReady ? 'Credenciales completas' : 'Credenciales incompletas' }}
                    </span>
                </div>
            </div>

            <div class="panel border-l-4 border-warning p-5">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white">El SIRE no tiene ambiente de pruebas</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    SUNAT solo publica el host de producción. Esta fase es de <strong>solo lectura</strong>: obtiene el token y
                    descarga la propuesta del período, pero no acepta ni reemplaza registros. Las pruebas del flujo se hacen
                    con respuestas simuladas.
                </p>
            </div>

            <div v-if="! company" class="panel p-5 text-sm text-gray-600 dark:text-gray-300">
                Configura primero los datos de la empresa para cargar las credenciales del SIRE.
            </div>

            <template v-else>
                <div class="panel p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white">Modo de trabajo</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                El modo solo define qué pestaña se abre primero en el registro; las dos opciones siguen
                                disponibles.
                            </p>
                        </div>
                        <p class="text-xs text-gray-400">RUC {{ company.ruc }}</p>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <button
                            type="button"
                            class="rounded-lg border p-4 text-left transition"
                            :class="form.mode === 'txt'
                                ? 'border-primary bg-primary/5'
                                : 'border-gray-200 hover:border-primary/40 dark:border-gray-700'"
                            @click="form.mode = 'txt'"
                        >
                            <p class="font-semibold text-gray-800 dark:text-white">Descarga manual (TXT / ZIP)</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Genera el archivo plano y se sube a SOL o al aplicativo cliente SIRE.
                            </p>
                        </button>

                        <button
                            type="button"
                            class="rounded-lg border p-4 text-left transition"
                            :class="form.mode === 'api'
                                ? 'border-primary bg-primary/5'
                                : 'border-gray-200 hover:border-primary/40 dark:border-gray-700'"
                            @click="form.mode = 'api'"
                        >
                            <p class="font-semibold text-gray-800 dark:text-white">Envío automático (API)</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Token, propuesta y envío sin salir del ERP. Requiere las cuatro credenciales.
                            </p>
                        </button>
                    </div>

                    <p v-if="form.errors.mode" class="mt-3 text-sm text-danger">{{ form.errors.mode }}</p>
                </div>

                <div class="panel p-5">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">Credenciales de API SUNAT</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Se generan en SOL → Empresas → Credenciales de API SUNAT. El usuario y la clave son los mismos del
                        contribuyente; el client_id y el client_secret identifican a esta aplicación.
                    </p>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-500 dark:text-gray-400">
                                client_id
                            </label>
                            <input
                                v-model="form.client_id"
                                type="text"
                                class="form-input w-full"
                                autocomplete="off"
                                :placeholder="pistaClientId"
                            />
                            <p v-if="form.errors.client_id" class="mt-1 text-xs text-danger">{{ form.errors.client_id }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-500 dark:text-gray-400">
                                client_secret
                            </label>
                            <input
                                v-model="form.client_secret"
                                type="password"
                                class="form-input w-full"
                                autocomplete="new-password"
                                :placeholder="pistaSecreto(credentials.has_client_secret, 'El secreto que genera SOL')"
                            />
                            <p v-if="form.errors.client_secret" class="mt-1 text-xs text-danger">
                                {{ form.errors.client_secret }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-500 dark:text-gray-400">
                                Usuario SOL
                            </label>
                            <input
                                v-model="form.sol_user"
                                type="text"
                                class="form-input w-full"
                                autocomplete="off"
                                :placeholder="credentials.sol_user
                                    ? `Configurado: ${credentials.sol_user} — deja vacío para conservar`
                                    : 'Usuario SOL del contribuyente'"
                            />
                            <p v-if="form.errors.sol_user" class="mt-1 text-xs text-danger">{{ form.errors.sol_user }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-500 dark:text-gray-400">
                                Clave SOL
                            </label>
                            <input
                                v-model="form.sol_key"
                                type="password"
                                class="form-input w-full"
                                autocomplete="new-password"
                                :placeholder="pistaSecreto(credentials.has_sol_key, 'Clave SOL del contribuyente')"
                            />
                            <p v-if="form.errors.sol_key" class="mt-1 text-xs text-danger">{{ form.errors.sol_key }}</p>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        <button type="button" class="btn btn-primary" :disabled="form.processing" @click="guardar">
                            {{ form.processing ? 'Guardando…' : 'Guardar configuración' }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-primary"
                            :disabled="probando || ! credentialsReady"
                            @click="probar"
                        >
                            {{ probando ? 'Consultando SUNAT…' : 'Probar conexión' }}
                        </button>
                        <span v-if="! credentialsReady" class="text-xs text-gray-400">
                            Guarda las cuatro credenciales para poder probar la conexión.
                        </span>
                    </div>

                    <p class="mt-3 text-xs text-gray-400">
                        Probar conexión solo pide el token: no consulta ni modifica ningún registro del SIRE.
                    </p>
                </div>

                <div class="panel overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-2 p-5">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white">Bitácora de llamadas</h3>
                        <Link :href="route('sire_configuracion')" class="text-xs text-primary">Actualizar</Link>
                    </div>

                    <div class="table-responsive">
                        <table class="table-hover w-full text-sm">
                            <thead>
                                <tr class="border-t border-white-light text-left dark:border-dark">
                                    <th class="px-5 py-3">Fecha</th>
                                    <th class="px-5 py-3">Acción</th>
                                    <th class="px-5 py-3">HTTP</th>
                                    <th class="px-5 py-3">Duración</th>
                                    <th class="px-5 py-3">Mensaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="log in logs" :key="log.id" class="border-t border-white-light dark:border-dark">
                                    <td class="whitespace-nowrap px-5 py-3 text-xs text-gray-500">{{ log.at }}</td>
                                    <td class="px-5 py-3 font-semibold text-gray-800 dark:text-white">
                                        {{ log.action }}
                                        <p class="break-all text-xs font-normal text-gray-400">{{ log.endpoint }}</p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span
                                            class="badge"
                                            :class="log.success ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
                                        >
                                            {{ log.http_status || '—' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-xs text-gray-500">{{ log.duration_ms }} ms</td>
                                    <td class="px-5 py-3 text-xs text-gray-600 dark:text-gray-300">{{ log.message }}</td>
                                </tr>
                                <tr v-if="! logs.length">
                                    <td colspan="5" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Todavía no hay llamadas a SUNAT. Usa «Probar conexión» o descarga una propuesta.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel overflow-hidden">
                    <div class="p-5">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white">Últimas descargas de propuesta</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Cada proceso guarda su ticket, el archivo recibido y su hash.
                        </p>
                    </div>

                    <div class="table-responsive">
                        <table class="table-hover w-full text-sm">
                            <thead>
                                <tr class="border-t border-white-light text-left dark:border-dark">
                                    <th class="px-5 py-3">Fecha</th>
                                    <th class="px-5 py-3">Registro</th>
                                    <th class="px-5 py-3">Período</th>
                                    <th class="px-5 py-3">Ticket</th>
                                    <th class="px-5 py-3">Estado</th>
                                    <th class="px-5 py-3">Archivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in submissions" :key="item.id" class="border-t border-white-light dark:border-dark">
                                    <td class="whitespace-nowrap px-5 py-3 text-xs text-gray-500">{{ item.at }}</td>
                                    <td class="px-5 py-3 text-gray-700 dark:text-gray-200">{{ item.tipo }}</td>
                                    <td class="px-5 py-3 text-gray-700 dark:text-gray-200">{{ item.period }}</td>
                                    <td class="break-all px-5 py-3 text-xs text-gray-500">{{ item.ticket || '—' }}</td>
                                    <td class="px-5 py-3">
                                        <span
                                            class="badge"
                                            :class="item.status === 'terminado'
                                                ? 'bg-success/10 text-success'
                                                : (item.status === 'error' || item.status === 'expirado'
                                                    ? 'bg-danger/10 text-danger'
                                                    : 'bg-warning/10 text-warning')"
                                        >
                                            {{ item.status }}
                                        </span>
                                    </td>
                                    <td class="break-all px-5 py-3 text-xs text-gray-500">
                                        {{ item.file_name || '—' }}
                                        <span v-if="item.lines"> · {{ item.lines }} línea(s)</span>
                                    </td>
                                </tr>
                                <tr v-if="! submissions.length">
                                    <td colspan="6" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Sin descargas registradas.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
