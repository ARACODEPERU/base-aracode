<?php

namespace App\Helpers\Invoice\Documents;

/**
 * Traduce los códigos y mensajes crudos que devuelve Greenter/SUNAT al
 * procesar una Comunicación de Baja a un resultado normalizado y a una
 * alerta comprensible para el usuario final.
 *
 * La clase no toca base de datos ni red, por lo que se puede probar de forma
 * aislada.
 */
class LowCommunicationAlerts
{
    /** Resultados normalizados. */
    public const RESULT_ACEPTADO = 'aceptado';

    public const RESULT_RECHAZADO = 'rechazado';

    public const RESULT_EN_PROCESO = 'en_proceso';

    public const RESULT_NO_DISPONIBLE = 'sunat_no_disponible';

    public const RESULT_YA_PRESENTADO = 'ya_presentado';

    public const RESULT_PLAZO_VENCIDO = 'plazo_vencido';

    public const RESULT_ERROR_LOCAL = 'error_local';

    /** Acciones sugeridas al usuario. */
    public const ACTION_NONE = 'ninguna';

    public const ACTION_RECHECK = 'reconsultar';

    public const ACTION_RETRY = 'reintentar';

    public const ACTION_NEW_VOID = 'regenerar_baja';

    public const ACTION_CREDIT_NOTE = 'emitir_nota_credito';

    public const ACTION_FIX = 'corregir_y_reenviar';

    public const ACTION_SUPPORT = 'contactar_soporte';

    /**
     * Estado que se guarda en sale_low_communications.status para cada
     * resultado. Se mantiene el vocabulario histórico ('Enviado', 'Aceptado',
     * 'Rechazado', 'registrado') y se agregan los dos estados que ya usa el
     * resumen diario ('sunat_disponible', 'fue_enviado') para no romper datos
     * existentes.
     */
    public static function statusFor(string $result): string
    {
        return match ($result) {
            self::RESULT_ACEPTADO => 'Aceptado',
            self::RESULT_RECHAZADO, self::RESULT_PLAZO_VENCIDO => 'Rechazado',
            self::RESULT_EN_PROCESO => 'Enviado',
            self::RESULT_NO_DISPONIBLE => 'sunat_disponible',
            self::RESULT_YA_PRESENTADO => 'fue_enviado',
            default => 'registrado',
        };
    }

    /**
     * Baja aceptada por SUNAT (CDR con código 0).
     */
    public static function accepted(?string $code = '0', ?string $description = null, array $notes = [], bool $hasCdr = false): array
    {
        return [
            'result' => self::RESULT_ACEPTADO,
            'kind' => 'cdr_aceptado',
            'level' => 'success',
            'title' => 'Baja aceptada por SUNAT',
            'message' => 'SUNAT aceptó la comunicación de baja: las facturas incluidas en ella quedaron anuladas. '
                .($hasCdr
                    ? 'El CDR se guardó en el sistema y puedes descargarlo desde el listado.'
                    : 'SUNAT respondió con el código 0, pero no devolvió el archivo CDR; puedes volver a consultar el ticket para recuperarlo.'),
            'action' => self::ACTION_NONE,
            'status' => self::statusFor(self::RESULT_ACEPTADO),
            'code' => $code ?? '0',
            'detail' => self::detail($code ?? '0', $description),
            'cdr_code' => $code ?? '0',
            'cdr_description' => $description,
            'has_cdr' => $hasCdr,
            'notes' => $notes,
        ];
    }

    /**
     * La comunicación se envía correctamente a SUNAT pero la baja es
     * asíncrona: se obtuvo ticket y aún no hay CDR.
     */
    public static function sentToSunat(?string $ticket = null): array
    {
        return [
            'result' => self::RESULT_EN_PROCESO,
            'kind' => 'ticket_obtenido',
            'level' => 'info',
            'title' => 'Comunicación enviada, pendiente de confirmación',
            'message' => 'SUNAT recibió la comunicación de baja'
                .($ticket ? ' con el ticket '.$ticket : '')
                .'. La anulación todavía NO está confirmada: presiona "Consultar" para revisar el CDR. '
                .'No vuelvas a generar una baja para las mismas facturas mientras SUNAT la procesa.',
            'action' => self::ACTION_RECHECK,
            'status' => self::statusFor(self::RESULT_EN_PROCESO),
            'code' => null,
            'detail' => $ticket ? 'Ticket: '.$ticket : null,
            'cdr_code' => null,
            'cdr_description' => null,
            'has_cdr' => false,
            'notes' => [],
        ];
    }

