import ExcelJS from 'exceljs';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { formatFechaReporte, formatMontoReporte, formatFechaHoraReporte, nombreArchivoReporte } from './treasuryReportExport';

/**
 * Exportación de la previsión de caja.
 *
 * Todo se genera en el navegador. El Excel (ExcelJS) lleva los datos en una
 * hoja y las gráficas como imágenes en otra; el PDF (jsPDF) lleva las mismas
 * gráficas y las tablas. Ambos archivos declaran quién los pidió y cuándo.
 */

const PRIMARY = [67, 97, 238];
const HEX_PRIMARY = '4361EE';
const GRIS = [107, 114, 128];

const porcentaje = (valor) => `${Math.round((Number(valor) || 0) * 100)} %`;

/** Metadatos del archivo exportado. */
export const construirMetaPrevision = ({ user, forecast, generatedAt = new Date() }) => ({
    requestedBy: user?.name || 'Usuario no identificado',
    email: user?.email || '—',
    generatedAt: formatFechaHoraReporte(generatedAt),
    date: generatedAt,
    period: `${formatFechaReporte(forecast?.from)} al ${formatFechaReporte(forecast?.to)}`,
    periodLabel: `Previsión de ${forecast?.days ?? 30} días`,
    currency: forecast?.currency || 'PEN',
    days: forecast?.days ?? 30,
    ratio: forecast?.collection_ratio ?? 1,
});

/** Tipos de movimiento de la previsión con su etiqueta en español. */
export const etiquetasTipo = {
    cuota: 'Cuotas por cobrar',
    cronograma: 'Cronogramas',
    orden: 'Órdenes de pago',
    recurrente: 'Gastos recurrentes',
    cargo_programado: 'Cargos programados',
    igv: 'IGV por pagar',
    ajuste: 'Ajustes manuales',
};

/**
 * Agrupa los ítems de la previsión por tipo y sentido.
 *
 * @returns {{ entradas: Array, salidas: Array }}
 */
export const composicionPrevision = (forecast) => {
    const acumulado = { in: {}, out: {} };

    (forecast?.rows || []).forEach((row) => {
        (row.items || []).forEach((item) => {
            const direccion = String(item.type) === 'cuota' || String(item.type) === 'cronograma' ? 'in' : 'out';

            acumulado[direccion][item.type] = (acumulado[direccion][item.type] || 0) + Number(item.amount || 0);
        });
    });

    const aLista = (grupo) =>
        Object.entries(grupo)
            .map(([tipo, total]) => ({
                tipo,
                label: etiquetasTipo[tipo] || tipo,
                total: Math.round(total * 100) / 100,
            }))
            .filter((fila) => fila.total > 0)
            .sort((a, b) => b.total - a.total);

    return { entradas: aLista(acumulado.in), salidas: aLista(acumulado.out) };
};

/**
 * Genera y descarga la previsión en Excel con sus gráficas.
 *
 * @param {Object} opciones
 * @param {Object} opciones.forecast
 * @param {Object} opciones.meta
 * @param {{flujo?: string, composicion?: string}} opciones.charts  Data URIs PNG.
 */
