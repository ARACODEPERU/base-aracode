<?php

namespace Modules\Commercial\Http\Controllers;

use App\Http\Controllers\ApisnetPeController;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\IdentityDocumentType;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CommercialClientController extends Controller
{
    public function index()
    {
        $clients = Person::query()
            ->where('is_client', true)
            ->when(request()->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('telephone', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(request()->input('per_page', 20))
            ->withQueryString();

        return Inertia::render('Commercial::Clients/List', [
            'clients' => $clients,
            'filters' => request()->only('search', 'per_page'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Commercial::Clients/Create', $this->formData());
    }

    public function store(Request $request)
    {
        $personId = $request->input('id');
        $data = $this->validatedData($request, $personId);

        if ($personId) {
            Person::findOrFail($personId)->update($this->personPayload($data));
        } else {
            Person::updateOrCreate(
                ['number' => $data['number']],
                $this->personPayload($data)
            );
        }

        return redirect()->back()->with('success', 'Cliente registrado correctamente');
    }

    public function edit($id)
    {
        return Inertia::render('Commercial::Clients/Edit', array_merge($this->formData(), [
            'client' => Person::findOrFail($id),
        ]));
    }

    public function update(Request $request, $id)
    {
        $client = Person::findOrFail($id);
        $data = $this->validatedData($request, $client->id);

        $client->update($this->personPayload($data));

        return redirect()->back()->with('success', 'Cliente actualizado correctamente');
    }

    public function destroy($id)
    {
        $client = Person::findOrFail($id);

        if ($this->hasAssociations($client->id)) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el cliente porque tiene registros asociados.',
            ], 422);
        }

        $client->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado correctamente',
        ]);
    }

    public function searchInternal(Request $request)
    {
        $request->validate([
            'document_type_id' => 'nullable|string',
            'number' => 'nullable|string',
            'full_name' => 'nullable|string',
        ]);

        $person = Person::query()
            ->when($request->input('number'), function ($query, $number) use ($request) {
                $query->where('number', $number)
                    ->when($request->input('document_type_id'), function ($q, $documentTypeId) {
                        $q->where('document_type_id', $documentTypeId);
                    });
            })
            ->when(! $request->input('number') && $request->input('full_name'), function ($query, $fullName) {
                $query->where('full_name', 'like', "%{$fullName}%");
            })
            ->first();

        return response()->json([
            'status' => (bool) $person,
            'person' => $person,
            'message' => $person ? 'Cliente encontrado en la base de datos.' : 'No se encontro el cliente en la base de datos.',
        ]);
    }

    public function searchExternal(Request $request)
    {
        $request->validate([
            'document_type_id' => 'required|string',
            'number' => 'required|string',
        ]);

        $person = Person::where('number', $request->input('number'))
            ->where('document_type_id', $request->input('document_type_id'))
            ->first();

        if ($person) {
            return response()->json([
                'success' => true,
                'source' => 'database',
                'person' => $person,
                'message' => 'El cliente ya existe en la base de datos.',
            ]);
        }

        $service = app(ApisnetPeController::class);
        $data = $request->input('document_type_id') == 6
            ? $service->consultaRUCmigo($request->input('number'))
            : $service->consultaDNI($request->input('number'));

        return response()->json(array_merge($data ?? ['success' => false], [
            'source' => $request->input('document_type_id') == 6 ? 'sunat' : 'reniec',
        ]));
    }

    private function formData(): array
    {
        $ubigeo = DB::table('districts')
            ->join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name, '-', provinces.name, '-', districts.name) AS city_name")
            )
            ->orderBy('departments.name')
            ->orderBy('provinces.name')
            ->orderBy('districts.name')
            ->get();

        return [
            'identityDocumentTypes' => IdentityDocumentType::orderBy('id')->get(),
            'countries' => Country::orderBy('description')->get(),
            'ubigeo' => $ubigeo,
        ];
    }

    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        $isRuc = (string) $request->input('document_type_id') === '6';

        return $request->validate([
            'id' => ['nullable', 'integer', 'exists:people,id'],
            'document_type_id' => ['required', 'string', 'exists:identity_document_type,id'],
            'number' => ['required', 'string', 'max:20', Rule::unique('people', 'number')->ignore($ignoreId)],
            'full_name' => [$isRuc ? 'required' : 'nullable', 'nullable', 'string', 'max:255'],
            'names' => [$isRuc ? 'nullable' : 'required', 'nullable', 'string', 'max:255'],
            'father_lastname' => [$isRuc ? 'nullable' : 'required', 'nullable', 'string', 'max:255'],
            'mother_lastname' => [$isRuc ? 'nullable' : 'required', 'nullable', 'string', 'max:255'],
            'gender' => [$isRuc ? 'nullable' : 'required', 'nullable', 'in:M,F'],
            'birthdate' => [$isRuc ? 'nullable' : 'required', 'nullable', 'date'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'ubigeo' => ['nullable', 'string', 'max:20'],
            'ubigeo_description' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('people', 'email')->ignore($ignoreId)],
            'telephone' => ['nullable', 'string', 'max:20'],
        ]);
    }

    private function personPayload(array $data): array
    {
        $fullName = (string) ($data['document_type_id'] ?? '') === '6'
            ? trim($data['full_name'])
            : trim(($data['father_lastname'] ?? '') . ' ' . ($data['mother_lastname'] ?? '') . ' ' . ($data['names'] ?? ''));

        return [
            'document_type_id' => $data['document_type_id'],
            'short_name' => $data['names'] ?? $fullName,
            'full_name' => $fullName,
            'number' => trim($data['number']),
            'telephone' => $data['telephone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'is_client' => true,
            'is_provider' => false,
            'ubigeo' => $data['ubigeo'] ?? null,
            'ubigeo_description' => $data['ubigeo_description'] ?? null,
            'birthdate' => $data['birthdate'] ?? null,
            'names' => $data['names'] ?? null,
            'father_lastname' => $data['father_lastname'] ?? null,
            'mother_lastname' => $data['mother_lastname'] ?? null,
            'gender' => $data['gender'] ?? null,
            'country_id' => $data['country_id'] ?? null,
            'status' => true,
        ];
    }

    private function hasAssociations(int $personId): bool
    {
        $checks = [
            ['users', 'person_id'],
            ['sales', 'client_id'],
            ['sale_physical_documents', 'client_id'],
            ['onli_sales', 'person_id'],
            ['aca_students', 'person_id'],
            ['aca_teachers', 'person_id'],
            ['heal_patients', 'person_id'],
            ['heal_doctors', 'person_id'],
            ['bib_authors', 'person_id'],
            ['bib_books', 'author_id'],
            ['event_edition_team_players', 'person_id'],
            ['even_event_exhibitors', 'exhibitor_id'],
            ['people', 'company_person_id'],
            ['sedes', 'person_id'],
        ];

        foreach ($checks as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                if (DB::table($table)->where($column, $personId)->exists()) {
                    return true;
                }
            }
        }

        return false;
    }
}
