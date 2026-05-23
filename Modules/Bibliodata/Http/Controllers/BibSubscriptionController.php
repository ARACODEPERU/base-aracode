<?php

namespace Modules\Bibliodata\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Bibliodata\Entities\BibBook;
use Modules\Bibliodata\Entities\BibOrganization;
use Modules\Bibliodata\Entities\BibSubscription;
use Modules\Bibliodata\Entities\BibSubscriptionPlan;
use Modules\Bibliodata\Services\BibSubscriptionService;
use Spatie\Permission\Models\Role;

class BibSubscriptionController extends Controller
{
    use ValidatesRequests;

    public function __construct(
        protected BibSubscriptionService $subscriptionService
    ) {}

    public function index()
    {
        $subscriptions = BibSubscription::with(['plan.books', 'user', 'organization', 'book'])
            ->when(request('status'), fn ($q) => $q->where('status', request('status')))
            ->when(request('plan_id'), fn ($q) => $q->where('plan_id', request('plan_id')))
            ->when(request('book_id'), fn ($q) => $q->where('book_id', request('book_id')))
            ->when(request('subscriber_type'), fn ($q) => $q->where('subscriber_type', request('subscriber_type')))
            ->when(request('search'), function ($q) {
                $term = '%'.request('search').'%';
                $q->where(function ($query) use ($term) {
                    $query->whereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('organization', fn ($o) => $o->where('name', 'like', $term));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        $subscriptions->getCollection()->transform(function (BibSubscription $sub) {
            $sub->effective_status = $this->subscriptionService->effectiveStatus($sub);

            return $sub;
        });

        return Inertia::render('Bibliodata::Subscription/Subscription/List', [
            'subscriptions' => $subscriptions,
            'filters' => request()->only(['search', 'status', 'plan_id', 'book_id', 'subscriber_type']),
            'plans' => BibSubscriptionPlan::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'books' => BibBook::orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Bibliodata::Subscription/Subscription/Create', [
            'plans' => BibSubscriptionPlan::with('books')->where('is_active', true)->orderBy('name')->get(),
            'organizations' => BibOrganization::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'lectorUsers' => $this->lectorUsers(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateSubscription($request);
        $this->subscriptionService->createSubscription($data, $request->user()?->id);

        return to_route('bib_subscriptions');
    }

    public function edit($id)
    {
        $subscription = BibSubscription::with(['plan.books', 'user', 'organization', 'book'])->findOrFail($id);

        return Inertia::render('Bibliodata::Subscription/Subscription/Edit', [
            'subscription' => $subscription,
            'plans' => BibSubscriptionPlan::with('books')->where('is_active', true)->orderBy('name')->get(),
            'organizations' => BibOrganization::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'lectorUsers' => $this->lectorUsers(),
        ]);
    }

    public function update(Request $request)
    {
        $subscription = BibSubscription::findOrFail($request->id);
        $data = $this->validateSubscription($request, $subscription->id);
        $this->subscriptionService->updateSubscription($subscription, $data);

        return to_route('bib_subscriptions');
    }

    public function cancel($id)
    {
        try {
            $subscription = BibSubscription::findOrFail($id);
            $this->subscriptionService->cancelSubscription($subscription);

            return response()->json([
                'success' => true,
                'message' => 'Suscripción cancelada correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function validateSubscription(Request $request, ?int $subscriptionId = null): array
    {
        $rules = [
            'plan_id' => 'required|exists:bib_subscription_plans,id',
            'subscriber_type' => 'required|in:individual,organization',
            'user_id' => 'nullable|required_if:subscriber_type,individual|exists:users,id',
            'organization_id' => 'nullable|required_if:subscriber_type,organization|exists:bib_organizations,id',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'status' => 'required|in:pending,active,expired,cancelled,suspended',
            'notes' => 'nullable|string',
            'recalculate_ends' => 'boolean',
        ];

        if ($subscriptionId) {
            $rules['id'] = 'required|exists:bib_subscriptions,id';
        }

        return $this->validate($request, $rules);
    }

    private function lectorUsers()
    {
        $roleName = config('bibliodata.reader.role', 'Lector');
        $role = Role::where('name', $roleName)->first();

        if (! $role) {
            return User::orderBy('name')->get(['id', 'name', 'email']);
        }

        return User::role($roleName)->orderBy('name')->get(['id', 'name', 'email']);
    }
}
