<?php

namespace Modules\Bibliodata\Http\Controllers;

use App\Http\Controllers\ApisnetPeController;
use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Modules\Bibliodata\Services\BibLectorUserService;

class BibOrganizationMemberController extends Controller
{
    use ValidatesRequests;

    public function __construct(
        protected BibLectorUserService $lectorUserService
    ) {}

    public function search(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'exclude_ids' => 'nullable|array',
            'exclude_ids.*' => 'integer',
            'per_page' => 'nullable|integer|min:5|max:30',
        ]);

        $paginator = $this->lectorUserService->search(
            trim((string) $request->input('search', '')),
            $request->input('exclude_ids', []),
            (int) $request->input('per_page', 10)
        );

        $paginator->getCollection()->transform(
            fn ($user) => $this->lectorUserService->formatMember($user)
        );

        return response()->json($paginator);
    }

    public function show($id)
    {
        $user = $this->lectorUserService->findForEdit((int) $id);

        return response()->json([
            'success' => true,
            'member' => $this->lectorUserService->formatMember($user),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateMember($request);
        $user = $this->lectorUserService->createOrUpdate($data);

        return response()->json([
            'success' => true,
            'member' => $this->lectorUserService->formatMember($user),
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $this->validateMember($request, true);
        $data['user_id'] = (int) $id;
        $user = $this->lectorUserService->createOrUpdate($data);

        return response()->json([
            'success' => true,
            'member' => $this->lectorUserService->formatMember($user),
        ]);
    }

    /**
     * Consulta RENIEC por DNI (document_type_id = 1).
     */
    public function searchReniec(Request $request)
    {
        $request->validate([
            'document_type_id' => 'required|integer',
            'number' => 'required|string|min:8|max:12',
        ]);

        $type = (int) $request->input('document_type_id');
        $number = preg_replace('/\D/', '', $request->input('number'));

        if ($type !== 1) {
            return response()->json([
                'success' => false,
                'error' => 'La consulta RENIEC solo aplica para DNI.',
            ], 422);
        }

        if (strlen($number) !== 8) {
            return response()->json([
                'success' => false,
                'error' => 'El DNI debe tener 8 dígitos.',
            ], 422);
        }

        $person = Person::where('document_type_id', $type)->where('number', $number)->first();

        if ($person) {
            return response()->json([
                'success' => true,
                'source' => 'database',
                'person' => $person,
                'message' => 'La persona ya está registrada en la base de datos.',
            ]);
        }

        $service = app(ApisnetPeController::class);
        $data = $service->consultaDNI($number);

        return response()->json(array_merge($data ?? ['success' => false], [
            'source' => 'reniec',
        ]));
    }

    private function validateMember(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'document_type_id' => 'required|integer',
            'number' => 'required|string|max:20',
            'names' => 'required|string|max:100',
            'father_lastname' => 'required|string|max:100',
            'mother_lastname' => 'nullable|string|max:100',
            'email' => 'required|email|max:255',
            'telephone' => 'nullable|string|max:30',
            'gender' => 'nullable|in:M,F',
            'password' => ['nullable', 'string', 'min:6'],
        ];

        if ($isUpdate) {
            $rules['email'] = 'required|email|max:255|unique:users,email,'.$request->route('id');
        } else {
            $rules['email'] = 'required|email|max:255|unique:users,email';
        }

        return $this->validate($request, $rules);
    }
}
