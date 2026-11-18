<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryStatementImportService;

/**
 * Importación de extractos para la conciliación.
 *
 * Responde JSON porque la pantalla muestra el mapeo, deja corregirlo y recién
 * entonces confirma; no recarga la página en medio del proceso.
 */
class TreasuryStatementImportController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(private TreasuryStatementImportService $imports)
    {
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorizePermission('treasury_conciliacion_importar');

        $data = $request->validate([
            // La extensión se valida en el servicio: los extractos OFX no tienen
            // un MIME estándar y la regla `mimes` los rechazaría.
            'file' => ['required', 'file', 'max:10240'],
            'treasury_account_id' => ['required', 'integer', 'exists:treasury_accounts,id'],
            // Mapeo corregido por el usuario para volver a leer la vista previa.
            'mapping' => ['nullable', 'string', 'max:2000'],
        ]);

        $account = TreasuryAccount::query()->active()->findOrFail($data['treasury_account_id']);
        $mapping = null;

        if (filled($data['mapping'] ?? null)) {
            $decoded = json_decode((string) $data['mapping'], true);
            $mapping = is_array($decoded) ? $decoded : null;
        }

        try {
            return response()->json($this->imports->preview($request->file('file'), $account, $mapping));
        } catch (TreasuryOperationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'No se pudo leer el extracto. Verifica que el archivo no esté dañado.'], 422);
        }
    }

    public function confirm(Request $request): JsonResponse
    {
        $this->authorizePermission('treasury_conciliacion_importar');

        $data = $request->validate([
            'token' => ['required', 'string', 'max:80'],
            'file_name' => ['nullable', 'string', 'max:180'],
            'treasury_account_id' => ['required', 'integer', 'exists:treasury_accounts,id'],
            'until' => ['required', 'date'],
            'mapping' => ['required', 'array'],
        ]);

        $account = TreasuryAccount::query()->active()->findOrFail($data['treasury_account_id']);

        try {
            $result = $this->imports->confirm($data, $account, auth()->id());
        } catch (TreasuryOperationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'No se pudo aplicar la importación. Revisa el mapeo e inténtalo otra vez.'], 422);
        }

        return response()->json([
            'message' => 'Extracto importado: ' . $result['rows_total'] . ' línea(s), '
                . $result['rows_matched'] . ' emparejada(s) y '
                . $result['rows_created'] . ' partida(s) nueva(s).',
            'result' => $result,
        ]);
    }
}
