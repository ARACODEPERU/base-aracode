<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Socialevents\Services\OrganizationDocumentService;
use Modules\Socialevents\Services\OrganizationTeamService;
use Modules\Socialevents\Services\SportsOrganizationService;

class OrganizationDocumentController extends Controller
{
    public function request(Request $request, OrganizationDocumentService $documents, SportsOrganizationService $sports)
    {
        $organizationId = $sports->organizationId($request->user());
        abort_unless($organizationId, 403, 'No hay una organización asociada a esta cuenta.');

        $validated = $request->validate([
            'purchase_type' => 'required|in:seat_purchase,topup',
            'purchase_id' => 'required|integer',
            'document_type_id' => 'required|integer|exists:sale_document_types,id',
            'billing_data' => 'required|array',
            'billing_data.business_name' => 'nullable|string|max:255',
            'billing_data.ruc' => 'nullable|string|max:11',
            'billing_data.address' => 'nullable|string|max:255',
            'billing_data.ubigeo' => 'nullable|string|size:6|exists:districts,id',
            'billing_data.document_type' => 'nullable|string|max:5',
            'billing_data.document_number' => 'nullable|string|max:20',
            'billing_data.email' => 'nullable|email|max:255',
            'billing_data.phone' => 'nullable|string|max:20',
            'channels' => 'nullable|array',
            'channels.*' => 'in:email,whatsapp',
        ]);

        $documents->request(
            $request->user(),
            $organizationId,
            $validated['purchase_type'],
            (int) $validated['purchase_id'],
            (int) $validated['document_type_id'],
            $validated['billing_data'],
            $validated['channels'] ?? []
        );

        return back()->with('success', 'Solicitud de comprobante enviada. El administrador la revisará.');
    }

    public function download(Request $request, OrganizationDocumentService $documents, int $documentRequest, string $file = 'PDF')
    {
        return $documents->download($request->user(), $documentRequest, $file);
    }

    public function issue(Request $request, OrganizationDocumentService $documents, int $documentRequest)
    {
        $validated = $request->validate([
            'channels' => 'nullable|array',
            'channels.*' => 'in:email,whatsapp',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'billing_data' => 'nullable|array',
        ]);

        $documents->issue($request->user(), $documentRequest, $validated);

        return back()->with('success', 'Comprobante emitido y disponible para el organizador.');
    }

    public function reject(Request $request, OrganizationDocumentService $documents, int $documentRequest)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $documents->reject($request->user(), $documentRequest, $validated['reason']);

        return back()->with('success', 'Solicitud rechazada con el motivo indicado.');
    }
}