    /**
     * SUNAT sigue procesando el archivo (código 0098 / 98).
     */
    public static function processing(?string $code = '0098', ?string $raw = null): array
    {
        return [
            'result' => self::RESULT_EN_PROCESO,
            'kind' => 'en_proceso',
            'level' => 'info',
            'title' => 'SUNAT aún está procesando la baja',
            'message' => 'SUNAT todavía está procesando la comunicación de baja, así que aún no hay una respuesta definitiva. '
                .'No es un error del sistema: vuelve a presionar "Consultar" en unos minutos.',
            'action' => self::ACTION_RECHECK,
            'status' => self::statusFor(self::RESULT_EN_PROCESO),
            'code' => $code,
            'detail' => self::detail($code, $raw),
            'cdr_code' => null,
            'cdr_description' => null,
            'has_cdr' => false,
            'notes' => [],
        ];
    }

    /**
     * Traduce un error de Greenter/SUNAT (envío o consulta del ticket).
     *
     * @param  array<int, string>  $notes  Observaciones devueltas por SUNAT.
     * @param  string  $stage  'send' | 'check'
     */
    public static function failure(?string $code, ?string $message, array $notes = [], string $stage = 'send'): array
    {
        $code = $code !== null ? trim((string) $code) : null;
        $raw = trim((string) $message);
        $notes = array_values(array_filter(array_map('strval', $notes), fn ($note) => trim($note) !== ''));
        $haystack = mb_strtolower($code.' '.$raw.' '.implode(' ', $notes));
        $detail = self::detail($code, $raw);

        // 1) Fuera del plazo legal de 7 días hábiles: la baja ya no es posible.
        if (self::contains($haystack, ['plazo', 'fuera de fecha', 'fuera del plazo', 'excede', 'vencid', 'venci'])) {
            return self::alert(
                self::RESULT_PLAZO_VENCIDO,
                'plazo_vencido',
                'error',
                'Baja fuera del plazo legal',
                'SUNAT rechazó la baja porque la factura ya superó el plazo legal de 7 días hábiles para comunicarla. '
                .'La anulación por comunicación de baja ya no es posible: emite una Nota de Crédito (tipo 07) por el total del comprobante para regularizarlo contable y tributariamente.',
                self::ACTION_CREDIT_NOTE,
                $code,
                $detail,
                $notes
            );
        }

        // 2) SUNAT todavía está procesando el ticket.
        if (self::contains($haystack, ['0098', ' no ha terminado', 'en proceso', 'procesamiento', 'procesando'])) {
            return self::processing($code ?: '0098', $raw);
        }

        // 3) El archivo ya fue presentado antes (típico al reenviar).
        if (self::contains($haystack, ['2223', 'ya fue presentado', 'ya se encuentra presentado'])) {
            return self::alert(
                self::RESULT_YA_PRESENTADO,
                'ya_presentado',
                'warning',
                'La baja ya fue presentada anteriormente',
                'SUNAT indica que este archivo ya fue presentado antes (código 2223), por lo que no lo procesó de nuevo. '
                .'No es un rechazo: presiona "Consultar" con el ticket para recuperar el CDR y confirmar si la baja fue aceptada.',
                self::ACTION_RECHECK,
                $code,
                $detail,
                $notes
            );
        }

        // 4) Ticket inexistente: la comunicación no llegó a registrarse.
        if (self::contains($haystack, ['0127', 'ticket no existe', 'no existe el ticket', 'ticket no encontrado'])) {
            return self::alert(
                self::RESULT_RECHAZADO,
                'ticket_inexistente',
                'error',
                'Ticket no encontrado en SUNAT',
                'SUNAT no reconoce el ticket de esta comunicación de baja, así que la anulación no quedó registrada. '
                .'Verifica el comprobante en el portal de SUNAT y, si la factura sigue vigente, genera una nueva comunicación de baja '
                .'(o emite una Nota de Crédito si ya venció el plazo de 7 días hábiles).',
                self::ACTION_NEW_VOID,
                $code,
                $detail,
                $notes
            );
        }

        // 5) SUNAT no disponible / problemas de transporte HTTP.
        if (self::isTransportOrUnavailable($code, $haystack)) {
            return self::alert(
                self::RESULT_NO_DISPONIBLE,
                'sunat_no_disponible',
                'warning',
                'SUNAT no respondió',
                'No se pudo obtener respuesta de SUNAT: el servicio está caído, rechazó la conexión o devolvió un error HTTP. '
                .'La baja NO quedó registrada y las facturas siguen vigentes. No necesitas emitir una Nota de Crédito: '
                .'intenta enviar (o consultar) la baja nuevamente en unos minutos.',
                self::ACTION_RETRY,
                $code,
                $detail,
                $notes
            );
        }

        // 6) Rechazo de negocio con CDR/observaciones.
        $notesText = $notes ? ' Observaciones de SUNAT: '.implode(' | ', $notes) : '';
        $suffix = $stage === 'check'
            ? 'La baja fue rechazada, por lo que las facturas siguen vigentes. Corrige lo indicado y vuelve a generar la comunicación'
            : 'La comunicación no fue aceptada, por lo que las facturas siguen vigentes. Corrige lo indicado y vuelve a enviarla';

        return self::alert(
            self::RESULT_RECHAZADO,
            $stage === 'check' ? 'rechazo_cdr' : 'rechazo_envio',
            'error',
            'SUNAT rechazó la comunicación de baja',
            $suffix.'. Si el comprobante ya no admite baja (por ejemplo, por plazo vencido), emite una Nota de Crédito.'.$notesText,
            self::ACTION_FIX,
            $code,
            $detail,
            $notes
        );
    }

