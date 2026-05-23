<?php

namespace Modules\Bibliodata\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Bibliodata\Entities\BibOrganization;
use Modules\Bibliodata\Services\BibLectorUserService;
use Modules\Bibliodata\Services\BibSubscriptionService;

class BibOrganizationController extends Controller
{
    use ValidatesRequests;

    public function __construct(
        protected BibSubscriptionService $subscriptionService
    ) {}

    public function index()
    {
        $organizations = BibOrganization::withCount('organizationUsers')
            ->when(request('search'), function ($query) {
                $term = '%'.request('search').'%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('document_number', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(12);

        return Inertia::render('Bibliodata::Subscription/Organization/List', [
            'organizations' => $organizations,
            'filters' => request()->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Bibliodata::Subscription/Organization/Create', $this->formPageData());
    }

    public function store(Request $request)
    {
        $data = $this->validateOrganization($request);

        $organization = BibOrganization::create($data);
        $this->subscriptionService->syncOrganizationMembers(
            $organization,
            $request->input('member_ids', [])
        );

        return to_route('bib_organizations');
    }

    public function edit($id)
    {
        $organization = BibOrganization::with(['members.person', 'person'])->findOrFail($id);

        return Inertia::render('Bibliodata::Subscription/Organization/Edit', array_merge(
            $this->formPageData(),
            ['organization' => $organization]
        ));
    }

    public function update(Request $request)
    {
        $organization = BibOrganization::findOrFail($request->id);
        $data = $this->validateOrganization($request);

        $organization->update($data);
        $this->subscriptionService->syncOrganizationMembers(
            $organization,
            $request->input('member_ids', [])
        );

        return to_route('bib_organizations');
    }

    public function destroy($id)
    {
        try {
            $organization = BibOrganization::findOrFail($id);
            if ($organization->subscriptions()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar: tiene suscripciones asociadas.',
                ]);
            }
            $organization->members()->detach();
            $organization->delete();

            return response()->json([
                'success' => true,
                'message' => 'Organización eliminada correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function validateOrganization(Request $request): array
    {
        return $this->validate($request, [
            'person_id' => 'nullable|integer|exists:people,id',
            'name' => 'required|string|max:200',
            'document_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:30',
            'status' => 'required|in:active,inactive',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'integer|exists:users,id',
        ]);
    }

    private function formPageData(): array
    {
        return [
            'identityDocumentTypes' => DB::table('identity_document_type')->orderBy('id')->get(),
        ];
    }
}
