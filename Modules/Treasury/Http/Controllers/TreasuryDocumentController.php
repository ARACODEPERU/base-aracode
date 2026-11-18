<?php

namespace Modules\Treasury\Http\Controllers;

use App\Http\Controllers\ApisnetPeController;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryDocument;
use Modules\Treasury\Entities\TreasurySupplier;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryDocumentService;

/**
 * Cartera de cheques y letras por cobrar y por pagar.
 *
 * Los documentos pendientes alimentan la previsión de caja. El ciclo de vida se
 * controla por estados y cada cambio queda auditado; anular no borra.
 */
class TreasuryDocumentController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(private TreasuryDocumentService $documents)
    {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('treasury_documentos');

        $direction = $request->get('direction');
        $status = $request->get('status');

        $documents = TreasuryDocument::query()
            ->with(['supplier', 'account', 'creator'])
            ->when(in_array($direction, array_keys(TreasuryDocument::DIRECTION_LABELS), true),
                fn ($query) => $query->where('direction', $direction))
            ->when(in_array($status, array_keys(TreasuryDocument::STATUS_LABELS), true),
                fn ($query) => $query->where('status', $status))
            ->orderBy('due_date')
            ->orderBy('id')
            ->limit(300)
            ->get()
            ->map(fn (TreasuryDocument $document) => [
                'id' => $document->id,
                'type' => $document->type,
                'type_label' => $document->typeLabel(),
                'direction' => $document->direction,
                'direction_label' => $document->directionLabel(),
                'number' => $document->number,
                'bank_name' => $document->bank_name,
                'issuer' => $document->issuer ?: $document->supplier?->name,
                'issuer_doc_type' => $document->issuer_doc_type,
                'issuer_doc_number' => $document->issuer_doc_number,
                'supplier' => $document->supplier?->name,
                'amount' => (float) $document->amount,
                'currency' => $document->currency,
                'issue_date' => $document->issue_date?->format('Y-m-d'),
                'due_date' => $document->due_date?->format('Y-m-d'),
                'status' => $document->status,
                'status_label' => $document->statusLabel(),
                'overdue' => $document->isOverdue(),
                'days' => $document->due_date ? (int) today()->diffInDays($document->due_date, false) : null,
                'account' => $document->account?->displayName(),
                'notes' => $document->notes,
                'created_by' => $document->creator?->name,
            ]);

        return Inertia::render('Treasury::Documents/Index', [
            'documents' => $documents,
            'summary' => $this->documents->summary(),
            'suppliers' => TreasurySupplier::query()->active()->orderBy('name')->get(['id', 'name', 'doc_type', 'doc_number']),
            'accounts' => TreasuryAccount::query()->active()->orderBy('type')->get()
                ->map(fn (TreasuryAccount $account) => [
                    'id' => $account->id,
                    'label' => $account->displayName(),
                    'currency' => strtoupper((string) ($account->currency_type_id ?: 'PEN')) ?: 'PEN',
                ]),
            'filters' => [
                'direction' => $direction,
                'status' => $status,
            ],
            'options' => [
                'types' => collect(TreasuryDocument::TYPE_LABELS)->map(fn ($label, $code) => ['code' => $code, 'label' => $label])->values(),
                'directions' => collect(TreasuryDocument::DIRECTION_LABELS)->map(fn ($label, $code) => ['code' => $code, 'label' => $label])->values(),
                'statuses' => collect(TreasuryDocument::STATUS_LABELS)->map(fn ($label, $code) => ['code' => $code, 'label' => $label])->values(),
            ],
            'canManage' => auth()->user()?->hasPermissionTo('treasury_documentos_gestionar'),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('treasury_documentos_gestionar');

        $data = $request->validate([
            'type' => ['required', 'in:' . implode(',', array_keys(TreasuryDocument::TYPE_LABELS))],
            'direction' => ['required', 'in:' . implode(',', array_keys(TreasuryDocument::DIRECTION_LABELS))],
            'number' => ['required', 'string', 'max:40'],
            'bank_name' => ['nullable', 'string', 'max:80'],
            'issuer' => ['nullable', 'string', 'max:160'],
            'issuer_doc_type' => ['nullable', 'in:1,6'],
            'issuer_doc_number' => ['nullable', 'string', 'max:15'],
            'supplier_id' => ['nullable', 'integer', 'exists:treasury_suppliers,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'currency' => ['nullable', 'string', 'size:3'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'treasury_account_id' => ['nullable', 'integer', 'exists:treasury_accounts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $document = $this->documents->register($data, auth()->id());
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $document->typeLabel() . ' ' . $document->number . ' registrado en cartera.');
    }

    /**
     * Consulta el DNI o RUC del emisor en la API RENIEC/SUNAT del sistema.
     *
     * Devuelve el nombre o razón social para autocompletar el emisor; no
     * guarda nada: el documento se registra con el formulario.
     */
    public function consultIssuer(Request $request)
    {
        $this->authorizePermission('treasury_documentos_gestionar');

        $data = $request->validate([
            'issuer_doc_type' => ['required', 'in:1,6'],
            'issuer_doc_number' => ['required', 'string', 'max:15'],
        ]);

        $type = (int) $data['issuer_doc_type'];
        $number = preg_replace('/\D/', '', (string) $data['issuer_doc_number']);
        $expected = $type === 6 ? 11 : 8;

        if (strlen($number) !== $expected) {
            return response()->json([
                'success' => false,
                'error' => $type === 6
                    ? 'El RUC debe tener 11 dígitos.'
                    : 'El DNI debe tener 8 dígitos.',
            ], 422);
        }

        // Decolecta es el proveedor vigente del sistema; migo.pe queda como respaldo.
        $api = app(ApisnetPeController::class);
        $result = $type === 6
            ? $api->consultaRUC($number)
            : $api->consultaDNI($number);

        if (empty($result['success'])) {
            $fallback = $type === 6
                ? $api->consultaRUCmigo($number)
                : $api->consultaDNImigo($number);

            // Si el respaldo tampoco responde, se explica el error del proveedor vigente.
            if (! empty($fallback['success'])) {
                $result = $fallback;
            }
        }

        if (empty($result['success'])) {
            $error = trim((string) ($result['error'] ?? ''));

            // El proveedor responde «not found»: se explica en español.
            if ($error === '' || strcasecmp($error, 'not found') === 0) {
                $error = 'No se encontraron datos para el documento consultado.';
            }

            return response()->json([
                'success' => false,
                'error' => $error,
            ]);
        }

        $person = $result['person'] ?? [];

        return response()->json([
            'success' => true,
            'source' => $type === 6 ? 'sunat' : 'reniec',
            'issuer' => [
                'name' => $person['razon_social'] ?? null,
                'document_type' => (string) $type,
                'document_number' => $person['numero_documento'] ?? $person['document_number'] ?? $number,
                'address' => $person['direccion'] ?? null,
            ],
        ]);
    }

    public function changeStatus(Request $request, int $id)
    {
        $this->authorizePermission('treasury_documentos_gestionar');

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(TreasuryDocument::STATUS_LABELS))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $document = $this->documents->changeStatus(
                TreasuryDocument::findOrFail($id),
                $data['status'],
                auth()->id(),
                $data['note'] ?? null
            );
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Documento ' . $document->number . ': ' . $document->statusLabel() . '.');
    }
}