export const exportarPrevisionExcel = async ({ forecast, meta, charts = {} }) => {
    const libro = new ExcelJS.Workbook();
    libro.creator = 'ARACODE · Tesorería';
    libro.created = meta.date;

    const formatoMoneda = meta.currency === 'PEN' ? '"S/" #,##0.00' : '"$" #,##0.00';
    const hoja = libro.addWorksheet('Previsión', { views: [{ showGridLines: false }] });

    hoja.columns = [{ width: 30 }, { width: 20 }, { width: 20 }, { width: 20 }, { width: 20 }];

    // Encabezado del archivo
    hoja.mergeCells('A1:E1');
    const titulo = hoja.getCell('A1');
    titulo.value = 'PREVISIÓN DE CAJA';
    titulo.font = { bold: true, size: 15, color: { argb: 'FFFFFFFF' } };
    titulo.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: `FF${HEX_PRIMARY}` } };
    titulo.alignment = { vertical: 'middle' };
    hoja.getRow(1).height = 26;

    const datosCabecera = [
        ['Solicitado por:', meta.requestedBy],
        ['Correo:', meta.email],
        ['Fecha y hora del reporte:', meta.generatedAt],
        ['Periodo:', `${meta.periodLabel} (${meta.period})`],
        ['Moneda:', meta.currency],
        ['Cobranza esperada:', porcentaje(meta.ratio)],
    ];

    datosCabecera.forEach(([etiqueta, valor]) => {
        const fila = hoja.addRow([etiqueta, valor]);
        fila.getCell(1).font = { bold: true, size: 10 };
        fila.getCell(2).font = { size: 10 };
    });

    hoja.addRow([]);

    // Resumen de la previsión
    const filaResumen = hoja.addRow([`RESUMEN (${meta.currency})`]);
    filaResumen.getCell(1).font = { bold: true, size: 11, color: { argb: `FF${HEX_PRIMARY}` } };

    const resumen = [
        ['Saldo inicial', Number(forecast.opening_balance || 0)],
        ['Entradas estimadas', Number(forecast.totals?.inflows || 0)],
        ['Salidas comprometidas', Number(forecast.totals?.outflows || 0)],
        ['Neto del periodo', Number(forecast.totals?.net || 0)],
        ['Saldo proyectado al cierre', Number(forecast.totals?.closing || 0)],
        ['Saldo mínimo proyectado', Number(forecast.min_balance || 0)],
        ['Fecha del saldo mínimo', formatFechaReporte(forecast.min_date)],
        ['Cuentas consideradas', Number(forecast.accounts_count || 0)],
    ];

    resumen.forEach(([concepto, valor]) => {
        const fila = hoja.addRow([concepto, valor]);
        fila.getCell(1).font = { bold: true };
        if (typeof valor === 'number') {
            fila.getCell(2).numFmt = formatoMoneda;
        }
    });

    hoja.addRow([]);

    // Composición de entradas y salidas
    const composicion = composicionPrevision(forecast);

    [
        ['COMPOSICIÓN DE ENTRADAS', composicion.entradas],
        ['COMPOSICIÓN DE SALIDAS', composicion.salidas],
    ].forEach(([tituloGrupo, filas]) => {
        const encabezado = hoja.addRow([tituloGrupo, 'Importe']);
        encabezado.getCell(1).font = { bold: true, size: 11, color: { argb: `FF${HEX_PRIMARY}` } };
        encabezado.getCell(2).font = { bold: true, size: 11 };

        if (filas.length === 0) {
            hoja.addRow(['Sin movimientos proyectados']);
        }

        filas.forEach((fila) => {
            const row = hoja.addRow([fila.label, fila.total]);
            row.getCell(2).numFmt = formatoMoneda;
        });

        hoja.addRow([]);
    });

    // Flujo diario
    const tituloFlujo = hoja.addRow(['FLUJO PROYECTADO POR DÍA']);
    tituloFlujo.getCell(1).font = { bold: true, size: 11, color: { argb: `FF${HEX_PRIMARY}` } };

    const cabeceraFlujo = hoja.addRow(['Fecha', 'Detalle', 'Entradas', 'Salidas', 'Saldo']);
    cabeceraFlujo.eachCell((cell) => {
        cell.font = { bold: true, color: { argb: 'FFFFFFFF' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: `FF${HEX_PRIMARY}` } };
    });

    (forecast.rows || []).forEach((row) => {
        const fila = hoja.addRow([
            formatFechaReporte(row.date),
            (row.items || []).map((item) => item.concept).join(' · ') || '—',
            Number(row.inflows || 0),
            Number(row.outflows || 0),
            Number(row.balance || 0),
        ]);

        [3, 4, 5].forEach((columna) => {
            fila.getCell(columna).numFmt = formatoMoneda;
        });
    });

    if (forecast.warnings?.length) {
        hoja.addRow([]);
        const avisos = hoja.addRow(['AVISOS']);
        avisos.getCell(1).font = { bold: true, color: { argb: 'FFB45309' } };
        forecast.warnings.forEach((aviso) => hoja.addRow([aviso]));
    }

    // Hoja de gráficos: las mismas imágenes que se ven en pantalla.
    if (charts.flujo || charts.composicion) {
        const hojaGraficos = libro.addWorksheet('Gráficos', { views: [{ showGridLines: false }] });
        hojaGraficos.columns = [{ width: 4 }, { width: 120 }];

        const tituloGraficos = hojaGraficos.getCell('B2');
        tituloGraficos.value = `GRÁFICAS DE LA PREVISIÓN (${meta.currency})`;
        tituloGraficos.font = { bold: true, size: 14, color: { argb: `FF${HEX_PRIMARY}` } };

        let fila = 4;

        if (charts.flujo) {
            const id = libro.addImage({ base64: charts.flujo, extension: 'png' });
            hojaGraficos.addImage(id, { tl: { col: 1, row: fila }, ext: { width: 780, height: 300 } });
            fila += 17;
        }

        if (charts.composicion) {
            const id = libro.addImage({ base64: charts.composicion, extension: 'png' });
            hojaGraficos.addImage(id, { tl: { col: 1, row: fila }, ext: { width: 560, height: 340 } });
        }
    }

    const buffer = await libro.xlsx.writeBuffer();
    descargarArchivo(
        new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }),
        nombreArchivoReporte('xlsx', meta.date).replace('reporte-tesoreria', 'prevision-tesoreria'),
    );
};

