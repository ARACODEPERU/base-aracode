<?php

namespace Modules\Bibliodata\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Bibliodata\Entities\BibOrganization;
use Modules\Bibliodata\Entities\BibOrganizationUser;
use Modules\Bibliodata\Entities\BibSubscription;
use Modules\Bibliodata\Entities\BibSubscriptionPlan;

class BibSubscriptionService
{
    public function createPlan(array $data, array $bookIds): BibSubscriptionPlan
    {
        $this->validatePlanBooks($data['scope_type'] ?? 'single_book', $bookIds);

        return DB::transaction(function () use ($data, $bookIds) {
            $plan = BibSubscriptionPlan::create($data);
            $this->syncPlanBooks($plan, $bookIds);

            return $plan->load('books');
        });
    }

    public function updatePlan(BibSubscriptionPlan $plan, array $data, array $bookIds): BibSubscriptionPlan
    {
        $scopeType = $data['scope_type'] ?? $plan->scope_type;
        $this->validatePlanBooks($scopeType, $bookIds);

        return DB::transaction(function () use ($plan, $data, $bookIds) {
            $plan->update($data);
            $this->syncPlanBooks($plan, $bookIds);

            return $plan->fresh()->load('books');
        });
    }

    public function syncPlanBooks(BibSubscriptionPlan $plan, array $bookIds): void
    {
        $bookIds = array_values(array_unique(array_filter(array_map('intval', $bookIds))));
        $plan->books()->sync($bookIds);
    }

    public function validatePlanBooks(string $scopeType, array $bookIds): void
    {
        $bookIds = array_filter($bookIds);

        if ($scopeType === 'single_book' && count($bookIds) !== 1) {
            throw ValidationException::withMessages([
                'book_id' => 'Debe seleccionar exactamente un libro para este plan.',
            ]);
        }

        if ($scopeType === 'limited_books' && count($bookIds) < 1) {
            throw ValidationException::withMessages([
                'book_ids' => 'Debe seleccionar al menos un libro.',
            ]);
        }
    }

    public function computeEndsAt(BibSubscriptionPlan $plan, Carbon $startsAt): ?Carbon
    {
        if ($plan->duration_type === 'lifetime') {
            return null;
        }

        $value = max(1, (int) ($plan->duration_value ?? 1));

        return match ($plan->duration_type) {
            'monthly' => $startsAt->copy()->addMonths($value),
            'yearly' => $startsAt->copy()->addYears($value),
            default => null,
        };
    }

    public function resolveStatus(BibSubscription $subscription): string
    {
        if (in_array($subscription->status, ['cancelled', 'suspended', 'pending'], true)) {
            return $subscription->status;
        }

        if ($subscription->ends_at && $subscription->ends_at->isPast()) {
            return 'expired';
        }

        return $subscription->status === 'pending' ? 'pending' : 'active';
    }

    public function effectiveStatus(BibSubscription $subscription): string
    {
        $resolved = $this->resolveStatus($subscription);

        if ($resolved === 'expired' && $subscription->status === 'active') {
            $subscription->update(['status' => 'expired']);
        }

        return $resolved;
    }

    public function createSubscription(array $data, ?int $createdBy = null): BibSubscription
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $plan = BibSubscriptionPlan::with('books')->findOrFail($data['plan_id']);
            $startsAt = Carbon::parse($data['starts_at']);
            $endsAt = isset($data['ends_at']) && $data['ends_at'] !== ''
                ? Carbon::parse($data['ends_at'])
                : $this->computeEndsAt($plan, $startsAt);

            $this->validateSubscriber($data['subscriber_type'], $data['user_id'] ?? null, $data['organization_id'] ?? null);

            $bookId = $plan->books->first()?->id;

            $subscription = BibSubscription::create([
                'plan_id' => $plan->id,
                'subscriber_type' => $data['subscriber_type'],
                'user_id' => $data['subscriber_type'] === 'individual' ? $data['user_id'] : null,
                'organization_id' => $data['subscriber_type'] === 'organization' ? $data['organization_id'] : null,
                'book_id' => $bookId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
                'created_by' => $createdBy,
            ]);

