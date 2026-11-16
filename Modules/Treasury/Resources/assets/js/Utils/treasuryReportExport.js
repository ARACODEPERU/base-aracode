import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

/**
 * Exportación de los reportes de Tesorería.
 *
 * Todo se genera en el navegador: Excel con SheetJS y PDF con jsPDF +
 * jspdf-autotable. Cada archivo lleva en su cabecera quién lo pidió, su
 * correo y la fecha y hora exactas de generación, además de los filtros
 * aplicados (periodo, moneda y centro de costo).
 */

const PRIMARY = [67, 97, 238];
const GRIS = [107, 114, 128];

const dosDigitos = (valor) => String(valor).padStart(2, '0');

/** 'Y-m-d' o Date -> 'dd/mm/aaaa'. */
export const formatFechaReporte = (valor) => {
    if (!valor) return '—';

    if (valor instanceof Date) {
        return `${dosDigitos(valor.getDate())}/${dosDigitos(valor.getMonth() + 1)}/${valor.getFullYear()}`;
    }

    const [year, month, day] = String(valor).split('-');

    return year && month && day ? `${day}/${month}/${year}` : String(valor);
};

/** Fecha y hora legibles: 16/11/2026 14:32. */
export const formatFechaHoraReporte = (valor = new Date()) => {
    const fecha = valor instanceof Date ? valor : new Date(valor);

    return `${formatFechaReporte(fecha)} ${dosDigitos(fecha.getHours())}:${dosDigitos(fecha.getMinutes())}`;
};

export const formatMontoReporte = (valor, currency = 'PEN') => {
    try {
        return new Intl.NumberFormat('es-PE', { style: 'currency', currency }).format(valor || 0);
    } catch (e) {
        return `${currency} ${Number(valor || 0).toFixed(2)}`;
    }
};

/** Nombre de archivo sin caracteres problemáticos. */
export const nombreArchivoReporte = (extension, fecha = new Date()) => {
    const sello = `${fecha.getFullYear()}${dosDigitos(fecha.getMonth() + 1)}${dosDigitos(fecha.getDate())}-${dosDigitos(fecha.getHours())}${dosDigitos(fecha.getMinutes())}`;

    return `reporte-tesoreria-${sello}.${extension}`;
};

/**
 * Metadatos que acompañan a todo reporte exportado.
 *
 * @param {Object} opciones
 * @param {Object} opciones.user           Usuario autenticado (name, email).
 * @param {Object} opciones.filters        Filtros aplicados (from, to...).
 * @param {String} opciones.currency       Moneda del flujo.
 * @param {String} opciones.costCenter     Etiqueta del centro de costo o "Todos".
 * @param {Date}   opciones.generatedAt    Momento de generación.
 */
export const construirMetaReporte = ({
    user,
    filters,
    currency,
    costCenter,
    periodLabel = 'Rango personalizado',
    generatedAt = new Date(),
}) => ({
    requestedBy: user?.name || 'Usuario no identificado',
    email: user?.email || '—',
    generatedAt: formatFechaHoraReporte(generatedAt),
    date: generatedAt,
    period: `${formatFechaReporte(filters?.from)} al ${formatFechaReporte(filters?.to)}`,
    periodLabel,
    currency: currency || 'PEN',
    costCenter: costCenter || 'Todos',
});

/** Filas comunes de cabecera usadas en el Excel. */
const filasCabecera = (meta) => [
    ['REPORTE DE TESORERÍA'],
    ['Solicitado por:', meta.requestedBy],
    ['Correo:', meta.email],
    ['Fecha y hora del reporte:', meta.generatedAt],
    ['Periodo:', `${meta.periodLabel} (${meta.period})`],
    ['Moneda del flujo:', meta.currency],
    ['Centro de costo:', meta.costCenter],
    [],
];

/**
 * Genera y descarga el reporte en Excel (.xlsx).
 *
 * Los importes se escriben como números (no texto) para que el usuario pueda
 * sumarlos o filtrarlos en Excel.
 */
