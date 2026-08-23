<?php

namespace Modules\Bibliodata\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Bibliodata\Entities\BibBookPagePracticalCase;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Bibliodata\Entities\BibBook;
use Modules\Bibliodata\Entities\BibBookPage;
use Modules\Bibliodata\Entities\BibBookSection;
use Modules\Bibliodata\Services\BibReaderAccessService;
use Modules\Bibliodata\Services\ReadingCacheService;

class BibReaderController extends Controller
{
    public function __construct(
        protected BibReaderAccessService $readerAccess,
        protected ReadingCacheService $readingCache,
    ) {}

    public function index(Request $request): Response
    {
        $user = Auth::user();

        if ($this->readerAccess->canAccessAllBooks($user)) {
            return $this->renderShelf($user);
        }

        $book = $this->readerAccess->resolveBookForReader($user);

        if (! $book) {
            return Inertia::render('Bibliodata::Reader/Home', [
                'user' => ['name' => $user->name],
                'book' => null,
                'books' => [],
                'canAccessAllBooks' => false,
                'sections' => [],
                'access' => [
                    'hasActiveSubscription' => false,
                    'previewPageId' => null,
                ],
                'welcomeMessage' => 'Bienvenido a tu biblioteca digital. Aún no hay un libro disponible para leer.',
                'openedBooks' => [],
            ]);
        }

        return $this->renderBook($user, $book);
    }

    public function showBook(int $id): Response
    {
        $user = Auth::user();
        $book = BibBook::query()
            ->where('status', 'available')
            ->findOrFail($id);

        $this->readerAccess->authorizeBookForReader($user, $book);

        return $this->renderBook($user, $book);
    }

    public function sectionPages(Request $request, int $sectionId)
    {
        $user = Auth::user();
        $section = BibBookSection::findOrFail($sectionId);
        $book = $section->book;
        $this->readerAccess->authorizeBookForReader($user, $book);

        $perPage = min((int) $request->get('per_page', 100), 200);

        $pages = BibBookPage::where('section_id', $section->id)
            ->orderBy('page_number')
            ->paginate($perPage);

        $includeCases = $book->isLevelContent();
        $casesByPage = collect();

        if ($includeCases && $pages->total() > 0) {
            $pageIds = $pages->getCollection()->pluck('id');
            $casesByPage = BibBookPagePracticalCase::query()
                ->whereIn('page_id', $pageIds)
                ->where('status', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->groupBy('page_id');
        }

        $pages->getCollection()->transform(function ($p) use ($includeCases, $casesByPage) {
            $item = [
                'id' => $p->id,
                'section_id' => $p->section_id,
                'page_number' => $p->page_number,
                'preview' => $this->pagePreview($p->content),
                'has_content' => ! empty(trim(strip_tags($p->content ?? ''))),
            ];

            if ($includeCases) {
                $item['practical_cases'] = ($casesByPage[$p->id] ?? collect())
                    ->map(fn (BibBookPagePracticalCase $case) => [
                        'id' => $case->id,
                        'page_id' => $case->page_id,
                        'title' => $case->title,
                        'type' => $case->type,
                    ])
                    ->values()
                    ->all();
            }

            return $item;
        });

        return response()->json([
            'success' => true,
            'pages' => $pages,
            'section' => [
                'id' => $section->id,
                'title' => $section->title,
            ],
        ]);
    }

    public function showPage(int $id)
    {
        $user = Auth::user();

        $page = BibBookPage::with([
            'section.book',
            'practicalCases' => fn ($query) => $query
                ->where('status', true)
                ->orderBy('sort_order')
                ->orderBy('id'),
        ])->findOrFail($id);

        $book = $page->section?->book;
        if (! $book) {
            abort(404);
        }
        $this->readerAccess->authorizeBookForReader($user, $book);

        $access = $this->readerAccess->evaluatePageAccess($user, $book, $page->id);

        if (! $access['allowed']) {
            return response()->json([
                'success' => false,
                'code' => 'subscription_required',
                'message' => 'Necesitas una suscripción activa para continuar leyendo.',
                'preview_page_id' => $access['preview_page_id'],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'page' => [
                'id' => $page->id,
                'section_id' => $page->section_id,
                'page_number' => $page->page_number,
                'content' => $page->content ?? '',
                'section_title' => $page->section?->title,
                'practical_cases' => $page->practicalCases
                    ->map(fn (BibBookPagePracticalCase $case) => $this->formatPracticalCase($case))
                    ->values(),
            ],
            'book' => [
                'id' => $book->id,
                'title' => $book->title,
            ],
            'progress' => $this->computePageProgress($page),
            'access' => [
                'hasActiveSubscription' => $access['has_subscription'],
                'previewPageId' => $access['preview_page_id'] ?? $this->readerAccess->getPreviewPageId($user, $book->id),
            ],
        ]);
    }

    public function showPracticalCase(int $pageId, int $caseId)
    {
        $user = Auth::user();

        $page = BibBookPage::with('section.book')->findOrFail($pageId);
        $book = $page->section?->book;
        if (! $book) {
            abort(404);
        }
        $this->readerAccess->authorizeBookForReader($user, $book);

        $access = $this->readerAccess->evaluatePageAccess($user, $book, $page->id);

        if (! $access['allowed']) {
            return response()->json([
                'success' => false,
                'code' => 'subscription_required',
                'message' => 'Necesitas una suscripción activa para continuar leyendo.',
                'preview_page_id' => $access['preview_page_id'],
            ], 403);
        }

        $practicalCase = BibBookPagePracticalCase::query()
            ->where('page_id', $page->id)
            ->where('id', $caseId)
            ->where('status', true)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'case' => $this->formatPracticalCase($practicalCase),
            'page_id' => $page->id,
            'access' => [
                'hasActiveSubscription' => $access['has_subscription'],
                'previewPageId' => $access['preview_page_id'] ?? $this->readerAccess->getPreviewPageId($user, $book->id),
            ],
        ]);
    }

