<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import ModalMedium from '@/Components/ModalMedium.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { Head, router, useForm } from '@inertiajs/vue3';
    import { computed, ref } from 'vue';
    import { faPlus, faFilter, faCheck, faBan, faSearch } from '@fortawesome/free-solid-svg-icons';
    import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

    const props = defineProps({
        documents: { type: Array, default: () => [] },
        summary: { type: Object, default: () => ({ groups: [], count: 0, receivable: 0, payable: 0, overdue: 0, due_soon: 0 }) },
        suppliers: { type: Array, default: () => [] },
        accounts: { type: Array, default: () => [] },
        filters: { type: Object, default: () => ({}) },
        options: { type: Object, default: () => ({ types: [], directions: [], statuses: [] }) },
        canManage: { type: Boolean, default: false },
    });

    const money = (value, currency = 'PEN') => {
        try {
            return new Intl.NumberFormat('es-PE', { style: 'currency', currency }).format(value || 0);
        } catch (e) {
            return `${currency} ${Number(value || 0).toFixed(2)}`;
        }
    };

    const notifyFlash = (page) => {
        const flash = page?.props?.flash || {};
        if (flash.error) window.alertaSistema('No se pudo completar', flash.error, 'error');
        else if (flash.success) window.alertaSistema('Listo', flash.success, 'success');
    };

    const today = () => new Date().toISOString().slice(0, 10);

    const filters = ref({
        direction: props.filters.direction || '',
        status: props.filters.status || '',
    });

    const applyFilters = () => {
        router.get(route('treasury_documents_index'), filters.value, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const statusClass = (status) => ({
        portfolio: 'bg-primary/10 text-primary',
        deposited: 'bg-info/10 text-info',
        collected: 'bg-success/10 text-success',
        paid: 'bg-success/10 text-success',
        protested: 'bg-danger/10 text-danger',
        cancelled: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300',
    }[status] || 'bg-gray-100 text-gray-600');

    // ---------------- Alta de documento ----------------
    const modal = ref(false);
    const form = useForm({
        type: 'cheque',
        direction: 'receivable',
        number: '',
        bank_name: '',
        issuer: '',
        issuer_doc_type: '1',
        issuer_doc_number: '',
        supplier_id: null,
        amount: '',
        currency: 'PEN',
        issue_date: today(),
        due_date: today(),
        treasury_account_id: null,
        notes: '',
    });

    const open = () => {
        form.reset();
        form.clearErrors();
        form.type = 'cheque';
        form.direction = 'receivable';
        form.currency = 'PEN';
        form.issue_date = today();
        form.due_date = today();
        form.issuer_doc_type = '1';
        form.issuer_doc_number = '';
        modal.value = true;
    };

    // ---------------- Consulta RENIEC / SUNAT del emisor ----------------
    const issuerLoading = ref(false);
    const isRucIssuer = computed(() => String(form.issuer_doc_type) === '6');

    const onlyIssuerDigits = () => {
        form.issuer_doc_number = String(form.issuer_doc_number || '').replace(/\D/g, '');
    };

    const consultIssuer = () => {
        if (issuerLoading.value) return;

        onlyIssuerDigits();
        form.clearErrors('issuer_doc_number');

        const expected = isRucIssuer.value ? 11 : 8;

        if (String(form.issuer_doc_number || '').length !== expected) {
            window.alertaSistema(
                'Documento incompleto',
                isRucIssuer.value ? 'Ingresa los 11 dígitos del RUC.' : 'Ingresa los 8 dígitos del DNI.',
                'warning'
            );
            return;
        }

        issuerLoading.value = true;

        axios.post(route('treasury_documents_consult_issuer'), {
            issuer_doc_type: form.issuer_doc_type,
            issuer_doc_number: form.issuer_doc_number,
        }).then((res) => {
            if (!res.data.success) {
                window.alertaSistema('Sin resultados', res.data.error || 'No se encontraron datos para el documento.', 'error');
                return;
            }

            form.issuer = res.data.issuer.name || form.issuer;
            form.issuer_doc_number = res.data.issuer.document_number || form.issuer_doc_number;

            window.alertaSistema(
                'Datos del emisor cargados',
                isRucIssuer.value ? 'Se consultó SUNAT correctamente.' : 'Se consultó RENIEC correctamente.',
                'success'
            );
        }).catch((error) => {
            const data = error.response?.data;
            const message = data?.errors?.issuer_doc_number?.[0]
                || data?.error
                || 'No se pudo consultar el documento. Revisa el token de la API en Parámetros.';

            window.alertaSistema('No se pudo consultar', message, 'error');
        }).finally(() => {
            issuerLoading.value = false;
        });
    };

    const save = () => {
        form.post(route('treasury_documents_store'), {
            preserveScroll: true,
            onSuccess: (page) => {
                notifyFlash(page);
                modal.value = false;
            },
            onError: () => window.alertaSistema('Revisa el formulario', 'El número, el monto y las fechas son obligatorios.', 'warning'),
        });
    };

    // ---------------- Ciclo de vida ----------------
    const actions = (document) => {
        if (document.direction === 'receivable') {
            if (document.status === 'portfolio') {
                return [
                    { status: 'deposited', label: 'Depositar', icon: faCheck },
                    { status: 'collected', label: 'Cobrado', icon: faCheck },
                    { status: 'protested', label: 'Protestar', icon: faBan },
                    { status: 'cancelled', label: 'Anular', icon: faBan },
                ];
            }

            if (document.status === 'deposited') {
                return [
                    { status: 'collected', label: 'Cobrado', icon: faCheck },
                    { status: 'protested', label: 'Protestar', icon: faBan },
                    { status: 'cancelled', label: 'Anular', icon: faBan },
                ];
            }

            return [];
        }

        if (document.status === 'portfolio') {
            return [
                { status: 'paid', label: 'Pagado', icon: faCheck },
                { status: 'cancelled', label: 'Anular', icon: faBan },
            ];
        }

        return [];
    };

    const changeStatus = async (document, action) => {
        const result = await window.alertaSistema(
            action.label,
            `${document.type_label} ${document.number} por ${money(document.amount, document.currency)}. El cambio queda auditado.`,
            action.status === 'cancelled' || action.status === 'protested' ? 'warning' : 'question',
            {
                duration: 0,
                showCancelButton: true,
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar',
            }
        );

        if (!result?.isConfirmed) return;

        router.post(
            route('treasury_documents_status', document.id),
            {
                status: action.status,
                note: action.status === 'protested' || action.status === 'cancelled'
                    ? `${action.label} desde la cartera`
                    : null,
            },
            { preserveScroll: true, onSuccess: notifyFlash }
        );
    };
</script>

<template>
    <AppLayout title="Cartera de cheques y letras">
        <Head title="Tesorería · Cartera">
            <meta name="aracodito-view" content="new" />
        </Head>
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Tesorería</span></li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Cartera</span></li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div class="panel flex flex-col gap-3 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Cheques y letras</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Documentos por cobrar y por pagar. Los pendientes entran a la previsión de caja por su vencimiento.
                        Anular no borra: deja el rastro en auditoría.
                    </p>
                </div>
                <button v-if="canManage" type="button" class="btn btn-primary" @click="open">
                    <font-awesome-icon :icon="faPlus" class="mr-1" /> Nuevo documento
                </button>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="group in summary.groups" :key="group.currency" class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Por cobrar · {{ group.currency }}</p>
                    <p class="mt-2 text-2xl font-bold text-success">{{ money(group.receivable, group.currency) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Por pagar {{ money(group.payable, group.currency) }}</p>
                    <p v-if="group.overdue" class="mt-1 text-xs text-danger">Vencido {{ money(group.overdue, group.currency) }}</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Documentos en cartera</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-100">{{ summary.count }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ summary.due_soon }} vencen en 7 días</p>
                </div>
                <div class="panel border-l-4 border-danger p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Vencidos</p>
                    <p class="mt-2 text-2xl font-bold text-danger">{{ summary.overdue }}</p>
                    <p class="mt-1 text-xs text-gray-500">Requieren gestión inmediata</p>
                </div>
            </div>

            <div class="panel flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <select v-model="filters.direction" class="form-select sm:w-44">
                        <option value="">Todas las direcciones</option>
                        <option v-for="item in options.directions" :key="item.code" :value="item.code">{{ item.label }}</option>
                    </select>
                    <select v-model="filters.status" class="form-select sm:w-48">
                        <option value="">Todos los estados</option>
                        <option v-for="item in options.statuses" :key="item.code" :value="item.code">{{ item.label }}</option>
                    </select>
                    <button type="button" class="btn btn-outline-primary" @click="applyFilters">
                        <font-awesome-icon :icon="faFilter" class="mr-1" /> Filtrar
                    </button>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Por cobrar {{ money(summary.receivable) }} · Por pagar {{ money(summary.payable) }}</p>
            </div>

            <div class="panel overflow-hidden p-0">
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th>Tipo</th>
                                <th>Número</th>
                                <th>Dirección</th>
                                <th class="min-w-[180px]">Emisor / Proveedor</th>
                                <th>Emisión</th>
                                <th>Vence</th>
                                <th class="text-right">Monto</th>
                                <th>Estado</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="documents.length === 0">
                                <td colspan="9" class="py-8 text-center text-sm text-gray-500">
                                    No hay documentos en cartera con esos filtros.
                                </td>
                            </tr>
                            <tr v-for="document in documents" :key="document.id">
                                <td class="text-sm">{{ document.type_label }}</td>
                                <td class="font-mono text-xs">{{ document.number }}</td>
                                <td>
                                    <span
                                        class="rounded-md px-2 py-1 text-xs font-medium"
                                        :class="document.direction === 'receivable' ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
                                    >
                                        {{ document.direction_label }}
                                    </span>
                                </td>
                                <td>
                                    <p class="text-sm">{{ document.issuer || '—' }}</p>
                                    <p v-if="document.issuer_doc_number" class="text-xs text-gray-400">
                                        {{ document.issuer_doc_type === '6' ? 'RUC' : 'DNI' }} {{ document.issuer_doc_number }}
                                    </p>
                                    <p v-if="document.bank_name" class="text-xs text-gray-400">{{ document.bank_name }}</p>
                                </td>
                                <td class="text-sm">{{ document.issue_date }}</td>
                                <td class="text-sm">
                                    {{ document.due_date }}
                                    <span v-if="document.overdue" class="ml-1 rounded bg-danger/10 px-1.5 py-0.5 text-xs text-danger">
                                        Vencido
                                    </span>
                                    <span v-else-if="document.status === 'portfolio' && document.days !== null" class="ml-1 text-xs text-gray-400">
                                        {{ document.days }} d
                                    </span>
                                </td>
                                <td class="text-right font-semibold">{{ money(document.amount, document.currency) }}</td>
                                <td>
                                    <span class="rounded-md px-2 py-1 text-xs font-medium" :class="statusClass(document.status)">
                                        {{ document.status_label }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div v-if="canManage" class="flex flex-wrap justify-end gap-1">
                                        <button
                                            v-for="action in actions(document)"
                                            :key="action.status"
                                            type="button"
                                            class="btn btn-sm"
                                            :class="action.status === 'cancelled' || action.status === 'protested'
                                                ? 'btn-outline-danger'
                                                : 'btn-outline-primary'"
                                            :title="action.label"
                                            @click="changeStatus(document, action)"
                                        >
                                            <font-awesome-icon :icon="action.icon" class="mr-1" />
                                            <span class="hidden sm:inline">{{ action.label }}</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ModalMedium :show="modal" :on-close="() => (modal = false)" :icon="'/img/cuenta-bancaria.png'">
            <template #title>Nuevo cheque o letra</template>
            <template #message><span>Registra el documento tal como está en cartera</span></template>
            <template #content>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel value="Tipo *" class="mb-1.5" />
                        <select v-model="form.type" class="form-select w-full">
                            <option v-for="item in options.types" :key="item.code" :value="item.code">{{ item.label }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Dirección *" class="mb-1.5" />
                        <select v-model="form.direction" class="form-select w-full">
                            <option v-for="item in options.directions" :key="item.code" :value="item.code">{{ item.label }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Número *" class="mb-1.5" />
                        <input v-model="form.number" type="text" class="form-input w-full" placeholder="Ej: 00123456" />
                        <InputError :message="form.errors.number" />
                    </div>
                    <div>
                        <InputLabel value="Banco" class="mb-1.5" />
                        <input v-model="form.bank_name" type="text" class="form-input w-full" />
                    </div>
                    <div class="md:col-span-2">
                        <InputLabel value="Emisor" class="mb-1.5" />
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-[120px_1fr_auto]">
                            <select v-model="form.issuer_doc_type" class="form-select w-full">
                                <option value="1">DNI</option>
                                <option value="6">RUC</option>
                            </select>
                            <input
                                v-model="form.issuer_doc_number"
                                type="text"
                                inputmode="numeric"
                                class="form-input w-full"
                                placeholder="Número de documento"
                                @input="onlyIssuerDigits"
                                @keyup.enter.prevent="consultIssuer"
                            />
                            <button type="button" class="btn btn-outline-primary" :disabled="issuerLoading" @click="consultIssuer">
                                <IconLoader v-if="issuerLoading" class="w-4 h-4 mr-1 animate-spin" />
                                <font-awesome-icon v-else :icon="faSearch" class="mr-1" />
                                {{ isRucIssuer ? 'Consultar SUNAT' : 'Consultar RENIEC' }}
                            </button>
                        </div>
                        <InputError :message="form.errors.issuer_doc_number" class="mt-1" />
                        <input v-model="form.issuer" type="text" class="form-input w-full mt-2" placeholder="Nombre o razón social del emisor" />
                        <p class="mt-1 text-xs text-gray-500">La consulta completa el nombre desde la API; puedes editarlo.</p>
                    </div>
                    <div v-if="form.direction === 'payable'" class="md:col-span-2">
                        <InputLabel value="Proveedor" class="mb-1.5" />
                        <select v-model="form.supplier_id" class="form-select w-full">
                            <option :value="null">Sin proveedor</option>
                            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Monto *" class="mb-1.5" />
                        <input v-model="form.amount" type="number" min="0.01" step="0.01" class="form-input w-full" />
                        <InputError :message="form.errors.amount" />
                    </div>
                    <div>
                        <InputLabel value="Moneda" class="mb-1.5" />
                        <select v-model="form.currency" class="form-select w-full">
                            <option value="PEN">Soles (PEN)</option>
                            <option value="USD">Dólares (USD)</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Emisión *" class="mb-1.5" />
                        <input v-model="form.issue_date" type="date" class="form-input w-full" />
                        <InputError :message="form.errors.issue_date" />
                    </div>
                    <div>
                        <InputLabel value="Vencimiento *" class="mb-1.5" />
                        <input v-model="form.due_date" type="date" class="form-input w-full" />
                        <InputError :message="form.errors.due_date" />
                    </div>
                    <div class="md:col-span-2">
                        <InputLabel value="Cuenta de depósito / pago" class="mb-1.5" />
                        <select v-model="form.treasury_account_id" class="form-select w-full">
                            <option :value="null">Sin cuenta</option>
                            <option v-for="account in accounts" :key="account.id" :value="account.id">{{ account.label }} ({{ account.currency }})</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <InputLabel value="Notas" class="mb-1.5" />
                        <textarea v-model="form.notes" rows="2" class="form-textarea w-full"></textarea>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton :disabled="form.processing" @click="save">Guardar documento</PrimaryButton>
            </template>
        </ModalMedium>
    </AppLayout>
</template>
