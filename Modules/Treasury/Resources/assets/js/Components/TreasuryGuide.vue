<script setup>
    import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
    import { usePage } from '@inertiajs/vue3';
    import { faRobot, faArrowRight } from '@fortawesome/free-solid-svg-icons';

    /**
     * Guía de Aracodito por pestaña de Tesorería.
     *
     * Se monta una sola vez en el layout, pero solo actúa en las rutas de
     * Tesorería: marca la vista como nueva para que las alertas salgan con
     * Aracodito y ofrece un botón con la explicación de cada pantalla.
     */
    const page = usePage();

    const guias = {
        dashboard: {
            titulo: 'Dashboard',
            para: 'Resumen del dinero de la empresa: saldo por moneda, consolidado, ingresos y egresos del mes y pagos pendientes.',
            hacer: [
                'Revisa primero el saldo de cada moneda, no solo el consolidado.',
                'Si una cuenta está baja, entra a Previsión para ver qué vence.',
                'Usa «Saldos por cuenta» para detectar cuentas desactivadas o sin movimientos.',
            ],
            evitar: [
                'No decidas pagos mirando solo el consolidado: mezcla monedas convertidas.',
                'No uses este saldo como «disponible» si hay pagos aprobados por ejecutar.',
            ],
            clave: 'Tesorería cuenta desde la fecha de inicio del módulo; lo anterior no suma.',
        },
        accounts: {
            titulo: 'Cuentas',
            para: 'Cuentas de tesorería de la empresa (banco, billetera o caja), sus comisiones, ITF y saldo inicial.',
            hacer: [
                'Crea las cuentas que falten con «Crear cuentas según parámetros».',
                'Asigna cada método de pago a su cuenta para que los cobros caigan solos.',
                'Registra el saldo inicial con su fecha real de corte.',
                'Si la billetera tarda en pasar el dinero al banco, configura sus días hábiles: el cobro queda en la billetera y se transfiere solo en la fecha acordada.',
            ],
            evitar: [
                'No cambies el saldo inicial sin un respaldo: altera todos los reportes.',
                'No borres una cuenta con movimientos: el sistema la desactiva para conservar el historial.',
            ],
            clave: 'La cuenta de Detracción no se crea ni se edita aquí: es del sistema. Las transferencias de billeteras con plazo aparecen en esta pantalla hasta que se ejecutan.',
        },
        transfers: {
            titulo: 'Transferencias',
            para: 'Mueve dinero entre cuentas propias aplicando ITF y comisiones (fijas o porcentuales), sin IGV.',
            hacer: [
                'Verifica la cuenta de origen y el saldo antes de confirmar.',
                'Escribe una referencia reconocible: banco, operación o motivo.',
                'Revisa el desglose de comisiones e ITF antes de guardar.',
            ],
            evitar: [
                'No transfieras entre la misma cuenta: el sistema lo bloquea.',
                'No uses transferencias para la cuenta de Detracción: tiene su propia liberación con contraseña.',
            ],
            clave: 'Una transferencia genera dos movimientos enlazados; anularla anula ambos.',
        },
        transactions: {
            titulo: 'Movimientos',
            para: 'Libro de una cuenta: ingresos y egresos con categoría, centro de costo, medio de pago y adjunto.',
            hacer: [
                'Registra cada gasto con su categoría y centro de costo para que los reportes sirvan.',
                'Adjunta el comprobante cuando exista.',
                'Filtra por fecha para revisar un periodo concreto.',
                'Si una factura desde S/ 700 muestra «Falta detracción», usa «Hacer auto-detracción» en su cobro: el importe pasa de esa cuenta a Detracción.',
            ],
            evitar: [
                'No dupliques movimientos que ya llegaron solos desde ventas, pagos o cobros.',
                'No edites movimientos anulados ni de una conciliación cerrada.',
                'No hagas la auto-detracción dos veces: la segunda intenta se rechaza.',
            ],
            clave: 'Si un cobro no apareció, revisa «Cobros sin cuenta» antes de crearlo a mano. Al emitir una factura desde S/ 700 se decide si el cliente deposita la detracción o si la empresa la transfiere después desde aquí.',
        },
        payments: {
            titulo: 'Pagos',
            para: 'Órdenes de pago a proveedores y gastos: se crean, se aprueban y recién después se ejecutan.',
            hacer: [
                'Adjunta el sustento antes de pedir aprobación.',
                'Respeta el flujo: crear → aprobar → pagar.',
                'Registra la fecha programada para que la previsión la considere.',
            ],
            evitar: [
                'No pagues una orden sin aprobación: el permiso es distinto a propósito.',
                'No anules una orden ya pagada: revierte el movimiento desde Movimientos.',
            ],
            clave: 'Aprobar y ejecutar son permisos separados: quien aprueba no necesariamente paga.',
        },
        forecast: {
            titulo: 'Previsión',
            para: 'Proyección del flujo de caja con los vencimientos de cobros, deudas, gastos e IGV.',
            hacer: [
                'Elige el horizonte (30, 60 o 90 días) según lo que quieras decidir.',
                'Revisa el saldo mínimo proyectado: es la señal de alerta.',
                'Exporta a Excel o PDF si necesitas compartirlo.',
            ],
            evitar: [
                'No tomes decisiones si el aviso dice que faltan tipos de cambio.',
                'No cuentes el saldo de Detracción como disponible: está restringido a IGV.',
            ],
            clave: 'La previsión no adivina: solo proyecta lo ya registrado y los vencimientos.',
        },
        cash: {
            titulo: 'Efectivo',
            para: 'Ventas cobradas en efectivo, sus egresos y el arqueo de cada sesión de caja.',
            hacer: [
                'Cuenta los billetes y monedas con «Arquear» y registra la diferencia.',
                'Si hay faltante o sobrante, explica el motivo en la nota.',
                'Cierra la sesión con el dinero contado cuando termines el turno.',
            ],
            evitar: [
                'No cierres sin contar: el saldo final queda con lo que declares.',
                'No intentes corregir un arqueo anterior: registra uno nuevo y explica.',
            ],
            clave: 'El arqueo es inmutable y queda auditado con tu usuario.',
        },
        documents: {
            titulo: 'Cartera',
            para: 'Cheques y letras por cobrar (de clientes) y por pagar (a proveedores), con su vencimiento.',
            hacer: [
                'Consulta el DNI o RUC del emisor para traer su nombre desde RENIEC o SUNAT.',
                'Registra el número, el emisor y el vencimiento tal como figuran en el documento.',
                'Marca «Depositado» al llevarlo al banco y «Cobrado» o «Pagado» cuando el dinero se mueva.',
                'Si vence sin pago, márcalo «Protestado» y deja la nota.',
            ],
            evitar: [
                'No lo uses como reemplazo del libro: el cobro o pago real se registra en Movimientos.',
                'No anules para corregir: anular es definitivo y queda auditado.',
            ],
            clave: 'Los documentos pendientes entran solos a la previsión de caja por su vencimiento.',
        },
        collections: {
            titulo: 'Cobros sin cuenta',
            para: 'Comprobantes ya emitidos que todavía no tienen su ingreso registrado en una cuenta.',
            hacer: [
                'Elige la cuenta real donde entró el dinero y vincula.',
                'Verifica el monto y la fecha del depósito.',
            ],
            evitar: [
                'No vincules un comprobante anulado: el sistema lo rechaza.',
                'No lo vincules dos veces: el segundo intento se bloquea.',
            ],
            clave: 'Vincular aquí crea el ingreso; no hace falta crearlo también en Movimientos.',
        },
        reconciliation: {
            titulo: 'Conciliación',
            para: 'Compara el libro con el estado de cuenta del banco y cierra con un acta inmutable.',
            hacer: [
                'Importa el extracto (CSV, Excel u OFX): el sistema propone las columnas y tú las confirmas.',
                'Revisa la comparación: las líneas que coinciden en monto y fecha quedan aceptadas.',
                'Registra como movimiento las líneas del banco que el libro no trae, o déjalas como partida.',
            ],
            evitar: [
                'No apliques la importación sin revisar el mapeo: una columna mal elegida empareja mal.',
                'No cierres con diferencias sin explicar: el acta queda inmutable.',
                'No intentes editar movimientos de una conciliación cerrada.',
            ],
            clave: 'El extracto compara el banco con el libro en su rango de fechas; las líneas sin par puedes convertirlas en movimientos o dejarlas como partidas.',
        },
        periods: {
            titulo: 'Periodos',
            para: 'Cierre contable de cada mes: bloquea movimientos para que los reportes no cambien.',
            hacer: [
                'Cierra el mes solo después de conciliar y registrar el IGV.',
                'Reabre únicamente con autorización y motivo.',
            ],
            evitar: [
                'No cierres un periodo con pagos pendientes de registrar.',
                'No reabras para «corregir» algo que se arregla con un ajuste.',
            ],
            clave: 'Un periodo cerrado impide registrar movimientos con esa fecha.',
        },
        detraction: {
            titulo: 'Detracción',
            para: 'Cuenta del Banco de la Nación con el 12 %, 10 % o 4 % de las facturas desde S/ 700. El IGV sigue siendo 18 % y solo se paga desde este saldo.',
            hacer: [
                'Registra el número de cuenta para que el cliente sepa dónde depositar.',
                'Úsala para pagar el IGV del periodo desde Impuestos.',
                'Libera el saldo a una cuenta de la empresa cuando SUNAT lo habilite.',
                'Si necesitas corregir el saldo, usa «Ajuste manual» con un motivo claro.',
            ],
            evitar: [
                'No la uses como caja ni para pagos distintos al IGV.',
                'No ajustes sin sustento: descuadra el IGV y queda auditado con tu usuario.',
            ],
            clave: 'Ajustar y liberar son críticos: piden tu contraseña y quedan en auditoría.',
        },
        taxes: {
            titulo: 'Impuestos',
            para: 'IGV de boletas y facturas emitidas del periodo, menos notas de crédito y detracción aplicada.',
            hacer: [
                'Registra el pago antes del vencimiento que muestra la pantalla.',
                'Revisa el desglose: IGV trasladado − notas − detracción.',
                'Puedes pagar desde la cuenta de Detracción si tiene saldo.',
            ],
            evitar: [
                'No pagues dos veces el mismo periodo desde la misma cuenta.',
                'No confíes en el IGV si el comprobante tiene el impuesto mal grabado.',
            ],
            clave: 'El IGV se calcula por fecha de emisión, no por fecha de cobro.',
        },
        reports: {
            titulo: 'Reportes',
            para: 'Movimientos y totales por rango de fechas, moneda y centro de costo, con exportación.',
            hacer: [
                'Fija bien el periodo antes de exportar.',
                'Compara siempre dentro de la misma moneda.',
            ],
            evitar: [
                'No compares meses sin revisar si hubo cierres o ajustes.',
            ],
            clave: 'El reporte respeta la fecha de inicio del módulo: no muestra lo anterior.',
        },
        audit: {
            titulo: 'Auditoría',
            para: 'Bitácora de solo lectura de las operaciones críticas del módulo.',
            hacer: [
                'Revisa ajustes, liberaciones, anulaciones y cambios de configuración.',
                'Usa el usuario y la IP para rastrear una operación.',
            ],
            evitar: [
                'No esperes poder borrar registros: es inmutable por diseño.',
            ],
            clave: 'Si algo no cuadra, empieza por aquí: dice quién hizo qué y cuándo.',
        },
        integration: {
            titulo: 'Integración',
            para: 'Bandeja de movimientos que no pudieron registrarse automáticamente (ventas, cobros, pagos).',
            hacer: [
                'Lee el error antes de reintentar.',
                'Reintenta después de corregir la causa (cuenta, moneda o fecha).',
            ],
            evitar: [
                'No reintentes en cadena sin leer: el error se repite.',
            ],
            clave: 'Los reintentos son idempotentes: no duplican el movimiento.',
        },
        categories: {
            titulo: 'Categorías',
            para: 'Clasificación de ingresos y egresos que usan los movimientos y los reportes.',
            hacer: [
                'Crea categorías con nombres claros y consistentes.',
                'Asigna el color para reconocerlas en los gráficos.',
            ],
            evitar: [
                'No borres categorías del sistema (Detracción, IGV por pagar, Ajuste de detracción).',
            ],
            clave: 'Una buena categorización es lo que hace útil el reporte por categoría.',
        },
        configuration: {
            titulo: 'Configuración',
            para: 'Puesta en marcha del módulo: activarlo o desactivarlo y fijar la fecha desde la que cuenta.',
            hacer: [
                'Define la fecha de inicio antes de empezar a operar.',
                'Ajusta el saldo inicial de cada cuenta al valor real a esa fecha: el corte no lo pone en cero.',
                'Cambia la configuración con tu contraseña y revisa el historial.',
            ],
            evitar: [
                'No desactives Tesorería en medio de un cierre o conciliación.',
                'No muevas la fecha de inicio para «cuadrar» un mes: usa un ajuste.',
            ],
            clave: 'El corte no toca conciliaciones, periodos cerrados ni auditoría: esas actas se conservan.',
        },
    };

    // Pestaña actual según la URL de Inertia.
    const slug = computed(() => {
        if (typeof window === 'undefined') return null;

        const path = new URL(page.url, window.location.origin).pathname.replace(/\/+$/, '');
        const match = path.match(/\/treasury\/([a-z-]+)$/);

        return match && guias[match[1]] ? match[1] : null;
    });

    const guia = computed(() => (slug.value ? guias[slug.value] : null));

    // Pista visual: una flecha señala el botón al entrar y se va sola. La guía
    // solo se abre cuando el usuario pulsa el botón.
    const pistaVisible = ref(false);
    let pistaTimer = null;

    const ocultarPista = () => {
        pistaVisible.value = false;

        if (pistaTimer) {
            clearTimeout(pistaTimer);
            pistaTimer = null;
        }
    };

    const mostrarPista = () => {
        if (!guia.value || typeof window === 'undefined') return;

        // Una vez por pestaña y sesión: no se repite al volver a entrar.
        const clave = `aracodito_treasury_hint_${slug.value}`;

        try {
            if (sessionStorage.getItem(clave)) return;
            sessionStorage.setItem(clave, '1');
        } catch (e) {
            // Sin almacenamiento: la pista se muestra igual, solo se repite.
        }

        pistaVisible.value = true;
        pistaTimer = setTimeout(ocultarPista, 5200);
    };

    const mostrar = () => {
        if (!guia.value || typeof window.alertaSistema !== 'function') return;

        ocultarPista();

        const texto = [
            guia.value.para,
            '',
            'QUÉ HACER',
            ...guia.value.hacer.map((item) => `• ${item}`),
            '',
            'QUÉ EVITAR',
            ...guia.value.evitar.map((item) => `• ${item}`),
            '',
            `CONSEJO: ${guia.value.clave}`,
        ].join('\n');

        window.alertaSistema(`Guía de ${guia.value.titulo}`, texto, 'info', { duration: 0 });
    };

    // La vista de Tesorería se comporta como vista nueva para Aracodito.
    watch(
        slug,
        (actual) => {
            if (typeof document === 'undefined') return;

            if (actual) {
                document.body.dataset.aracoditoView = 'new';
            } else if (document.body.dataset.aracoditoView === 'new') {
                delete document.body.dataset.aracoditoView;
            }

        },
        { immediate: true }
    );

    // La flecha aparece al entrar en la pestaña, sin abrir ningún aviso.
    onMounted(() => {
        mostrarPista();
    });

    onBeforeUnmount(ocultarPista);