            return $subscription->load(['plan.books', 'user', 'organization', 'book']);
        });
    }

    public function updateSubscription(BibSubscription $subscription, array $data): BibSubscription
    {
        return DB::transaction(function () use ($subscription, $data) {
            $plan = isset($data['plan_id'])
                ? BibSubscriptionPlan::with('books')->findOrFail($data['plan_id'])
                : $subscription->plan()->with('books')->first();

            $startsAt = isset($data['starts_at'])
                ? Carbon::parse($data['starts_at'])
                : $subscription->starts_at;

            $endsAt = array_key_exists('ends_at', $data)
                ? ($data['ends_at'] ? Carbon::parse($data['ends_at']) : null)
                : ($data['recalculate_ends'] ?? false
                    ? $this->computeEndsAt($plan, $startsAt)
                    : $subscription->ends_at);

            $subscriberType = $data['subscriber_type'] ?? $subscription->subscriber_type;
            $userId = $subscriberType === 'individual'
                ? ($data['user_id'] ?? $subscription->user_id)
                : null;
            $organizationId = $subscriberType === 'organization'
                ? ($data['organization_id'] ?? $subscription->organization_id)
                : null;

            $this->validateSubscriber($subscriberType, $userId, $organizationId);

            $subscription->update([
                'plan_id' => $plan->id,
                'subscriber_type' => $subscriberType,
                'user_id' => $userId,
                'organization_id' => $organizationId,
                'book_id' => $plan->books->first()?->id ?? $subscription->book_id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $data['status'] ?? $subscription->status,
                'notes' => $data['notes'] ?? $subscription->notes,
            ]);

            return $subscription->fresh()->load(['plan.books', 'user', 'organization', 'book']);
        });
    }

    public function cancelSubscription(BibSubscription $subscription): BibSubscription
    {
        $subscription->update(['status' => 'cancelled']);

        return $subscription->fresh();
    }

    public function syncOrganizationMembers(BibOrganization $organization, array $members): void
    {
        $sync = [];
        foreach ($members as $member) {
            $userId = is_array($member) ? ($member['user_id'] ?? $member['id'] ?? null) : $member;
            if (! $userId) {
                continue;
            }
            $role = is_array($member) ? ($member['role'] ?? 'member') : 'member';
            $sync[(int) $userId] = ['role' => in_array($role, ['owner', 'member'], true) ? $role : 'member'];
        }

        $organization->members()->sync($sync);
    }

    /**
     * Fase lector: resolver suscripción activa del usuario para un libro.
     * No usado aún por BibReaderController.
     */
    public function getActiveSubscriptionForUser(User $user, ?int $bookId = null): ?BibSubscription
    {
        $today = Carbon::today();

        $individual = BibSubscription::query()
            ->with('plan')
            ->where('subscriber_type', 'individual')
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'pending'])
            ->when($bookId, fn ($q) => $q->where('book_id', $bookId))
            ->get()
            ->first(fn ($sub) => $this->resolveStatus($sub) === 'active'
                && $sub->starts_at->lte($today)
                && ($sub->ends_at === null || $sub->ends_at->gte($today)));

        if ($individual) {
            return $individual;
        }

        $orgIds = BibOrganizationUser::where('user_id', $user->id)->pluck('organization_id');

        if ($orgIds->isEmpty()) {
            return null;
        }

        return BibSubscription::query()
            ->with('plan')
            ->where('subscriber_type', 'organization')
            ->whereIn('organization_id', $orgIds)
            ->whereIn('status', ['active', 'pending'])
            ->when($bookId, fn ($q) => $q->where('book_id', $bookId))
            ->get()
            ->first(fn ($sub) => $this->resolveStatus($sub) === 'active'
                && $sub->starts_at->lte($today)
                && ($sub->ends_at === null || $sub->ends_at->gte($today)));
    }

    private function validateSubscriber(string $type, ?int $userId, ?int $organizationId): void
    {
        if ($type === 'individual' && ! $userId) {
            throw ValidationException::withMessages([
                'user_id' => 'Seleccione un usuario para la suscripción individual.',
            ]);
        }

        if ($type === 'organization' && ! $organizationId) {
            throw ValidationException::withMessages([
                'organization_id' => 'Seleccione una organización.',
            ]);
        }
    }

    public function durationLabel(BibSubscriptionPlan $plan): string
    {
        return match ($plan->duration_type) {
            'lifetime' => 'Vitalicia',
            'monthly' => ($plan->duration_value ?? 1).' mes(es)',
            'yearly' => ($plan->duration_value ?? 1).' año(s)',
            default => $plan->duration_type,
        };
    }
}
