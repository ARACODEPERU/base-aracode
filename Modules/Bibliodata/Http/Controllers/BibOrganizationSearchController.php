<?php

namespace Modules\Bibliodata\Http\Controllers;

use App\Http\Controllers\ApisnetPeController;
use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Http\Request;

class BibOrganizationSearchController extends Controller
{
    private const RUC_DOCUMENT_TYPE_ID = 6;

    /**
     * Clientes (people) con RUC: is_client y document_type_id = 6.
     */
    private function clientRucQuery()
    {
        return Person::query()
            ->where('is_client', true)
            ->where('document_type_id', self::RUC_DOCUMENT_TYPE_ID)
            ->whereNotNull('number')
            ->where('number', '!=', '');
    }

    public function searchList(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        if ($search === '') {
            return response()->json([
                'status' => false,
                'persons' => [],
                'alert' => 'Ingrese un RUC o razón social para buscar.',
            ]);
        }

        $persons = $this->clientRucQuery()
            ->where(function ($query) use ($search) {
                $query->where('number', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%");
            })
            ->orderBy('full_name')
            ->limit(25)
            ->get(['id', 'number', 'full_name', 'email', 'telephone', 'address', 'ubigeo', 'ubigeo_description']);

        return response()->json([
            'status' => $persons->isNotEmpty(),
            'persons' => $persons,
            'alert' => $persons->isNotEmpty() ? null : 'No se encontraron empresas con ese criterio.',
        ]);
    }

    public function searchInternal(Request $request)
    {
        $request->validate([
            'number' => 'required|string|min:11|max:11',
        ]);

        $number = preg_replace('/\D/', '', $request->input('number'));
        $person = $this->clientRucQuery()->where('number', $number)->first();

        return response()->json([
            'status' => (bool) $person,
            'person' => $person,
            'alert' => $person ? null : 'No existe en la base de datos. Puede consultar SUNAT o registrar uno nuevo.',
        ]);
    }

    public function searchSunat(Request $request)
    {
        $request->validate([
            'number' => 'required|string|min:11|max:11',
        ]);

        $number = preg_replace('/\D/', '', $request->input('number'));

        $person = $this->clientRucQuery()->where('number', $number)->first();

        if ($person) {
            return response()->json([
                'success' => true,
                'source' => 'database',
                'person' => $person,
                'message' => 'La empresa ya está registrada como cliente.',
            ]);
        }

        $service = app(ApisnetPeController::class);
        $data = $service->consultaRUCmigo($number);

        return response()->json(array_merge($data ?? ['success' => false], [
            'source' => 'sunat',
        ]));
    }

    public function savePerson(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|integer|exists:people,id',
            'number' => 'required|string|size:11',
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:150',
            'telephone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'ubigeo' => 'nullable|string|max:20',
            'ubigeo_description' => 'nullable|string|max:255',
        ]);

        $number = preg_replace('/\D/', '', $data['number']);

        $person = Person::updateOrCreate(
            [
                'document_type_id' => self::RUC_DOCUMENT_TYPE_ID,
                'number' => $number,
            ],
            [
                'full_name' => trim($data['full_name']),
                'email' => $data['email'] ?? null,
                'telephone' => $data['telephone'] ?? null,
                'address' => $data['address'] ?? null,
                'ubigeo' => $data['ubigeo'] ?? null,
                'ubigeo_description' => $data['ubigeo_description'] ?? null,
                'is_client' => true,
                'is_provider' => false,
            ]
        );

        return response()->json([
            'success' => true,
            'person' => $person->only([
                'id', 'number', 'full_name', 'email', 'telephone', 'address', 'ubigeo', 'ubigeo_description',
            ]),
        ]);
    }
}
