<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Sales\Entities\SireSubmission;
use Modules\Sales\Entities\SireSubmissionLog;
use Modules\Sales\Services\Sire\SireApiClient;
use Modules\Sales\Services\Sire\SireApiException;

/**
 * Configuración del SIRE por empresa: modo de trabajo (TXT/ZIP o API) y las
 * credenciales que SUNAT entrega en SOL. Los cuatro valores se guardan
 * cifrados y la pantalla solo muestra pistas enmascaradas.
 */
class SireConfiguracionController extends Controller
{
    private const CAMPOS = [
        'client_id' => 'sire_client_id',
        'client_secret' => 'sire_client_secret',
        'sol_user' => 'sire_sol_user',
        'sol_key' => 'sire_sol_key',
    ];

    public function index(SireApiClient $client)
    {
        $company = Company::query()->first();

        return Inertia::render('Sales::Sire/Configuracion', [
            'company' => $company ? [
                'id' => $company->id,
                'ruc' => (string) $company->ruc,
                'name' => (string) ($company->business_name ?: $company->name),
            ] : null,
            'mode' => $company?->sire_mode === 'api' ? 'api' : 'txt',
            'credentials' => [
                'client_id' => $this->pista($company?->sire_client_id, 6),
                'sol_user' => $this->pista($company?->sire_sol_user, 2),
                'has_client_secret' => filled($company?->sire_client_secret),
                'has_sol_key' => filled($company?->sire_sol_key),
            ],
            'credentialsReady' => $company ? $client->credencialesCompletas($company) : false,
            'logs' => $this->logs($company),
            'submissions' => $this->submissions($company),
        ]);
    }

    public function update(Request $request, SireApiClient $client)
    {
        $datos = $request->validate([
            'mode' => 'required|in:txt,api',
            'client_id' => 'nullable|string|max:140',
            'client_secret' => 'nullable|string|max:140',
            'sol_user' => 'nullable|string|max:140',
            'sol_key' => 'nullable|string|max:140',
        ]);

        $company = Company::query()->first();

        if (! $company) {
            return back()->with('error', 'Configura primero los datos de la empresa antes del SIRE.');
        }

        $company->sire_mode = $datos['mode'];

        foreach (self::CAMPOS as $input => $columna) {
            if (filled($datos[$input] ?? null)) {
                $company->{$columna} = trim((string) $datos[$input]);
            }
        }

        // El modo API necesita las cuatro piezas; se puede completar lo que
        // falte más adelante y guardar el modo manual mientras tanto.
        if ($datos['mode'] === 'api' && ! $client->credencialesCompletas($company)) {
            throw ValidationException::withMessages([
                'mode' => 'Para activar el modo API completa el client_id, el client_secret, el usuario SOL y la clave SOL.',
            ]);
        }

        $company->save();

        // Un cambio de credencial invalida el token que estaba cacheado.
        $client->olvidarToken($company);

        return back()->with('message', 'Configuración del SIRE guardada.');
    }

    public function probar(SireApiClient $client)
    {
        $company = Company::query()->first();

        if (! $company) {
            return response()->json([
                'success' => false,
                'message' => 'Configura primero los datos de la empresa.',
            ], 422);
        }

        if (! $client->credencialesCompletas($company)) {
            return response()->json([
                'success' => false,
                'message' => 'Faltan credenciales: completa el client_id, el client_secret, el usuario SOL y la clave SOL.',
            ], 422);
        }

        try {
            // Se pide un token nuevo para comprobar la credencial vigente, no
            // el token que ya estaba en caché.
            $client->olvidarToken($company);
            $client->token($company);

            return response()->json([
                'success' => true,
                'message' => 'SUNAT aceptó las credenciales y entregó el token de acceso. El SIRE no tiene ambiente de pruebas: esta llamada solo se autenticó, no consultó ni modificó ningún registro.',
            ]);
        } catch (SireApiException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo comprobar la conexión con SUNAT. Revisa la bitácora.',
            ], 500);
        }
    }

    /**
     * Deja visible solo el inicio del valor y su final; el resto se enmascara.
     */
    private function pista(?string $valor, int $visibles): ?string
    {
        if (blank($valor)) {
            return null;
        }

        $valor = (string) $valor;

        if (strlen($valor) <= $visibles) {
            return str_repeat('•', 6);
        }

        return substr($valor, 0, $visibles) . str_repeat('•', 6) . substr($valor, -2);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function logs(?Company $company): array
    {
        if (! $company) {
            return [];
        }

        return SireSubmissionLog::query()
            ->where('company_id', $company->id)
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn (SireSubmissionLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'method' => $log->method,
                'endpoint' => Str::limit((string) $log->endpoint, 90),
                'http_status' => $log->http_status,
                'success' => (bool) $log->success,
                'message' => Str::limit((string) $log->message, 240),
                'duration_ms' => $log->duration_ms,
                'at' => optional($log->created_at)->format('d/m/Y H:i:s'),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function submissions(?Company $company): array
    {
        if (! $company) {
            return [];
        }

        return SireSubmission::query()
            ->where('company_id', $company->id)
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (SireSubmission $submission) => [
                'id' => $submission->id,
                'tipo' => $submission->tipo,
                'period' => $submission->period,
                'action' => $submission->action,
                'ticket' => $submission->ticket,
                'status' => $submission->status,
                'file_name' => $submission->file_name,
                'lines' => $submission->lines,
                'at' => optional($submission->created_at)->format('d/m/Y H:i'),
            ])
            ->all();
    }
}
