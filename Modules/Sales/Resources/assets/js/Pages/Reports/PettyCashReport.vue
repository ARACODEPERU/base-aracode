<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { ref } from 'vue';
    import Keypad from '@/Components/Keypad.vue';
    import jsPDF from 'jspdf';
    import autoTable from 'jspdf-autotable';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';

    const props = defineProps({
        locals: {
            type: Object,
            default: () => ({}),
        },
        report: {
            type: Object,
            default: () => ({}),
        },
        petty_cash: {
            type: Object,
            default: () => ({}),
        },
        date: {
            type: String,
            default: null,
        },
        start: {
            type: String,
            default: null,
        },
        end: {
            type: String,
            default: null,
        },
    });

    const totals = props.report.totals || {
        electronic: 0, tickets: 0, physicals: 0, gross: 0,
        credits: 0, debits: 0, net_sales: 0, expenses: 0,
        counts: {},
    };
    const electronic = props.report.electronic || [];
    const tickets = props.report.tickets || [];
    const physicals = props.report.physicals || [];
    const notes = props.report.notes || [];
    const excluded = props.report.excluded || [];
    const expenses = props.report.expenses || [];

    const creditNotes = notes.filter((n) => n.tipo === 'Nota de Crédito');
    const debitNotes = notes.filter((n) => n.tipo === 'Nota de Débito');

    const local_name = ref(null);
    const local = (props.locals || []).find((l) => l.id == props.petty_cash.local_sale_id);
    local_name.value = local ? local.description : 'TODOS LOS LOCALES';

    const money = (n) => 'S/ ' + (Number(n) || 0).toFixed(2);

    const getLocal = (id = null) => {
        if (id) {
            let arreglo = props.locals;
            let l = arreglo.find((a) => a.id == id);
            local_name.value = l ? l.description : null;
        } else {
            local_name.value = 'TODOS LOS LOCALES';
        }
    };

    const downloadPdf = () => {
        const table = document.getElementById('table_export');

        const pdf = new jsPDF({
            orientation: 'landscape',
            unit: 'pt',
            format: 'a4',
        });

        const titulo = 'Reporte de Caja de: ' + (local_name.value || '');
        pdf.text(titulo, 200, 20);

        const subtitulo = props.petty_cash.state == 0
            ? 'Día: ' + props.petty_cash.date_opening
            : 'Caja abierta';
        pdf.text(subtitulo, 200, 40);

        pdf.autoTable({
            html: table,
            startY: 70,
        });

        const nombre = props.petty_cash.state == 0
            ? props.petty_cash.date_opening
            : 'caja-abierta';
        pdf.save('RpteCaja_' + (local_name.value || '') + '-' + nombre + '.pdf');
    };

    getLocal(props.petty_cash.local_sale_id);
</script>

