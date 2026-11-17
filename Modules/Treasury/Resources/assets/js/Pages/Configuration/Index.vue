<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, useForm } from '@inertiajs/vue3';
    import InputLabel from '@/Components/InputLabel.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { computed } from 'vue';
    import { faGear, faTriangleExclamation, faLock, faCircleInfo } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        settings: { type: Object, required: true },
        history: { type: Array, default: () => [] },
        canManage: { type: Boolean, default: false },
    });

    const fecha = (value) => {
        if (!value) return '—';
        const [day] = [String(value).split(' ')[0]];
        const [year, month, dia] = day.split('-');
        return year && month && dia ? `${dia}/${month}/${year}` : value;
    };

    const form = useForm({
        password: '',
        enabled: Boolean(props.settings.enabled),
        starts_on: props.settings.starts_on || null,
    });

    const desactivando = computed(() => Boolean(props.settings.enabled) && !form.enabled);

    const ayuda = () => {
        window.alertaSistema(
            'Cómo funciona Tesorería',
            'Tesorería arranca desde la fecha de inicio que fijes aquí: los movimientos y comprobantes anteriores no cuentan para saldos, proyecciones ni reportes, pero se conservan en la base. El corte no filtra las conciliaciones ni los periodos ya cerrados, porque son actas contables, y tampoco borra la auditoría. El saldo inicial de cada cuenta sigue sumando: para arrancar en cero, déjalo en 0 o en el saldo real a esa fecha. Si desactivas el módulo, todas sus pantallas dejan de responder hasta que lo vuelvas a activar; los datos no se borran. Cada cambio pide tu contraseña y queda en la auditoría con tu usuario, la fecha y la hora.',
            'info',
            { duration: 0 }
        );
    };

    const guardar = async () => {
        const resultado = await window.alertaSistema(
            'Guardar configuración de Tesorería',
            desactivando.value
                ? 'Vas a DESACTIVAR Tesorería: nadie podrá entrar a sus pantallas hasta que la actives de nuevo. ¿Continuar?'
                : 'Vas a fijar la puesta en marcha del módulo. La fecha de inicio marca el corte: lo anterior no se toma en cuenta. ¿Continuar?',
            'warning',
            {
                duration: 0,
                showCancelButton: true,
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar',
            }
        );

        if (!resultado?.isConfirmed) return;

        form.post(route('treasury_configuration_update'), {
            preserveScroll: true,
            onSuccess: (page) => {
                const flash = page?.props?.flash || {};
                if (flash.error) window.alertaSistema('No se guardó', flash.error, 'error');
                else if (flash.success) window.alertaSistema('Configuración guardada', flash.success, 'success');
                form.password = '';
            },
            onError: () => window.alertaSistema('Revisa el formulario', 'La contraseña es obligatoria y la fecha debe ser válida.', 'warning'),
        });
    };
</script>

