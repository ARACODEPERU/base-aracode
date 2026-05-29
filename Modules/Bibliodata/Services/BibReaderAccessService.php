<?php

namespace Modules\Bibliodata\Services;

use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Modules\Bibliodata\Entities\BibBook;
use Modules\Bibliodata\Entities\BibBookPage;
use Modules\Bibliodata\Entities\BibBookSection;
use Modules\Bibliodata\Entities\BibSubscription;

class BibReaderAccessService
{
    public function __construct(
        protected BibSubscriptionService $subscriptionService
    ) {}

    public function readerRoleName(): string
    {
        return config('bibliodata.reader.role', 'Lector');
    }

    public function isLector(User $user): bool
    {
        return $user->hasRole($this->readerRoleName());
    }

    /**
     * @throws ValidationException
     */
    public function assertLector(User $user): void
    {
        if (! $this->isLector($user)) {
            throw ValidationException::withMessages([
                'email' => 'No tienes permiso para acceder a la biblioteca. Se requiere el rol Lector.',
            ]);
        }
    }

    public function getActiveSubscription(User $user, ?int $bookId = null): ?BibSubscription
    {
        return $this->subscriptionService->getActiveSubscriptionForUser($user, $bookId);
    }

    public function resolveBookForReader(User $user): ?BibBook
    {
        $subscription = $this->getActiveSubscription($user);

        if ($subscription?->book_id) {
            $book = BibBook::query()
                ->where('id', $subscription->book_id)
                ->where('status', 'available')
                ->first();

            if ($book) {
                return $book;
            }
        }

        $bookId = config('bibliodata.reader.default_book_id');

        if ($bookId) {
            return BibBook::query()
                ->where('id', $bookId)
                ->where('status', 'available')
                ->first();
        }

        return BibBook::query()
            ->where('status', 'available')
            ->latest('id')
            ->first();
    }

    public function previewSessionKey(User $user, int $bookId): string
    {
        return "bib_reader.preview.{$user->id}.{$bookId}";
    }

    public function getPreviewPageId(User $user, int $bookId): ?int
    {
        $value = Session::get($this->previewSessionKey($user, $bookId));

        return $value !== null ? (int) $value : null;
    }

    public function setPreviewPageId(User $user, int $bookId, int $pageId): void
    {
        Session::put($this->previewSessionKey($user, $bookId), $pageId);
    }

    /**
     * @return array{allowed: bool, has_subscription: bool, reason: ?string, preview_page_id: ?int}
     */
    public function evaluatePageAccess(User $user, BibBook $book, int $pageId): array
    {
        $subscription = $this->getActiveSubscription($user, $book->id);

        if ($subscription) {
            return [
                'allowed' => true,
                'has_subscription' => true,
                'reason' => null,
                'preview_page_id' => null,
            ];
        }

        $previewPageId = $this->getPreviewPageId($user, $book->id);

        if ($previewPageId === null) {
            $this->setPreviewPageId($user, $book->id, $pageId);

            return [
                'allowed' => true,
                'has_subscription' => false,
                'reason' => null,
                'preview_page_id' => $pageId,
            ];
        }

        if ((int) $previewPageId === $pageId) {
            return [
                'allowed' => true,
                'has_subscription' => false,
                'reason' => null,
                'preview_page_id' => $previewPageId,
            ];
        }

        return [
            'allowed' => false,
            'has_subscription' => false,
            'reason' => 'subscription_required',
            'preview_page_id' => $previewPageId,
        ];
    }

    public function buildAccessPayload(User $user, ?BibBook $book): array
    {
        if (! $book) {
            return [
                'hasActiveSubscription' => false,
                'previewPageId' => null,
            ];
        }

        $subscription = $this->getActiveSubscription($user, $book->id);

        return [
            'hasActiveSubscription' => (bool) $subscription,
            'previewPageId' => $this->getPreviewPageId($user, $book->id),
        ];
    }

    public function assertPageBelongsToBook(BibBookPage $page, BibBook $book): void
    {
        $section = $page->relationLoaded('section')
            ? $page->section
            : BibBookSection::find($page->section_id);

        if (! $section || (int) $section->book_id !== (int) $book->id) {
            abort(404);
        }
    }

    public function buildSectionTree(int $bookId): array
    {
        $book = BibBook::findOrFail($bookId);

        $query = BibBookSection::where('book_id', $bookId)
            ->whereNull('parent_id')
            ->orderBy('order');

        if (! $book->isLevelContent()) {
            $query->with(['children' => fn ($q) => $q->orderBy('order')]);
        }

        $sections = $query->get();

        $sectionIds = $this->collectSectionIds($sections);

        $pageCounts = $sectionIds
            ? BibBookPage::whereIn('section_id', $sectionIds)
                ->selectRaw('section_id, COUNT(*) as total')
                ->groupBy('section_id')
                ->pluck('total', 'section_id')
            : collect();

        return $sections
            ->map(fn ($sec) => $this->formatSectionNode($sec, $pageCounts))
            ->values()
            ->all();
    }

    private function collectSectionIds($sections): array
    {
        $ids = [];
        foreach ($sections as $section) {
            $ids[] = $section->id;
            if ($section->relationLoaded('children') && $section->children->isNotEmpty()) {
                $ids = array_merge($ids, $this->collectSectionIds($section->children));
            }
        }

        return $ids;
    }

    private function formatSectionNode(BibBookSection $section, $pageCounts): array
    {
        $node = [
            'id' => $section->id,
            'title' => $section->title,
            'order' => $section->order,
            'parent_id' => $section->parent_id,
            'pages_count' => (int) ($pageCounts[$section->id] ?? 0),
            'children' => [],
        ];

        if ($section->relationLoaded('children')) {
            $node['children'] = $section->children
                ->map(fn ($child) => $this->formatSectionNode($child, $pageCounts))
                ->values()
                ->all();
        }

        return $node;
    }
}
