<?php

namespace Modules\Bibliodata\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Modules\Bibliodata\Entities\BibBook;
use Modules\Bibliodata\Entities\BibBookPage;
use Modules\Bibliodata\Entities\BibBookSection;

class BibReaderAccessService
{
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

    public function resolveBookForReader(User $user): ?BibBook
    {
        // Fase lector (próxima): usar BibSubscriptionService::getActiveSubscriptionForUser($user)
        // y el book_id de bib_subscriptions en lugar de default_book_id / primer libro available.
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
        $sections = BibBookSection::where('book_id', $bookId)
            ->whereNull('parent_id')
            ->orderBy('order')
            ->with(['children' => fn ($q) => $q->orderBy('order')])
            ->get();

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
