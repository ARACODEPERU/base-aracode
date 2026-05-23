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

            if ($data['subscriber_type'] === 'organization') {
                $beneficiaryIds = $data['beneficiary_user_ids'] ?? [];
                $this->syncBeneficiaries(
                    $subscription,
                    $beneficiaryIds,
                    (int) $data['organization_id'],
                    $bookId
                );
            }

            return $subscription->load(['plan.books', 'user', 'organization', 'book', 'beneficiaries']);
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

            $bookId = $plan->books->first()?->id ?? $subscription->book_id;

            $subscription->update([
                'plan_id' => $plan->id,
                'subscriber_type' => $subscriberType,
                'user_id' => $userId,
                'organization_id' => $organizationId,
                'book_id' => $bookId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $data['status'] ?? $subscription->status,
                'notes' => $data['notes'] ?? $subscription->notes,
            ]);

            if ($subscriberType === 'organization' && array_key_exists('beneficiary_user_ids', $data)) {
                $this->syncBeneficiaries(
                    $subscription,
                    $data['beneficiary_user_ids'] ?? [],
                    (int) $organizationId,
                    $bookId
                );
            } elseif ($subscriberType === 'individual') {
                $subscription->beneficiaries()->detach();
            }

            return $subscription->fresh()->load(['plan.books', 'user', 'organization', 'book', 'beneficiaries']);
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
     * Lista miembros de la organización para el modal de beneficiarios.
     */
    public function getOrganizationMembersForSubscription(
        int $organizationId,
        int $planId,
        ?int $subscriptionId = null
    ): array {
        $plan = BibSubscriptionPlan::with('books')->findOrFail($planId);
        $bookId = $plan->books->first()?->id;

        $organization = BibOrganization::with(['members.person'])->findOrFail($organizationId);

        $currentBeneficiaryIds = $subscriptionId
            ? BibSubscription::with('beneficiaries')->find($subscriptionId)?->beneficiaries->pluck('id')->all() ?? []
            : [];

        return $organization->members->map(function (User $user) use ($bookId, $currentBeneficiaryIds) {
            $person = $user->person;
            $hasIndividual = $bookId
                ? $this->userHasActiveIndividualSubscriptionForBook((int) $user->id, (int) $bookId)
                : false;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'document_number' => $person?->number,
                'is_member' => true,
                'has_individual_subscription' => $hasIndividual,
                'individual_subscription_label' => $hasIndividual
                    ? 'Tiene suscripción individual activa'
                    : null,
                'is_current_beneficiary' => in_array($user->id, $currentBeneficiaryIds, true),
            ];
        })->values()->all();
    }

    public function syncBeneficiaries(
        BibSubscription $subscription,
        array $userIds,
        int $organizationId,
        ?int $bookId
    ): void {
        $userIds = array_values(array_unique(array_map('intval', array_filter($userIds))));

        if ($userIds === []) {
            throw ValidationException::withMessages([
                'beneficiary_user_ids' => 'Seleccione al menos un beneficiario.',
            ]);
        }

        $orgMemberIds = BibOrganizationUser::where('organization_id', $organizationId)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($userIds as $userId) {
            if (! in_array($userId, $orgMemberIds, true)) {
                throw ValidationException::withMessages([
                    'beneficiary_user_ids' => 'Uno o más usuarios no pertenecen a la organización.',
                ]);
            }

            if ($bookId && $this->userHasActiveIndividualSubscriptionForBook($userId, $bookId)) {
                throw ValidationException::withMessages([
                    'beneficiary_user_ids' => 'No puede incluir trabajadores con suscripción individual activa para el mismo libro.',
                ]);
            }
        }

        $subscription->beneficiaries()->sync($userIds);
    }

    public function userHasActiveIndividualSubscriptionForBook(int $userId, int $bookId, ?int $excludeSubscriptionId = null): bool
    {
        $today = Carbon::today();

        return BibSubscription::query()
            ->where('subscriber_type', 'individual')
            ->where('user_id', $userId)
            ->where('book_id', $bookId)
            ->whereIn('status', ['active', 'pending'])
            ->when($excludeSubscriptionId, fn ($q) => $q->where('id', '!=', $excludeSubscriptionId))
            ->get()
            ->contains(fn (BibSubscription $sub) => $this->resolveStatus($sub) === 'active'
                && $sub->starts_at->lte($today)
                && ($sub->ends_at === null || $sub->ends_at->gte($today)));
    }

    /**
     * Resolver suscripción activa del usuario para un libro.
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
            ->whereHas('beneficiaries', fn ($q) => $q->where('users.id', $user->id))
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
