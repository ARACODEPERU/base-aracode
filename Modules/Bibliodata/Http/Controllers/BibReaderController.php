<?php

namespace Modules\Bibliodata\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Bibliodata\Entities\BibBookPage;
use Modules\Bibliodata\Entities\BibBookSection;
use Modules\Bibliodata\Services\BibReaderAccessService;

class BibReaderController extends Controller
{
    public function __construct(
        protected BibReaderAccessService $readerAccess
    ) {}

    public function index(): Response
    {
        $user = Auth::user();
        $book = $this->readerAccess->resolveBookForReader($user);

        if (! $book) {
            return Inertia::render('Bibliodata::Reader/Home', [
                'user' => ['name' => $user->name],
                'book' => null,
                'sections' => [],
                'access' => [
                    'hasActiveSubscription' => false,
                    'previewPageId' => null,
                ],
                'welcomeMessage' => 'Bienvenido a tu biblioteca digital. Aún no hay un libro disponible para leer.',
            ]);
        }

        $book->load('author.person');

        return Inertia::render('Bibliodata::Reader/Home', [
            'user' => ['name' => $user->name],
            'book' => [
                'id' => $book->id,
                'title' => $book->title,
                'description' => $book->description,
                'coverUrl' => $book->cover_image ? asset('storage/' . $book->cover_image) : null,
                'author' => $book->author?->display_name,
            ],
            'sections' => $this->readerAccess->buildSectionTree($book->id),
            'access' => $this->readerAccess->buildAccessPayload($user, $book),
            'welcomeMessage' => sprintf(
                '¡Bienvenido, %s! Selecciona una página del índice para comenzar a leer «%s».',
                $user->name,
                $book->title
            ),
        ]);
    }

    public function sectionPages(Request $request, int $sectionId)
    {
        $user = Auth::user();
        $book = $this->readerAccess->resolveBookForReader($user);

        if (! $book) {
            return response()->json(['success' => false, 'message' => 'Libro no disponible'], 404);
        }

        $section = BibBookSection::where('book_id', $book->id)->findOrFail($sectionId);

        $perPage = min((int) $request->get('per_page', 100), 200);

        $pages = BibBookPage::where('section_id', $section->id)
            ->orderBy('page_number')
            ->paginate($perPage);

        $pages->getCollection()->transform(fn ($p) => [
            'id' => $p->id,
            'section_id' => $p->section_id,
            'page_number' => $p->page_number,
            'preview' => $this->pagePreview($p->content),
            'has_content' => ! empty(trim(strip_tags($p->content ?? ''))),
        ]);

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
        $book = $this->readerAccess->resolveBookForReader($user);

        if (! $book) {
            return response()->json(['success' => false, 'message' => 'Libro no disponible'], 404);
        }

        $page = BibBookPage::with('section')->findOrFail($id);
        $this->readerAccess->assertPageBelongsToBook($page, $book);

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
            ],
            'book' => [
                'id' => $book->id,
                'title' => $book->title,
            ],
            'access' => [
                'hasActiveSubscription' => $access['has_subscription'],
                'previewPageId' => $access['preview_page_id'] ?? $this->readerAccess->getPreviewPageId($user, $book->id),
            ],
        ]);
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

        return mb_strlen($plain) > 80 ? mb_substr($plain, 0, 80) . '...' : $plain;
    }
}
