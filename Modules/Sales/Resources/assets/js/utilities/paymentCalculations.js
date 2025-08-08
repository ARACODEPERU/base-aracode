// En tu utilities/paymentCalculations.js (o donde tengas la función)
import Swal from 'sweetalert2'; // Asegúrate de importar SweetAlert2
// utilities/paymentCalculations.js (si lo pones en un archivo separado)
export const calcularMontosPorCuota = (montoTotal, numeroCuotas, fechaEmision, endOfMonth, daysInterval) => {
    montoTotal = parseFloat(montoTotal);
    numeroCuotas = parseInt(numeroCuotas);
    daysInterval = parseInt(daysInterval);

    if (isNaN(montoTotal) || isNaN(numeroCuotas) || numeroCuotas <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error de Cálculo',
            text: 'El monto total o el número de cuotas no son válidos. Por favor, revisa los datos.',
            confirmButtonText: 'Entendido'
        });
        return [];

    }
    if (!fechaEmision || isNaN(new Date(fechaEmision))) {
        Swal.fire({
            icon: 'error',
            title: 'Error de Fecha',
            text: 'La fecha de emisión del documento no es válida. Por favor, corrígela.',
            confirmButtonText: 'Entendido'
        });
        return [];
    }
    if (!endOfMonth && (isNaN(daysInterval) || daysInterval <= 0 || daysInterval > 31)) {
        Swal.fire({
            icon: 'error',
            title: 'Error de Intervalo de Días',
            text: 'El número de días para el intervalo de cuotas no es válido (debe ser entre 1 y 31).',
            confirmButtonText: 'Entendido'
        });
        return [];
    }

    const montoBasePorCuota = Math.floor(montoTotal / numeroCuotas);
    const residuo = montoTotal % numeroCuotas;
    const cuotas = [];

    let fechaBase = new Date(fechaEmision);

    for (let i = 0; i < numeroCuotas; i++) {
        let montoCuota = montoBasePorCuota;
        if (i < residuo) {
            montoCuota++;
        }

        let fechaVencimiento = new Date(fechaBase);

        if (endOfMonth) {
            fechaVencimiento.setMonth(fechaBase.getMonth() + (i + 1));
            fechaVencimiento.setDate(0); // Último día del mes anterior (el mes al que avanzamos)
        } else {
            // Suma los días de intervalo desde la fecha de emisión para cada cuota
            fechaVencimiento.setDate(fechaBase.getDate() + (daysInterval * (i + 1)));
        }

        cuotas.push({
            id: i,
            amount: montoCuota,
            dueDate: fechaVencimiento.toISOString().split('T')[0], // Formato YYYY-MM-DD
        });
    }

    return cuotas;
};