/**
 * Genera y descarga la previsión en PDF (A4 horizontal) con sus gráficas.
 */
export const exportarPrevisionPdf = ({ forecast, meta, charts = {} }) => {
    const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
    const ancho = doc.internal.pageSize.getWidth();
    const alto = doc.internal.pageSize.getHeight();
    const margen = 40;

    doc.setFillColor(...PRIMARY);
    doc.rect(0, 0, ancho, 62, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(16);
    doc.text('PREVISIÓN DE CAJA', margen, 28);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    doc.text('Sistema ARACODE · Módulo de Tesorería', margen, 46);

    doc.setTextColor(31, 41, 55);
    doc.setFontSize(9);
    doc.text(`Solicitado por: ${meta.requestedBy}`, margen, 82);
    doc.text(`Correo: ${meta.email}`, margen, 96);
    doc.text(`Fecha y hora del reporte: ${meta.generatedAt}`, margen, 110);

    doc.text(`Periodo: ${meta.periodLabel} (${meta.period})`, ancho / 2, 82);
    doc.text(`Moneda: ${meta.currency}`, ancho / 2, 96);
    doc.text(`Cobranza esperada: ${porcentaje(meta.ratio)}`, ancho / 2, 110);

    let y = 130;

    autoTable(doc, {
        startY: y,
        head: [['Concepto', 'Importe']],
        body: [
            ['Saldo inicial', formatMontoReporte(forecast.opening_balance, meta.currency)],
            ['Entradas estimadas', formatMontoReporte(forecast.totals?.inflows, meta.currency)],
            ['Salidas comprometidas', formatMontoReporte(forecast.totals?.outflows, meta.currency)],
            ['Neto del periodo', formatMontoReporte(forecast.totals?.net, meta.currency)],
            ['Saldo proyectado al cierre', formatMontoReporte(forecast.totals?.closing, meta.currency)],
            [
                `Saldo mínimo (${formatFechaReporte(forecast.min_date)})`,
                formatMontoReporte(forecast.min_balance, meta.currency),
            ],
        ],
        theme: 'grid',
        margin: { left: margen, right: ancho / 2 + 20 },
        styles: { fontSize: 8, cellPadding: 4, textColor: [31, 41, 55] },
        headStyles: { fillColor: PRIMARY, textColor: [255, 255, 255], fontStyle: 'bold' },
        columnStyles: { 1: { halign: 'right' } },
    });

    y = doc.lastAutoTable.finalY + 18;

    // Gráficas: se insertan las mismas imágenes que el usuario ve en pantalla.
    const anchoGrafico = ancho - margen * 2;

    if (charts.flujo) {
        const altoGrafico = anchoGrafico * 0.4;

        if (y + altoGrafico > alto - 60) {
            doc.addPage();
            y = margen;
        }

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(10);
        doc.setTextColor(...PRIMARY);
        doc.text('Flujo proyectado por día', margen, y);
        y += 8;

        doc.addImage(charts.flujo, 'PNG', margen, y, anchoGrafico, altoGrafico);
        y += altoGrafico + 16;
    }

    if (charts.composicion) {
        const altoGrafico = anchoGrafico * 0.5;

        if (y + altoGrafico > alto - 60) {
            doc.addPage();
            y = margen;
        }

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(10);
        doc.setTextColor(...PRIMARY);
        doc.text('Composición de entradas y salidas', margen, y);
        y += 8;

        const anchoComposicion = anchoGrafico * 0.62;

        doc.addImage(charts.composicion, 'PNG', margen, y, anchoComposicion, altoGrafico);
        y += altoGrafico + 16;
    }

    // Flujo diario
    if (y + 60 > alto - 40) {
        doc.addPage();
        y = margen;
    }

    const filas = (forecast.rows || []).map((row) => [
        formatFechaReporte(row.date),
        (row.items || []).map((item) => item.concept).join(' · ') || '—',
        formatMontoReporte(row.inflows, meta.currency),
        formatMontoReporte(row.outflows, meta.currency),
        formatMontoReporte(row.balance, meta.currency),
    ]);

    autoTable(doc, {
        startY: y + 14,
        head: [['Fecha', 'Detalle', 'Entradas', 'Salidas', 'Saldo']],
        body: filas,
        theme: 'grid',
        margin: { left: margen, right: margen },
        styles: { fontSize: 7.5, cellPadding: 3, textColor: [31, 41, 55] },
        headStyles: { fillColor: PRIMARY, textColor: [255, 255, 255], fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [244, 246, 251] },
        columnStyles: { 2: { halign: 'right' }, 3: { halign: 'right' }, 4: { halign: 'right' } },
        didDrawPage: () => {
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(10);
            doc.setTextColor(...PRIMARY);
            doc.text('Flujo proyectado por día', margen, 24);
        },
    });

    // Avisos y pie
    let avisoY = doc.lastAutoTable.finalY + 16;

    (forecast.warnings || []).forEach((aviso) => {
        if (avisoY > alto - 50) {
            doc.addPage();
            avisoY = margen;
        }
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(180, 83, 9);
        doc.text(`Aviso: ${aviso}`, margen, avisoY);
        avisoY += 12;
    });

    const totalPaginas = doc.internal.getNumberOfPages();

    for (let pagina = 1; pagina <= totalPaginas; pagina++) {
        doc.setPage(pagina);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(...GRIS);
        doc.text(`Solicitado por ${meta.requestedBy} · ${meta.generatedAt}`, margen, alto - 20);
        doc.text(`Página ${pagina} de ${totalPaginas}`, ancho - margen, alto - 20, { align: 'right' });
    }

    doc.save(nombreArchivoReporte('pdf', meta.date).replace('reporte-tesoreria', 'prevision-tesoreria'));
};

/** Descarga un Blob en el navegador. */
const descargarArchivo = (blob, nombre) => {
    const url = URL.createObjectURL(blob);
    const enlace = document.createElement('a');
    enlace.href = url;
    enlace.download = nombre;
    document.body.appendChild(enlace);
    enlace.click();
    enlace.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1500);
};