export const exportarReporteExcel = ({
    position,
    flows,
    byCostCenter,
    totalsByCurrency,
    projection,
    resumenProyeccion,
    meta,
}) => {
    const filas = [...filasCabecera(meta)];

    filas.push([`POSICIÓN CONSOLIDADA EN ${position.base_currency}`]);
    filas.push(['Cuenta', 'Moneda', 'Saldo']);
    (position.balances || []).forEach((cuenta) => {
        filas.push([cuenta.label, cuenta.currency, Number(cuenta.balance || 0)]);
    });
    filas.push(['Consolidado', position.base_currency, Number(position.consolidated || 0)]);
    if (position.incomplete) {
        filas.push(['Aviso: faltan tipos de cambio para consolidar todas las monedas.']);
    }
    filas.push([]);

    filas.push(['RESUMEN DEL PERIODO POR MONEDA']);
    filas.push(['Moneda', 'Ingresos', 'Egresos', 'Neto', 'Movimientos']);
    (totalsByCurrency || []).forEach((row) => {
        filas.push([
            row.currency,
            Number(row.incomes || 0),
            Number(row.expenses || 0),
            Number(row.net || 0),
            Number(row.movements || 0),
        ]);
    });
    filas.push([]);

    filas.push([`FLUJO POR CATEGORÍA (${flows.currency})`]);
    filas.push(['Categoría', 'Tipo', 'Movimientos', 'Total']);
    (flows.rows || []).forEach((row) => {
        filas.push([
            row.category,
            row.type === 'income' ? 'Ingreso' : 'Egreso',
            Number(row.movements || 0),
            Number(row.total || 0),
        ]);
    });
    filas.push(['Totales', '', '', Number(flows.net || 0)]);
    filas.push(['Ingresos', '', '', Number(flows.incomes || 0)]);
    filas.push(['Egresos', '', '', Number(flows.expenses || 0)]);
    filas.push([]);

    filas.push([`POR CENTRO DE COSTO (${flows.currency})`]);
    filas.push(['Centro de costo', 'Ingresos', 'Egresos', 'Neto', 'Movimientos']);
    (byCostCenter || []).forEach((row) => {
        filas.push([
            row.center,
            Number(row.incomes || 0),
            Number(row.expenses || 0),
            Number(row.net || 0),
            Number(row.movements || 0),
        ]);
    });

    // Proyección: lo que se espera cobrar y pagar en el horizonte elegido.
    if (projection) {
        filas.push([]);
        filas.push([`PROYECCIÓN DE CAJA (${projection.currency}) · ${projection.days} DÍAS`]);
        filas.push(['Saldo inicial', Number(projection.opening_balance || 0)]);
        filas.push(['Ingresos esperados', Number(projection.totals?.inflows || 0)]);
        filas.push(['Egresos proyectados', Number(projection.totals?.outflows || 0)]);
        filas.push(['Saldo al cierre', Number(projection.totals?.closing || 0)]);
        filas.push(['Saldo mínimo proyectado', Number(projection.min_balance || 0), projection.min_date || '']);
        filas.push([]);
        filas.push(['Resumen mensual de la proyección']);
        filas.push(['Mes', 'Ingresos esperados', 'Egresos proyectados', 'Neto', 'Saldo al cierre']);
        (resumenProyeccion || []).forEach((fila) => {
            filas.push([
                fila.label,
                Number(fila.inflows || 0),
                Number(fila.outflows || 0),
                Number(fila.net || 0),
                Number(fila.balance || 0),
            ]);
        });
    }

    const hoja = XLSX.utils.aoa_to_sheet(filas);
    hoja['!cols'] = [{ wch: 42 }, { wch: 16 }, { wch: 16 }, { wch: 16 }, { wch: 14 }];

    const libro = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(libro, hoja, 'Reporte');

    XLSX.writeFile(libro, nombreArchivoReporte('xlsx', meta.date));
};

/**
 * Genera y descarga el reporte en PDF (A4 horizontal).
 *
 * Cada página repite en el pie quién pidió el reporte y cuándo se generó,
 * para que una hoja suelta siga siendo trazable.
 */
