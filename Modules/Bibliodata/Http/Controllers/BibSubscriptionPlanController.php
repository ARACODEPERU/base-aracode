<?php

namespace Modules\Bibliodata\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Bibliodata\Entities\BibBook;
use Modules\Bibliodata\Entities\BibSubscriptionPlan;
use Modules\Bibliodata\Services\BibSubscriptionService;

class BibSubscriptionPlanController extends Controller
{
    use ValidatesRequests;

    public function __construct(
        protected BibSubscriptionService $subscriptionService
    ) {}

    public function index()
    {
        $plans = BibSubscriptionPlan::with('books')
            ->when(request('search'), function ($query) {
                $term = '%'.request('search').'%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)->orWhere('code', 'like', $term);
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(12);

        return Inertia::render('Bibliodata::Subscription/Plan/List', [
            'plans' => $plans,
            'filters' => request()->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Bibliodata::Subscription/Plan/Create', [
            'books' => BibBook::orderBy('title')->get(['id', 'title', 'code_name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);
        $bookIds = $validated['book_ids'] ?? [];
        unset($validated['book_ids']);
        $this->subscriptionService->createPlan($validated, $bookIds);

        return to_route('bib_subscription_plans');
    }

    public function edit($id)
    {
        $plan = BibSubscriptionPlan::with('books')->findOrFail($id);

        return Inertia::render('Bibliodata::Subscription/Plan/Edit', [
            'plan' => $plan,
            'books' => BibBook::orderBy('title')->get(['id', 'title', 'code_name']),
        ]);
    }

    public function update(Request $request)
    {
        $plan = BibSubscriptionPlan::findOrFail($request->id);
        $validated = $this->validatePlan($request);
        $bookIds = $validated['book_ids'] ?? [];
        unset($validated['book_ids']);
        $this->subscriptionService->updatePlan($plan, $validated, $bookIds);

        return to_route('bib_subscription_plans');
    }

    public function destroy($id)
    {
        try {
            $plan = BibSubscriptionPlan::findOrFail($id);
            if ($plan->subscriptions()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar: el plan tiene suscripciones asociadas.',
                ]);
            }
            $plan->books()->detach();
            $plan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Plan eliminado correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function validatePlan(Request $request): array
    {
        $validated = $this->validate($request, [
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:80|unique:bib_subscription_plans,code,'.$request->id,
            'description' => 'nullable|string',
            'scope_type' => 'required|in:single_book,limited_books,all_books',
            'max_books' => 'nullable|integer|min:1',
            'duration_type' => 'required|in:monthly,yearly,lifetime',
            'duration_value' => 'nullable|integer|min:1',
            'includes_ai_chatbot' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'book_ids' => 'required|array|min:1',
            'book_ids.*' => 'integer|exists:bib_books,id',
            'id' => 'nullable|integer|exists:bib_subscription_plans,id',
        ]);

        if ($validated['duration_type'] === 'lifetime') {
            $validated['duration_value'] = null;
        }

        $validated['includes_ai_chatbot'] = $request->boolean('includes_ai_chatbot');
        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