    public function storeProgress(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'book_id' => 'required|integer|exists:bib_books,id',
            'current_page' => 'nullable|integer',
            'chapter' => 'nullable|integer',
            'progress' => 'nullable|numeric|min:0|max:100',
        ]);

        $book = BibBook::findOrFail((int) $validated['book_id']);
        $this->readerAccess->authorizeBookForReader($user, $book);

        $result = $this->readingCache->saveProgress($user->id, 'book', (int) $book->id, [
            'current_page' => isset($validated['current_page']) ? (int) $validated['current_page'] : null,
            'chapter' => isset($validated['chapter']) ? (int) $validated['chapter'] : null,
            'progress' => isset($validated['progress']) ? (int) round((float) $validated['progress']) : null,
        ]);

        return response()->json(array_merge(['success' => true], $result));
    }

    public function syncProgress(Request $request)
    {
        $user = Auth::user();

        $bookId = (int) $request->input('book_id', 0);

        if ($bookId > 0) {
            return response()->json([
                'success' => true,
                'synced' => $this->readingCache->syncProgressToDatabase($user->id, 'book', $bookId),
                'book_id' => $bookId,
            ]);
        }

        return response()->json([
            'success' => true,
            'synced' => $this->readingCache->syncOpenedToDatabase($user->id, 'book'),
        ]);
    }

    public function markBookOpened(int $id)
    {
        $user = Auth::user();
        $book = BibBook::findOrFail($id);
        $this->readerAccess->authorizeBookForReader($user, $book);

        $this->readingCache->addOpenedResource($user->id, 'book', (int) $book->id);
        $this->readingCache->getProgress($user->id, 'book', (int) $book->id);

        return response()->json(['success' => true]);
    }

    public function openedBooksIndex()
    {
        $user = Auth::user();

        if (! $this->readerAccess->canAccessAllBooks($user)) {
            abort(404);
        }

        return response()->json([
            'success' => true,
            'books' => $this->openedBooks($user),
        ]);
    }

    private function renderBook($user, BibBook $book): Response
    {
        $book->load('author.person');
        $allBooksAccess = $this->readerAccess->canAccessAllBooks($user);

        return Inertia::render('Bibliodata::Reader/Home', [
            'user' => ['name' => $user->name],
            'book' => [
                'id' => $book->id,
                'title' => $book->title,
                'description' => $book->description,
                'coverUrl' => $book->cover_image ? asset('storage/' . $book->cover_image) : null,
                'author' => $book->author?->display_name,
                'content_structure' => $book->content_structure ?? BibBook::STRUCTURE_CHAPTER_SUBCHAPTER,
            ],
            'books' => $allBooksAccess ? $this->shelfBooks() : [],
            'canAccessAllBooks' => $allBooksAccess,
            'sections' => $this->readerAccess->buildSectionTree($book->id),
            'access' => $this->readerAccess->buildAccessPayload($user, $book),
            'welcomeMessage' => sprintf(
                '¡Bienvenido, %s! Selecciona una página del índice para comenzar a leer «%s».',
                $user->name,
                $book->title
            ),
            'openedBooks' => $allBooksAccess ? $this->openedBooks($user) : [],
        ]);
    }

    private function renderShelf($user): Response
    {
        return Inertia::render('Bibliodata::Reader/Home', [
            'user' => ['name' => $user->name],
            'book' => null,
            'books' => $this->shelfBooks(),
            'canAccessAllBooks' => true,
            'sections' => [],
            'access' => [
                'hasActiveSubscription' => true,
                'previewPageId' => null,
            ],
            'welcomeMessage' => 'Elige un libro para comenzar a leer.',
            'openedBooks' => $this->openedBooks($user),
        ]);
    }

    private function shelfBooks(): array
    {
        return BibBook::query()
            ->where('status', 'available')
            ->orderBy('title')
            ->get(['id', 'title', 'description', 'cover_image'])
            ->map(fn (BibBook $book) => [
                'id' => $book->id,
                'title' => $book->title,
                'description' => $book->description,
                'coverUrl' => $book->cover_image ? asset('storage/' . $book->cover_image) : null,
            ])
            ->values()
            ->all();
    }

    private function openedBooks($user): array
    {
        $ids = $this->readingCache->getOpenedResources($user->id, 'book');

        if (empty($ids)) {
            return [];
        }

        $books = BibBook::whereIn('id', $ids)
            ->where('status', 'available')
            ->get(['id', 'title', 'cover_image'])
            ->keyBy('id');

        $result = [];

        foreach ($ids as $id) {
            $book = $books->get($id);
            if (! $book) {
                continue;
            }
            $result[] = [
                'id' => (int) $book->id,
                'title' => $book->title,
                'coverUrl' => $book->cover_image ? asset('storage/' . $book->cover_image) : null,
            ];
        }

        return $result;
    }

    private function computePageProgress(BibBookPage $page): array
    {
        $section = $page->relationLoaded('section') ? $page->section : BibBookSection::find($page->section_id);

        if (! $section || ! $section->book_id) {
            return ['percent' => 0, 'page_index' => 0, 'total_pages' => 0];
        }

        $bookId = (int) $section->book_id;
        $sectionIds = BibBookSection::where('book_id', $bookId)
            ->orderBy('order')
            ->pluck('id')
            ->all();

        $counts = BibBookPage::whereIn('section_id', $sectionIds)
            ->selectRaw('section_id, COUNT(*) as total')
            ->groupBy('section_id')
            ->pluck('total', 'section_id');

        $pageIndex = 0;
        $total = 0;
        $found = false;

        foreach ($sectionIds as $sectionId) {
            $count = (int) ($counts[$sectionId] ?? 0);
            $total += $count;

            if ($found) {
                continue;
            }

            if ((int) $sectionId === (int) $section->id) {
                $pageIndex += BibBookPage::where('section_id', $sectionId)
                    ->where('page_number', '<', $page->page_number)
                    ->count();
                $pageIndex += 1;
                $found = true;
            } else {
                $pageIndex += $count;
            }
        }

        return [
            'percent' => $total > 0 ? (int) round(($pageIndex / $total) * 100) : 0,
            'page_index' => $pageIndex,
            'total_pages' => $total,
        ];
    }

    private function pagePreview(?string $content): string
    {
        if (! $content) {
            return '(vacío)';
        }
        $plain = strip_tags($content);
        if ($plain === '') {
            return '(vacío)';
        }

        // Decodificamos entidades HTML (p. ej. &nbsp; -> espacio) para que el preview
        // no muestre codigos como texto literal.
        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalizamos espacios no separables a espacio simple.
        $plain = str_replace("\xc2\xa0", ' ', $plain);
        $plain = preg_replace('/\s+/u', ' ', $plain) ?? $plain;

        $plain = trim($plain);

        if ($plain === '') {
            return '(vacío)';
        }

        return mb_strlen($plain) > 80 ? mb_substr($plain, 0, 80) . '...' : $plain;
    }

    private function formatPracticalCase(BibBookPagePracticalCase $practicalCase): array
    {
        return [
            'id' => $practicalCase->id,
            'title' => $practicalCase->title,
            'type' => $practicalCase->type,
            'content_html' => $practicalCase->content_html ?? '',
            'file_name' => $practicalCase->file_name,
            'file_mime' => $practicalCase->file_mime,
            'file_url' => $practicalCase->file_path ? asset('storage/' . $practicalCase->file_path) : null,
            'sort_order' => (int) $practicalCase->sort_order,
        ];
    }
}
