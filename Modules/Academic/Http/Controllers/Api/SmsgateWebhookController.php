<?php

namespace Modules\Academic\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Academic\Services\SmsgateService;

/**
 * Webhook publico de SMSGate (modelo pull).
 *
 * La app SMSGate lo llama con Authorization: Bearer <SC-00003>:
 *   - GET  -> recibe los mensajes pendientes y los reclama.
 *   - POST -> reporta el estado de un mensaje; si el cuerpo no trae un estado
 *             reconocible se trata como una consulta de pendientes (algunas
 *             apps solo saben hacer POST).
 *
 * El FQCN del middleware smsgate.bearer se aplica en las rutas del modulo.
 */
class SmsgateWebhookController extends Controller
{
    public function __construct(private readonly SmsgateService $smsgate)
    {
    }

    /**
     * Mensajes pendientes que la app debe enviar.
     */
    public function pending(Request $request): JsonResponse
    {
        $requested = (int) $request->integer('limit', 0);

        $messages = $this->smsgate->pendingMessages($requested > 0 ? $requested : null);

        return response()->json([
            'count' => count($messages),
            'messages' => $messages,
        ]);
    }

    /**
     * Reporte de estado, o consulta de pendientes si no hay estado.
     */
    public function handle(Request $request): JsonResponse
    {
        $report = $this->smsgate->extractStatusReport($request->all());

        if ($report === null) {
            return $this->pending($request);
        }

        $updated = $this->smsgate->reportStatus($report['id'], $report['status'], $report['reason']);

        return response()->json([
            'ok' => $updated,
            'id' => $report['id'],
            'status' => $report['status'],
        ], $updated ? 200 : 404);
    }
}
