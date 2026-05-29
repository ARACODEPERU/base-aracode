<?php

namespace Modules\Bibliodata\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Bibliodata\Entities\BibBook;
use Modules\Bibliodata\Entities\BibBookPage;
use Modules\Bibliodata\Entities\BibBookSection;

class BibBookSectionController extends Controller
{
    public function tree($bookId)
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

        $pageCounts = BibBookPage::whereIn('section_id', $sectionIds)
            ->selectRaw('section_id, COUNT(*) as total')
            ->groupBy('section_id')
            ->pluck('total', 'section_id');

        $tree = $sections->map(fn ($sec) => $this->formatSectionNode($sec, $pageCounts));

        $totalPages = $sectionIds
            ? (int) BibBookPage::whereIn('section_id', $sectionIds)->count()
            : 0;

        return response()->json([
            'success' => true,
            'sections' => $tree,
            'total_pages' => $totalPages,
            'content_structure' => $book->content_structure,
            'can_change_structure' => $book->canChangeContentStructure(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'book_id' => 'required|integer|exists:bib_books,id',
            'parent_id' => 'nullable|integer|exists:bib_book_sections,id',
            'title' => 'required|string|max:255',
        ]);

        $book = BibBook::findOrFail($data['book_id']);

        if ($book->isLevelContent() && ! empty($data['parent_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Este libro usa formato Nivel → Contenido; no se permiten sub-secciones.',
            ], 422);
        }

        if (! empty($data['parent_id'])) {
            BibBookSection::where('book_id', $data['book_id'])->findOrFail($data['parent_id']);
        }

        $orderQuery = BibBookSection::where('book_id', $data['book_id']);
        $orderQuery = ! empty($data['parent_id'])
            ? $orderQuery->where('parent_id', $data['parent_id'])
            : $orderQuery->whereNull('parent_id');

        $section = BibBookSection::create([
            'book_id' => $data['book_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'title' => $data['title'],
            'order' => (int) $orderQuery->max('order') + 1,
        ]);

        $section->load(['children' => fn ($q) => $q->orderBy('order')]);

        return response()->json([
            'success' => true,
            'section' => $this->formatSectionNode($section, collect()),
        ]);
    }

    public function update(Request $request, $id)
    {
        $section = BibBookSection::findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'order' => 'sometimes|integer|min:0',
        ]);

        $section->update($data);

        return response()->json([
            'success' => true,
            'section' => $section,
        ]);
    }

    public function destroy($id)
    {
        $section = BibBookSection::with('children')->findOrFail($id);

        DB::transaction(function () use ($section) {
            $ids = $this->collectSectionIds(collect([$section]));
            BibBookPage::whereIn('section_id', $ids)->delete();
            BibBookSection::whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Sección eliminada correctamente',
        ]);
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
