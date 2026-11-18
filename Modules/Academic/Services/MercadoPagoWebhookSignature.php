<?php

namespace Modules\Academic\Services;

use Illuminate\Http\Request;

/**
 * Validación de la firma x-signature de los webhooks de MercadoPago.
 *
 * Formato del encabezado: `ts=<timestamp>,v1=<hmac>`. El manifiesto firmado es
 * `id:<data.id>;request-id:<x-request-id>;ts:<ts>;` y el HMAC es SHA-256 con el
 * secreto configurado en el panel de MercadoPago.
 */
class MercadoPagoWebhookSignature
{
    /**
     * @return array{valid: bool, reason: string|null, data_id: string}
     */
    public function validate(Request $request, ?string $secret): array
    {
        $dataId = (string) ($request->query('data.id')
            ?? data_get($request->all(), 'data.id')
            ?? $request->query('data_id')
            ?? '');

        if ($secret === null || trim($secret) === '') {
            return ['valid' => false, 'reason' => 'sin_secreto', 'data_id' => $dataId];
        }

        $signature = (string) $request->header('x-signature');
        $requestId = (string) $request->header('x-request-id');

        $parts = [];
        foreach (explode(',', $signature) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[(string) $key] = (string) $value;
            }
        }

        if (empty($parts['ts']) || empty($parts['v1']) || $requestId === '' || $dataId === '') {
            return ['valid' => false, 'reason' => 'notificacion_incompleta', 'data_id' => $dataId];
        }

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$parts['ts']};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        if (! hash_equals($expected, $parts['v1'])) {
            return ['valid' => false, 'reason' => 'firma_invalida', 'data_id' => $dataId];
        }

        return ['valid' => true, 'reason' => null, 'data_id' => $dataId];
    }
}
