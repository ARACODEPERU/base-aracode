<?php

namespace Modules\Commercial\Http\Controllers;

use App\Http\Controllers\ApisnetPeController;
use App\Http\Controllers\Controller;
use App\Models\IdentityDocumentType;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Modules\Commercial\Entities\CommercialContract;

class CommercialContractController extends Controller
{
    public function index()
    {
        $contracts = CommercialContract::with(['client', 'responsible', 'service'])
            ->withCount('payments')
            ->when(request()->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($client) use ($search) {
                            $client->where('full_name', 'like', "%{$search}%")
                                ->orWhere('number', 'like', "%{$search}%");
                        })
                        ->orWhereHas('service', function ($service) use ($search) {
                            $service->where('description', 'like', "%{$search}%")
                                ->orWhere('interne', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(request()->input('per_page', 20))
            ->withQueryString();

        return Inertia::render('Commercial::Contracts/List', [
            'contracts' => $contracts,
            'filters' => request()->only('search', 'per_page'),
            'contractTypes' => $this->contractTypes(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Commercial::Contracts/Create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $responsibleId = $this->resolveResponsible($request);

        CommercialContract::create(array_merge($this->contractPayload($data), [
            'responsible_person_id' => $responsibleId,
            'signed_pdf_path' => $this->storePdf($request),
        ]));

        return redirect()->back()->with('success', 'Contrato registrado correctamente');
    }

    public function edit($id)
    {
        return Inertia::render('Commercial::Contracts/Edit', array_merge($this->formData(), [
            'contract' => CommercialContract::with(['client', 'responsible', 'service'])->findOrFail($id),
        ]));
    }

    public function update(Request $request, $id)
    {
        $contract = CommercialContract::findOrFail($id);
        $data = $this->validatedData($request, $contract->id);
        $responsibleId = $this->resolveResponsible($request);

        $payload = array_merge($this->contractPayload($data), [
            'responsible_person_id' => $responsibleId,
        ]);

        if ($request->hasFile('signed_pdf')) {
            if ($contract->signed_pdf_path) {
                Storage::disk('public')->delete($contract->signed_pdf_path);
            }
            $payload['signed_pdf_path'] = $this->storePdf($request);
        }

        if ($request->boolean('remove_signed_pdf') && $contract->signed_pdf_path) {
            Storage::disk('public')->delete($contract->signed_pdf_path);
            $payload['signed_pdf_path'] = null;
        }

        $contract->update($payload);

        return redirect()->back()->with('success', 'Contrato actualizado correctamente');
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $contract = CommercialContract::findOrFail($id);

            if ($contract->signed_pdf_path) {
                Storage::disk('public')->delete($contract->signed_pdf_path);
            }

            $contract->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contrato eliminado correctamente',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function searchResponsible(Request $request)
    {
        $request->validate([
            'responsible_document_type_id' => ['required', 'string', 'exists:identity_document_type,id'],
            'responsible_number' => ['required', 'string', 'max:20'],
        ]);

        $person = Person::where('number', $request->input('responsible_number'))
            ->where('document_type_id', $request->input('responsible_document_type_id'))
            ->first();

        if ($person) {
            return response()->json([
                'success' => true,
                'source' => 'database',
                'person' => $person,
                'message' => 'Responsable encontrado en la base de datos.',
            ]);
        }

        if ((string) $request->input('responsible_document_type_id') !== '1') {
            return response()->json([
                'success' => false,
                'message' => 'La busqueda externa del responsable solo esta disponible para DNI.',
            ]);
        }

        $data = app(ApisnetPeController::class)->consultaDNI($request->input('responsible_number'));

        return response()->json(array_merge($data ?? ['success' => false], [
            'source' => 'reniec',
        ]));
    }

    private function formData(): array
    {
        return [
            'clients' => Person::where('is_client', true)
                ->orderBy('full_name')
                ->get(['id', 'document_type_id', 'number', 'full_name', 'telephone', 'email']),
            'identityDocumentTypes' => IdentityDocumentType::orderBy('id')->get(),
            'currencyTypes' => DB::table('sunat_currency_types')
                ->where('active', true)
                ->orderBy('id')
                ->get(['id', 'symbol', 'description']),
            'contractTypes' => $this->contractTypes(),
        ];
    }

    private function contractTypes(): array
    {
        return [
            ['value' => 'new_development', 'label' => 'Nuevo desarrollo'],
            ['value' => 'maintenance', 'label' => 'Mantenimiento'],
            ['value' => 'rental', 'label' => 'Alquiler de servicio'],
        ];
    }

    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        $client = Person::find($request->input('client_id'));
        $requiresResponsible = $client && (string) $client->document_type_id === '6';

        return $request->validate([
            'client_id' => ['required', 'integer', 'exists:people,id'],
            'service_id' => ['nullable', 'integer', 'exists:products,id'],
            'contract_type' => ['required', Rule::in(['new_development', 'maintenance', 'rental'])],
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:5'],
            'body' => ['nullable', 'string'],
            'signed_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'remove_signed_pdf' => ['nullable', 'boolean'],
            'responsible_document_type_id' => [$requiresResponsible ? 'required' : 'nullable', 'string', 'exists:identity_document_type,id'],
            'responsible_number' => [$requiresResponsible ? 'required' : 'nullable', 'string', 'max:20'],
            'responsible_names' => [$requiresResponsible ? 'required' : 'nullable', 'string', 'max:255'],
            'responsible_father_lastname' => [$requiresResponsible ? 'required' : 'nullable', 'string', 'max:255'],
            'responsible_mother_lastname' => [$requiresResponsible ? 'required' : 'nullable', 'string', 'max:255'],
            'responsible_gender' => [$requiresResponsible ? 'required' : 'nullable', 'nullable', 'in:M,F'],
            'responsible_email' => ['nullable', 'email', 'max:255'],
            'responsible_telephone' => ['nullable', 'string', 'max:20'],
        ]);
    }

    private function contractPayload(array $data): array
    {
        return [
            'client_id' => $data['client_id'],
            'service_id' => $data['service_id'] ?? null,
            'contract_type' => $data['contract_type'],
            'title' => $data['title'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? 'PEN',
            'body' => $data['body'] ?? null,
            'status' => true,
        ];
    }

    private function resolveResponsible(Request $request): ?int
    {
        $client = Person::find($request->input('client_id'));

        if (! $client || (string) $client->document_type_id !== '6') {
            return null;
        }

        $fullName = trim(
            $request->input('responsible_father_lastname') . ' ' .
            $request->input('responsible_mother_lastname') . ' ' .
            $request->input('responsible_names')
        );

        $responsible = Person::updateOrCreate(
            [
                'document_type_id' => $request->input('responsible_document_type_id'),
                'number' => $request->input('responsible_number'),
            ],
            [
                'short_name' => $request->input('responsible_names'),
                'full_name' => $fullName,
                'names' => $request->input('responsible_names'),
                'father_lastname' => $request->input('responsible_father_lastname'),
                'mother_lastname' => $request->input('responsible_mother_lastname'),
                'gender' => $request->input('responsible_gender'),
                'email' => $request->input('responsible_email'),
                'telephone' => $request->input('responsible_telephone'),
                'company_person_id' => $client->id,
                'is_client' => false,
                'is_provider' => false,
                'status' => true,
            ]
        );

        return $responsible->id;
    }

    private function storePdf(Request $request): ?string
    {
        if (! $request->hasFile('signed_pdf')) {
            return null;
        }

        return $request->file('signed_pdf')->store('commercial/contracts', 'public');
    }
}