</script>

<template>
    <div v-if="guia" class="fixed bottom-24 ltr:right-6 rtl:left-6 z-40 flex items-end gap-2">
        <!-- Pista: flecha grande que señala el botón y desaparece sola -->
        <transition name="pista">
            <div v-if="pistaVisible"
                class="pointer-events-none mb-9 flex items-center gap-2 rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-white shadow-xl ring-2 ring-warning/70">
                <span>Toca aquí para la guía</span>
                <font-awesome-icon :icon="faArrowRight" class="pista-flecha text-3xl text-warning" />
            </div>
        </transition>

        <div class="relative">
            <!-- Latido alrededor del botón mientras dura la pista -->
            <span v-if="pistaVisible" class="pointer-events-none absolute inset-0 animate-ping rounded-full bg-primary/60"></span>

            <button type="button" class="btn btn-primary relative rounded-full shadow-lg gap-2 px-4 py-3" @click="mostrar"
                :title="`Guía de Aracodito: ${guia.titulo}`">
                <font-awesome-icon :icon="faRobot" />
                <span class="hidden sm:inline">Guía</span>
            </button>
        </div>
    </div>
</template>

<style scoped>
    /* Entrada y salida suaves de la pista que señala el botón. */
    .pista-enter-active,
    .pista-leave-active {
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .pista-enter-from,
    .pista-leave-to {
        opacity: 0;
        transform: translateX(8px);
    }

    /* La flecha rebota en diagonal, hacia el botón. */
    @keyframes pistaFlecha {
        0%,
        100% {
            transform: rotate(45deg) translate(0, 0) scale(1);
        }

        50% {
            transform: rotate(45deg) translate(4px, 4px) scale(1.18);
        }
    }

    .pista-flecha {
        animation: pistaFlecha 0.9s ease-in-out infinite;
    }
</style>