<template>
    <AppLayout title="Configuración de Tesorería">
        <Head title="Tesorería · Configuración">
            <meta name="aracodito-view" content="new" />
        </Head>
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Tesorería</span></li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Configuración</span></li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <!-- Operación crítica -->
            <div class="panel border-l-4 border-danger p-5">
                <div class="flex items-start gap-3">
                    <font-awesome-icon :icon="faLock" class="mt-1 text-danger" />
                    <div class="flex-1">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Configuración crítica</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            Activar o desactivar Tesorería y fijar su fecha de inicio cambia lo que ve toda la empresa.
                            Cada cambio pide tu contraseña y queda en la auditoría del módulo.
                        </p>
                    </div>
                    <button type="button" class="btn btn-outline-primary whitespace-nowrap" @click="ayuda">
                        <font-awesome-icon :icon="faCircleInfo" class="mr-1" /> ¿Cómo funciona?
                    </button>
                </div>
            </div>

            <!-- Estado actual -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Estado del módulo</p>
                    <p class="mt-2 text-2xl font-bold" :class="settings.enabled ? 'text-success' : 'text-danger'">
                        {{ settings.enabled ? 'Activa' : 'Desactivada' }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">Apagada, ninguna pantalla responde</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Fecha de inicio</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-100">{{ fecha(settings.starts_on) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Lo anterior no cuenta para saldos ni reportes</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Último cambio</p>
                    <p class="mt-2 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ settings.updated_at || '—' }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ settings.updated_by || 'Sin registro' }}</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Cambios auditados</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-100">{{ history.length }}</p>
                    <p class="mt-1 text-xs text-gray-500">Registro inmutable con usuario e IP</p>
                </div>
            </div>

            <div v-if="!settings.enabled" class="panel border-l-4 border-warning p-4 text-sm text-warning">
                <font-awesome-icon :icon="faTriangleExclamation" class="mr-1" />
                Tesorería está apagada: sus pantallas responden 403 hasta que la actives aquí.
            </div>

            <!-- Formulario -->
            <div class="panel p-5">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Puesta en marcha</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    El corte aplica a saldos, proyecciones y reportes. Los movimientos anteriores se conservan, pero no se suman.
                </p>

                <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <div>
                        <InputLabel value="Módulo de Tesorería" class="mb-1.5" />
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.enabled" type="checkbox" class="form-checkbox rounded" />
                            <span>{{ form.enabled ? 'Activo' : 'Desactivado' }}</span>
                        </label>
                        <InputError :message="form.errors.enabled" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel value="Fecha de inicio" class="mb-1.5" />
                        <input v-model="form.starts_on" type="date" class="form-input w-full" />
                        <p class="mt-1 text-xs text-gray-500">
                            Desde aquí cuenta Tesorería. Déjala vacía para no aplicar corte.
                        </p>
                        <InputError :message="form.errors.starts_on" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel value="Tu contraseña *" class="mb-1.5" />
                        <input v-model="form.password" type="password" class="form-input w-full" autocomplete="current-password" />
                        <p class="mt-1 text-xs text-gray-500">Obligatoria para guardar; el cambio se audita.</p>
                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>
                </div>

                <!-- Alcance exacto del corte -->
                <div class="mt-5 rounded-lg border border-info/40 bg-info/5 p-3 text-xs leading-relaxed text-gray-600 dark:text-gray-300">
                    <p class="font-semibold text-gray-700 dark:text-gray-100">Qué corta y qué no la fecha de inicio</p>
                    <ul class="mt-1 list-disc space-y-1 pl-5">
                        <li>Corta saldos, libro de movimientos, reportes, previsión, detracción e IGV.</li>
                        <li>No filtra conciliaciones ni periodos ya cerrados: son actas contables y se conservan.</li>
                        <li>No borra la auditoría: el historial de operaciones queda íntegro.</li>
                        <li>
                            El saldo inicial de cada cuenta sigue sumando. Para arrancar en cero, déjalo en 0
                            o en el saldo real que tenía la cuenta a esa fecha.
                        </li>
                    </ul>
                </div>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="max-w-xl text-xs text-gray-500 dark:text-gray-400">
                        Al desactivar, las alertas del módulo dejan de mostrarse y las pantallas no responden.
                        Los datos, la auditoría y la cuenta de detracción se conservan.
                    </p>
                    <PrimaryButton :disabled="!canManage || form.processing" @click="guardar">
                        Guardar configuración
                    </PrimaryButton>
                </div>
            </div>

            <!-- Historial -->
            <div class="panel overflow-hidden p-0">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Historial de la configuración</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Quién cambió qué, cuándo y desde qué IP</p>
                </div>
                <div class="table-responsive max-h-[420px] overflow-y-auto">
                    <table class="min-w-full">
                        <thead class="sticky top-0 bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Antes</th>
                                <th>Después</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in history" :key="row.id">
                                <td class="whitespace-nowrap text-sm">{{ row.created_at }}</td>
                                <td class="text-sm">{{ row.user || 'Sistema' }}</td>
                                <td class="text-sm text-gray-500">
                                    {{ row.old?.enabled ? 'Activa' : 'Apagada' }} · {{ fecha(row.old?.starts_on) }}
                                </td>
                                <td class="text-sm font-medium">
                                    {{ row.new?.enabled ? 'Activa' : 'Apagada' }} · {{ fecha(row.new?.starts_on) }}
                                </td>
                            </tr>
                            <tr v-if="history.length === 0">
                                <td colspan="4" class="py-8 text-center text-sm text-gray-500">Todavía no hay cambios registrados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
