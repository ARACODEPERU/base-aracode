<?php

namespace Modules\Bibliodata\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Bibliodata\Entities\BibBook;
use Modules\Bibliodata\Services\BibReaderAccessService;

class BibAuthController extends Controller
{
    public function __construct(
        protected BibReaderAccessService $readerAccess
    ) {}

    public function create(): Response
    {
        $config = config('bibliodata.reader_login', []);
        $branding = [
            'appName' => $config['app_name'] ?? 'Biblio Data',
            'tagline' => $config['tagline'] ?? 'Accede a tu biblioteca digital',
            'coverUrl' => null,
        ];

        $book = BibBook::query()
            ->where('status', 'available')
            ->whereNotNull('cover_image')
            ->latest('id')
            ->first(['title', 'cover_image']);

        if ($book) {
            if (empty($config['app_name'])) {
                $branding['appName'] = $book->title;
            }
            $branding['coverUrl'] = asset('storage/' . $book->cover_image);
        }

        return Inertia::render('Bibliodata::Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'branding' => $branding,
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        if (! $this->readerAccess->isLector($user)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'No tienes permiso para acceder a la biblioteca. Se requiere el rol Lector.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('bib_reader_home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('bib_login');
    }
}