<template>
    <AppLayout title="Reporte de Caja Chica">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Ventas'"
            :data="[
                {route: route('pettycash.index'), title: 'Caja Chica'},
                {title: 'Reporte'}
            ]"
        />
        <div class="mt-5">
            <div class="flex flex-col gap-5">
                <div class="panel p-0">
                    <div class="w-full p-4">
                        <div class="grid grid-cols-3">
                            <div class="col-span-3 sm:col-span-1">
                                <select id="stablishment" disabled
                                    class="form-select text-white-dark">
                                    <option v-for="(l, index) in props.locals" :key="index" :value="l.id">{{ l.description }}</option>
                                </select>
                            </div>
                            <div class="col-span-3 sm:col-span-2">
                                <Keypad>
                                    <template #botones>
                                        <button v-on:click="downloadPdf()"
                                            class="px-3 py-1 text-base font-normal text-gray-700 bg-white bg-clip-padding border border-solid border-gray-300 rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
                                            >Exportar en PDF
                                        </button>
                                        <a :href="route('pettycash.index')" class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</a>
                                    </template>
                                </Keypad>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="table_export">
                            <thead class="text-xs uppercase">
                                <tr class="bg-primary/20 border-primary/20">
                                    <th colspan="2" class=""><strong>LOCAL:</strong></th>
                                    <th colspan="3" class="">{{ local_name }}</th>
                                </tr>
                                <tr class="bg-primary/20 border-primary/20">
                                    <th colspan="2" class=""><strong>APERTURA:</strong></th>
                                    <th colspan="3" class="text-left font-medium">
                                        {{ petty_cash.date_opening }} {{ petty_cash.time_opening ? petty_cash.time_opening.slice(0, -3) : '' }}
                                    </th>
                                </tr>
                                <tr class="bg-primary/20 border-primary/20">
                                    <th colspan="2" class="text-left font-medium"><strong>CIERRE:</strong></th>
                                    <th colspan="3" class="text-left font-medium">
                                        <span v-if="petty_cash.state == 0">{{ petty_cash.date_closed }} {{ petty_cash.time_closed }}</span>
                                        <span v-else class="text-warning">Caja abierta</span>
                                    </th>
                                </tr>
                                <tr class="bg-primary/20 border-primary/20">
                                    <td class="text-center text-sm" colspan="5"><b>RESUMEN DEL CUADRE DE CAJA</b></td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-right">Saldo inicial de caja</td>
                                    <td class="text-right">{{ money(petty_cash.beginning_balance) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-right">Facturas electrónicas ({{ totals.counts.electronic || 0 }})</td>
                                    <td class="text-right">{{ money(totals.electronic) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-right">Boletas electrónicas y tickets ({{ totals.counts.tickets || 0 }})</td>
                                    <td class="text-right">{{ money(totals.tickets) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-right">Documentos físicos ({{ totals.counts.physicals || 0 }})</td>
                                    <td class="text-right">{{ money(totals.physicals) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-right font-medium">Total ventas brutas</td>
                                    <td class="text-right font-medium">{{ money(totals.gross) }}</td>
                                </tr>
                                <tr v-for="note in creditNotes" :key="'rc-' + note.id" class="text-danger">
                                    <td colspan="4" class="text-right">{{ note.numero }} · {{ note.tipo }} ({{ note.motivo }}) — afecta a {{ note.doc_afectado }}</td>
                                    <td class="text-right">- {{ money(note.monto_soles) }}</td>
                                </tr>
                                <tr v-for="note in debitNotes" :key="'rd-' + note.id" class="text-success">
                                    <td colspan="4" class="text-right">{{ note.numero }} · {{ note.tipo }} ({{ note.motivo }}) — afecta a {{ note.doc_afectado }}</td>
                                    <td class="text-right">+ {{ money(note.monto_soles) }}</td>
                                </tr>
                                <tr class="bg-primary/20 border-primary/20">
                                    <td colspan="4" class="text-right font-bold uppercase">Total ventas netas</td>
                                    <td class="text-right font-bold">{{ money(totals.net_sales) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-right">Gastos de caja</td>
                                    <td class="text-right text-danger">- {{ money(totals.expenses) }}</td>
                                </tr>
                                <tr class="bg-primary/20 border-primary/20">
                                    <td colspan="4" class="text-right font-bold uppercase">Efectivo en caja</td>
                                    <td class="text-right font-bold">
                                        {{ money((Number(petty_cash.beginning_balance) || 0) + Number(totals.net_sales) - Number(totals.expenses)) }}
                                    </td>
                                </tr>

                                <tr>
                                    <td class="text-center text-sm font-bold uppercase" colspan="5">Ventas</td>
                                </tr>
                                <tr>
                                    <th scope="col">Fecha</th>
                                    <th>Tienda</th>
                                    <th>Serie / Número</th>
                                    <th>Tipo</th>
                                    <th>Total</th>
                                </tr>
                                <tr v-for="ticket in tickets" :key="'t-' + ticket.sale_id">
                                    <th class="font-medium whitespace-nowrap">{{ ticket.fecha }}</th>
                                    <td>{{ ticket.local }}</td>
                                    <td>{{ ticket.numero }}</td>
                                    <td>{{ ticket.tipo }}</td>
                                    <td class="text-right">{{ money(ticket.monto) }}</td>
                                </tr>
                                <tr v-for="item in electronic" :key="'e-' + item.sale_id">
                                    <th class="font-medium whitespace-nowrap">{{ item.fecha }}</th>
                                    <td>{{ item.local }}</td>
                                    <td>{{ item.numero }}</td>
                                    <td>{{ item.tipo }}</td>
                                    <td class="text-right">{{ money(item.monto) }}</td>
                                </tr>
                                <tr v-for="physical in physicals" :key="'p-' + physical.sale_id">
                                    <th class="font-medium whitespace-nowrap">{{ physical.fecha }}</th>
                                    <td>{{ physical.local }}</td>
                                    <td>{{ physical.numero }}</td>
                                    <td>{{ physical.tipo }}</td>
                                    <td class="text-right">{{ money(physical.monto) }}</td>
                                </tr>

                                <tr v-if="notes.length > 0">
                                    <td class="text-center text-sm font-bold uppercase" colspan="5">Notas de crédito y débito</td>
                                </tr>
                                <tr v-if="notes.length > 0">
                                    <th scope="col">Fecha</th>
                                    <th>Nota / Motivo</th>
                                    <th>Documento afectado</th>
                                    <th>Estado</th>
                                    <th>Impacto</th>
                                </tr>
                                <tr v-for="note in notes" :key="'n-' + note.id"
                                    :class="{ 'text-danger': note.tipo === 'Nota de Crédito' && note.afecta_caja, 'text-success': note.tipo === 'Nota de Débito' && note.afecta_caja, 'text-white-dark': !note.afecta_caja }">
                                    <th class="font-medium whitespace-nowrap">{{ note.fecha }}</th>
                                    <td>
                                        <b>{{ note.numero }}</b> · {{ note.tipo }}<br>
                                        <span class="text-xs">{{ note.motivo }}<template v-if="note.descripcion"> — {{ note.descripcion }}</template></span>
                                    </td>
                                    <td>{{ note.doc_afectado }}</td>
                                    <td>
                                        {{ note.estado }}
                                        <span v-if="!note.afecta_caja" class="block text-xs italic">{{ note.sin_impacto }}</span>
                                    </td>
                                    <td class="text-right">
                                        <template v-if="note.afecta_caja">
                                            <template v-if="note.tipo === 'Nota de Crédito'">- </template>
                                            <template v-else>+ </template>{{ money(note.monto_soles) }}
                                        </template>
                                        <template v-else>{{ money(note.monto_soles) }}</template>
                                        <span v-if="note.moneda === 'USD'" class="block text-xs">({{ note.moneda }} {{ Number(note.monto).toFixed(2) }})</span>
                                    </td>
                                </tr>

                                <tr v-if="excluded.length > 0">
                                    <td class="text-center text-sm font-bold uppercase" colspan="5">No considerados en el cuadre</td>
                                </tr>
                                <tr v-if="excluded.length > 0">
                                    <th scope="col">Fecha</th>
                                    <th>Tienda</th>
                                    <th>Serie / Número</th>
                                    <th>Estado</th>
                                    <th>Motivo</th>
                                </tr>
                                <tr v-for="item in excluded" :key="'x-' + item.sale_id + item.numero" class="text-white-dark">
                                    <th class="font-medium whitespace-nowrap">{{ item.fecha }}</th>
                                    <td>{{ item.local }}</td>
                                    <td>{{ item.numero }}</td>
                                    <td>{{ item.estado || '-' }}</td>
                                    <td>{{ item.motivo_exclusion }}</td>
                                </tr>

                                <tr v-if="expenses.length > 0">
                                    <td class="text-center text-sm font-bold uppercase text-danger" colspan="5">Gastos</td>
                                </tr>
                                <tr v-if="expenses.length > 0" class="bg-danger/20 border-danger/20 uppercase">
                                    <td scope="col"><b>N° Documento</b></td>
                                    <td scope="col" colspan="3"><b>Motivo o Descripción</b></td>
                                    <td scope="col"><b>Monto</b></td>
                                </tr>
                                <tr v-for="expense in expenses" :key="'g-' + expense.id" class="bg-danger/20 border-danger/20">
                                    <td class="text-left">{{ expense.document }}</td>
                                    <td colspan="3" class="text-left">{{ expense.description }}</td>
                                    <td class="text-right">{{ money(expense.amount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