export const exportarReportePdf = ({
    position,
    flows,
    byCostCenter,
    totalsByCurrency,
    projection,
    resumenProyeccion,
    meta,
}) => {
    const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
    const ancho = doc.internal.pageSize.getWidth();
    const margen = 40;

    // Banda de título
    doc.setFillColor(...PRIMARY);
    doc.rect(0, 0, ancho, 62, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(16);
    doc.text('REPORTE DE TESORERÍA', margen, 28);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    doc.text('Sistema ARACODE · Módulo de Tesorería', margen, 46);

    // Identificación del pedido
    doc.setTextColor(31, 41, 55);
    doc.setFontSize(9);
    doc.text(`Solicitado por: ${meta.requestedBy}`, margen, 82);
    doc.text(`Correo: ${meta.email}`, margen, 96);
    doc.text(`Fecha y hora del reporte: ${meta.generatedAt}`, margen, 110);

    doc.text(`Periodo: ${meta.periodLabel} (${meta.period})`, ancho / 2, 82);
    doc.text(`Moneda del flujo: ${meta.currency}`, ancho / 2, 96);
    doc.text(`Centro de costo: ${meta.costCenter}`, ancho / 2, 110);

    const dibujarTabla = (titulo, cabecera, cuerpo, inicioY, alineacion = {}) => {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(10);
        doc.setTextColor(...PRIMARY);
        doc.text(titulo, margen, inicioY);

        autoTable(doc, {
            startY: inicioY + 8,
            head: [cabecera],
            body: cuerpo,
            theme: 'grid',
            margin: { left: margen, right: margen },
            styles: { fontSize: 8, cellPadding: 4, textColor: [31, 41, 55] },
            headStyles: { fillColor: PRIMARY, textColor: [255, 255, 255], fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [244, 246, 251] },
            columnStyles: alineacion,
        });

        return doc.lastAutoTable.finalY + 22;
    };

    let y = 132;

    y = dibujarTabla(
        `Posición consolidada en ${position.base_currency}`,
        ['Cuenta', 'Moneda', 'Saldo'],
        [
            ...(position.balances || []).map((cuenta) => [
                cuenta.label,
                cuenta.currency,
                formatMontoReporte(cuenta.balance, cuenta.currency),
            ]),
            ['Consolidado', position.base_currency, formatMontoReporte(position.consolidated, position.base_currency)],
        ],
        y,
        { 2: { halign: 'right' } },
    );

    y = dibujarTabla(
        'Resumen del periodo por moneda',
        ['Moneda', 'Ingresos', 'Egresos', 'Neto', 'Movimientos'],
        (totalsByCurrency || []).map((row) => [
            row.currency,
            formatMontoReporte(row.incomes, row.currency),
            formatMontoReporte(row.expenses, row.currency),
            formatMontoReporte(row.net, row.currency),
            String(row.movements || 0),
        ]),
        y,
        { 1: { halign: 'right' }, 2: { halign: 'right' }, 3: { halign: 'right' }, 4: { halign: 'right' } },
    );

    y = dibujarTabla(
        `Flujo por categoría (${flows.currency})`,
        ['Categoría', 'Tipo', 'Movimientos', 'Total'],
        [
            ...(flows.rows || []).map((row) => [
                row.category,
                row.type === 'income' ? 'Ingreso' : 'Egreso',
                String(row.movements || 0),
                formatMontoReporte(row.total, flows.currency),
            ]),
            ['Ingresos', '', '', formatMontoReporte(flows.incomes, flows.currency)],
            ['Egresos', '', '', formatMontoReporte(flows.expenses, flows.currency)],
            ['Neto', '', '', formatMontoReporte(flows.net, flows.currency)],
        ],
        y,
        { 2: { halign: 'right' }, 3: { halign: 'right' } },
    );

    y = dibujarTabla(
        `Por centro de costo (${flows.currency})`,
        ['Centro de costo', 'Ingresos', 'Egresos', 'Neto', 'Movimientos'],
        (byCostCenter || []).map((row) => [
            row.center,
            formatMontoReporte(row.incomes, flows.currency),
            formatMontoReporte(row.expenses, flows.currency),
            formatMontoReporte(row.net, flows.currency),
            String(row.movements || 0),
        ]),
        y,
        { 1: { halign: 'right' }, 2: { halign: 'right' }, 3: { halign: 'right' }, 4: { halign: 'right' } },
    );

    if (projection) {
        y = dibujarTabla(
            `Proyección de caja (${projection.currency}) · ${projection.days} días`,
            ['Concepto', 'Importe'],
            [
                ['Saldo inicial', formatMontoReporte(projection.opening_balance, projection.currency)],
                ['Ingresos esperados', formatMontoReporte(projection.totals?.inflows, projection.currency)],
                ['Egresos proyectados', formatMontoReporte(projection.totals?.outflows, projection.currency)],
                ['Saldo al cierre', formatMontoReporte(projection.totals?.closing, projection.currency)],
                ['Saldo mínimo proyectado', formatMontoReporte(projection.min_balance, projection.currency)],
            ],
            y,
            { 1: { halign: 'right' } },
        );

        dibujarTabla(
            'Resumen mensual de la proyección',
            ['Mes', 'Ingresos esperados', 'Egresos proyectados', 'Neto', 'Saldo al cierre'],
            (resumenProyeccion || []).map((fila) => [
                fila.label,
                formatMontoReporte(fila.inflows, projection.currency),
                formatMontoReporte(fila.outflows, projection.currency),
                formatMontoReporte(fila.net, projection.currency),
                formatMontoReporte(fila.balance, projection.currency),
            ]),
            y,
            { 1: { halign: 'right' }, 2: { halign: 'right' }, 3: { halign: 'right' }, 4: { halign: 'right' } },
        );
    }

    // Pie en todas las páginas
    const totalPaginas = doc.internal.getNumberOfPages();

    for (let pagina = 1; pagina <= totalPaginas; pagina++) {
        doc.setPage(pagina);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(...GRIS);
        doc.text(
            `Solicitado por ${meta.requestedBy} · ${meta.generatedAt}`,
            margen,
            doc.internal.pageSize.getHeight() - 20,
        );
        doc.text(
            `Página ${pagina} de ${totalPaginas}`,
            ancho - margen,
            doc.internal.pageSize.getHeight() - 20,
            { align: 'right' },
        );
    }

    doc.save(nombreArchivoReporte('pdf', meta.date));
};