    /**
     * Fallo interno (excepción, datos incompletos, certificado, etc.).
     */
    public static function localError(string $message, string $stage = 'local'): array
    {
        return [
            'result' => self::RESULT_ERROR_LOCAL,
            'kind' => $stage === 'local' ? 'error_sistema' : $stage,
            'level' => 'error',
            'title' => 'No se pudo completar la comunicación de baja',
            'message' => 'El sistema no pudo completar la operación: '.$message
                .' No se confirmó ninguna anulación en SUNAT. Revisa el detalle técnico y vuelve a intentarlo; '
                .'si el problema persiste, contacta al área de sistemas.',
            'action' => self::ACTION_SUPPORT,
            'status' => self::statusFor(self::RESULT_ERROR_LOCAL),
            'code' => null,
            'detail' => $message,
            'cdr_code' => null,
            'cdr_description' => null,
            'has_cdr' => false,
            'notes' => [],
        ];
    }

    protected static function alert(
        string $result,
        string $kind,
        string $level,
        string $title,
        string $message,
        string $action,
        ?string $code,
        ?string $detail,
        array $notes
    ): array {
        return [
            'result' => $result,
            'kind' => $kind,
            'level' => $level,
            'title' => $title,
            'message' => $message,
            'action' => $action,
            'status' => self::statusFor($result),
            'code' => $code,
            'detail' => $detail,
            'cdr_code' => null,
            'cdr_description' => null,
            'has_cdr' => false,
            'notes' => $notes,
        ];
    }

    protected static function detail(?string $code, ?string $message): ?string
    {
        $code = $code !== null ? trim($code) : '';
        $message = trim((string) $message);

        if ($code === '' && $message === '') {
            return null;
        }

        return trim(($code !== '' ? 'Código '.$code.'. ' : '').$message);
    }

    /**
     * @param  array<int, string>  $needles
     */
    protected static function contains(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected static function isTransportOrUnavailable(?string $code, string $haystack): bool
    {
        if ($code === '' || $code === null) {
            return true;
        }

        return self::contains($haystack, [
            '0109',
            'http',
            'bad request',
            'connection',
            'could not connect',
            'no se pudo conectar',
            'timeout',
            'timed out',
            'soap',
            'curl',
            'ssl',
            'servicio no disponible',
            'servicio no se encuentra disponible',
            'no disponible',
            'mantenimiento',
        ]);
    }
}
