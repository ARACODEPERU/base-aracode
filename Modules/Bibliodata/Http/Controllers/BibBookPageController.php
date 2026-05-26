<?php

namespace Modules\Bibliodata\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Bibliodata\Entities\BibBookPage;
use Modules\Bibliodata\Entities\BibBookSection;

class BibBookPageController extends Controller
{
    public function index(Request $request, $sectionId)
    {
        $section = BibBookSection::findOrFail($sectionId);

        if ($request->filled('page_number')) {
            $page = BibBookPage::where('section_id', $section->id)
                ->where('page_number', (int) $request->page_number)
                ->first();

            return response()->json([
                'success' => true,
                'page' => $page ? $this->formatPage($page, true) : null,
            ]);
        }

        $perPage = min((int) $request->get('per_page', 50), 100);

        $pages = BibBookPage::where('section_id', $section->id)
            ->orderBy('page_number')
            ->paginate($perPage);

        $pages->getCollection()->transform(fn ($p) => $this->formatPage($p, false));

        return response()->json([
            'success' => true,
            'pages' => $pages,
            'section' => [
                'id' => $section->id,
                'title' => $section->title,
            ],
        ]);
    }

    public function show($id)
    {
        $page = BibBookPage::with('section')->withCount('practicalCases')->findOrFail($id);

        return response()->json([
            'success' => true,
            'page' => $this->formatPage($page, true),
        ]);
    }

    public function store(Request $request, $sectionId)
    {
        $section = BibBookSection::findOrFail($sectionId);

        $data = $request->validate([
            'content' => 'nullable|string',
        ]);

        $nextNumber = (int) BibBookPage::where('section_id', $section->id)->max('page_number') + 1;

        $page = BibBookPage::create([
            'section_id' => $section->id,
            'page_number' => $nextNumber,
            'content' => $data['content'] ?? '',
        ]);

        return response()->json([
            'success' => true,
            'page' => $this->formatPage($page, true),
        ]);
    }

    public function bulkStore(Request $request, $sectionId)
    {
        $section = BibBookSection::findOrFail($sectionId);

        $data = $request->validate([
            'count' => 'nullable|integer|min:1|max:2000',
            'from' => 'nullable|integer|min:1',
            'to' => 'nullable|integer|min:1',
        ]);

        $from = $data['from'] ?? null;
        $to = $data['to'] ?? null;

        if ($from !== null && $to !== null) {
            if ($to < $from) {
                return response()->json([
                    'success' => false,
                    'message' => 'El rango final debe ser mayor o igual al inicial',
                ], 422);
            }
            if (($to - $from + 1) > 2000) {
                return response()->json([
                    'success' => false,
                    'message' => 'Máximo 2000 páginas por operación',
                ], 422);
            }
        } elseif (! empty($data['count'])) {
            $maxExisting = (int) BibBookPage::where('section_id', $section->id)->max('page_number');
            $from = $maxExisting + 1;
            $to = $maxExisting + (int) $data['count'];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Indique cantidad o rango de páginas',
            ], 422);
        }

        $existing = BibBookPage::where('section_id', $section->id)
            ->whereBetween('page_number', [$from, $to])
            ->pluck('page_number')
            ->flip();

        $rows = [];
        $now = now();
        for ($n = $from; $n <= $to; $n++) {
            if ($existing->has($n)) {
                continue;
            }
            $rows[] = [
                'section_id' => $section->id,
                'page_number' => $n,
                'content' => '',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay páginas nuevas que crear en ese rango',
            ], 422);
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            BibBookPage::insert($chunk);
        }

        return response()->json([
            'success' => true,
            'message' => count($rows) . ' página(s) creada(s)',
            'created' => count($rows),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function update(Request $request, $id)
    {
        $page = BibBookPage::findOrFail($id);

        $data = $request->validate([
            'content' => 'nullable|string',
        ]);

        $page->update(['content' => $data['content'] ?? '']);

        return response()->json([
            'success' => true,
            'page' => $this->formatPage($page->fresh(), true),
        ]);
    }

    public function destroy($id)
    {
        $page = BibBookPage::findOrFail($id);
        $sectionId = $page->section_id;
        $deletedNumber = $page->page_number;

        DB::transaction(function () use ($page, $sectionId, $deletedNumber) {
            $page->delete();
            BibBookPage::where('section_id', $sectionId)
                ->where('page_number', '>', $deletedNumber)
                ->decrement('page_number');
        });

        return response()->json([
            'success' => true,
            'message' => 'Página eliminada',
        ]);
    }

    public function navigate(Request $request, $sectionId)
    {
        $section = BibBookSection::findOrFail($sectionId);

        $data = $request->validate([
            'current_page_number' => 'required|integer|min:1',
            'direction' => 'required|in:prev,next',
        ]);

        $query = BibBookPage::where('section_id', $section->id)->orderBy('page_number');

        $page = $data['direction'] === 'next'
            ? $query->where('page_number', '>', $data['current_page_number'])->first()
            : $query->where('page_number', '<', $data['current_page_number'])->orderByDesc('page_number')->first();

        return response()->json([
            'success' => true,
            'page' => $page ? $this->formatPage($page, true) : null,
        ]);
    }

    private function formatPage(BibBookPage $page, bool $withContent): array
    {
        $preview = '';
        if ($page->content) {
            $plain = strip_tags($page->content);
            $preview = mb_strlen($plain) > 80 ? mb_substr($plain, 0, 80) . '...' : $plain;
        }

        $data = [
            'id' => $page->id,
            'section_id' => $page->section_id,
            'page_number' => $page->page_number,
            'preview' => $preview ?: '(vacío)',
            'has_content' => ! empty(trim(strip_tags($page->content ?? ''))),
            'practical_cases_count' => (int) ($page->practical_cases_count ?? 0),
        ];

        if ($withContent) {
            $data['content'] = $page->content ?? '';
        }

        if ($page->relationLoaded('section') && $page->section) {
            $data['section_title'] = $page->section->title;
        }

        return $data;
    }
}
