@extends('layouts.webpage')

@section('meta_title', 'Blog | ARACODE Smart Solutions')
@section('meta_description', 'Artículos, noticias y recursos sobre tecnología, automatización, inteligencia artificial y transformación digital.')

@section('content')
    @include('components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-12 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Blog</span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 reveal reveal-delay-1">
                    Noticias y <span class="text-gradient">recursos</span>
                </h1>
                <p class="text-lg text-white/70 reveal reveal-delay-2">
                    Artículos sobre tecnología, automatización, inteligencia artificial y transformación digital para tu empresa.
                </p>
            </div>
        </div>
    </section>

    {{-- Blog Content with Sidebar --}}
    <section class="py-10 lg:py-12 bg-ara-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Mobile Sidebar Toggle --}}
            <button onclick="document.getElementById('blog-sidebar').classList.toggle('active')" 
                    class="blog-sidebar-toggle lg:hidden">
                <span class="flex items-center gap-2 text-ara-slate-700 font-medium">
                    <svg class="w-5 h-5 text-ara-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    Filtrar por categoría
                </span>
                <svg class="w-5 h-5 text-ara-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                
                {{-- Sidebar - Categories (1/4) --}}
                <aside id="blog-sidebar" class="lg:col-span-1 lg:sticky lg:top-24 lg:self-start blog-sidebar-content lg:!block space-y-6">
                    
                    {{-- Search --}}
                    <div class="ara-card p-5">
                        <form action="{{ route('blog_principal') }}" method="GET" class="relative">
                            @if(request('category'))
                                <input type="hidden" name="category" value="{{ request('category') }}">
                            @endif
                            <input type="text" name="search" value="{{ $search ?? '' }}" 
                                   placeholder="Buscar artículos..." 
                                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-ara-slate-200 text-sm focus:outline-none focus:border-ara-blue focus:ring-2 focus:ring-ara-blue/20 transition-all">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ara-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            @if($search)
                                <a href="{{ route('blog_principal') }}{{ request('category') ? '?category=' . request('category') : '' }}" 
                                   class="absolute right-3 top-1/2 -translate-y-1/2 text-ara-slate-400 hover:text-ara-blue">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </a>
                            @endif
                        </form>
                    </div>

                    {{-- Categories --}}
                    <div class="ara-card p-5">
                        <h3 class="text-sm font-bold text-ara-slate-700 mb-3 flex items-center gap-2 uppercase tracking-wide">
                            <svg class="w-4 h-4 text-ara-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            Categorías
                        </h3>
                        
                        <nav class="space-y-1">
                            <a href="{{ route('blog_principal') }}" 
                               class="blog-cat-link flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-all
                                      {{ !request('category') ? 'bg-ara-blue text-white' : 'text-ara-slate-600 hover:bg-ara-slate-100' }}">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                    </svg>
                                    Todos
                                </span>
                                <span class="text-xs {{ !request('category') ? 'bg-white/20' : 'bg-ara-slate-100' }} px-2 py-0.5 rounded-full">
                                    {{ $articles->total() }}
                                </span>
                            </a>
                            
                            @foreach($categories as $category)
                                @php
                                    $categoryCount = \Modules\Blog\Entities\BlogArticle::where('category_id', $category->id)->where('status', true)->count();
                                @endphp
                                <a href="{{ route('blog_principal') }}?category={{ $category->id }}{{ $search ? '&search=' . $search : '' }}" 
                                   class="blog-cat-link flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-all
                                          {{ request('category') == $category->id ? 'bg-ara-blue text-white' : 'text-ara-slate-600 hover:bg-ara-slate-100' }}">
                                    <span class="flex items-center gap-2.5">
                                        @if($category->icon)
                                            <span class="w-4 h-4 flex items-center justify-center">
                                                {!! $category->icon !!}
                                            </span>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                            </svg>
                                        @endif
                                        {{ $category->description }}
                                    </span>
                                    <span class="text-xs {{ request('category') == $category->id ? 'bg-white/20' : 'bg-ara-slate-100' }} px-2 py-0.5 rounded-full">
                                        {{ $categoryCount }}
                                    </span>
                                </a>
                            @endforeach
                        </nav>
                    </div>

                    {{-- Popular Articles --}}
                    @if(isset($popular_articles) && $popular_articles->count() > 0)
                    <div class="ara-card p-5">
                        <h3 class="text-sm font-bold text-ara-slate-700 mb-3 flex items-center gap-2 uppercase tracking-wide">
                            <svg class="w-4 h-4 text-ara-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/>
                            </svg>
                            Populares
                        </h3>
                        <div class="space-y-3">
                            @foreach($popular_articles as $popular)
                                <a href="{{ route('blog_article', $popular->url) }}" class="flex gap-3 group">
                                    <div class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0 bg-ara-slate-100">
                                        <img src="{{ $popular->imagen }}" 
                                             alt="{{ $popular->title }}" 
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                             loading="lazy">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-xs font-semibold text-ara-slate-700 group-hover:text-ara-blue transition-colors line-clamp-2 leading-snug">
                                            {{ $popular->title }}
                                        </h4>
                                        <div class="flex items-center gap-2 mt-1.5">
                                            <time class="text-[10px] text-ara-slate-400">
                                                {{ $popular->created_at->format('d M Y') }}
                                            </time>
                                            <span class="text-[10px] text-ara-slate-300">•</span>
                                            <span class="text-[10px] text-ara-slate-400 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                {{ $popular->views ?? 0 }}
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Newsletter --}}
                    <div class="p-5 rounded-xl text-white" style="background: linear-gradient(135deg, #060E2D 0%, #0188EE 100%);">
                        <div class="text-center mb-4">
                            <div class="w-12 h-12 rounded-full bg-white/15 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold mb-1">Suscríbete al newsletter</h3>
                            <p class="text-xs text-white/80">Recibe los mejores artículos directo en tu correo.</p>
                        </div>
                        <form id="blogNewsletterForm" action="{{ route('blog.subscribe') }}" method="POST" class="space-y-3">
                            @csrf
                            <input type="email" name="email" placeholder="Tu correo electrónico" required
                                   class="w-full px-3 py-2.5 rounded-lg bg-white/15 border border-white/25 text-sm text-white placeholder-white/60 focus:outline-none focus:border-white focus:ring-2 focus:ring-white/30 transition-all">
                            <button type="submit" 
                                    class="w-full py-2.5 rounded-lg text-sm font-semibold transition-colors" style="background-color: #ffffff; color: #060E2D;">
                                Suscribirme
                            </button>
                        </form>
                    </div>

                </aside>

                {{-- Main Content (3/4) --}}
                <main class="lg:col-span-3">
                    {{-- Header --}}
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-ara-slate-700">
                            @if($search)
                                Resultados para: "{{ $search }}"
                            @elseif(request('category'))
                                @php $cat = \Modules\Blog\Entities\BlogCategory::find(request('category')); @endphp
                                {{ $cat ? $cat->description : 'Categoría' }}
                            @else
                                Todos los artículos
                            @endif
                        </h2>
                        <span class="text-xs text-ara-slate-400">
                            {{ $articles->total() }} artículo{{ $articles->total() != 1 ? 's' : '' }}
                        </span>
                    </div>

                    {{-- Articles Grid --}}
                    @if($articles->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($articles as $index => $article)
                                <article class="ara-card group overflow-hidden p-0 reveal reveal-delay-{{ ($index % 2) + 1 }}">
                                    {{-- Image --}}
                                    <div class="relative overflow-hidden aspect-[16/9]">
                                        <img src="{{ $article->imagen }}" 
                                             alt="{{ $article->title }}" 
                                             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                             loading="lazy">
                                        @if($article->category)
                                            <span class="absolute top-3 left-3 text-xs font-semibold px-2.5 py-1 rounded-full bg-ara-blue/90 text-white backdrop-blur-sm">
                                                {{ $article->category->description }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    {{-- Content --}}
                                    <div class="p-5">
                                        {{-- Author --}}
                                        @if($article->author)
                                            <div class="flex items-center gap-2 mb-1">
                                                <div class="w-6 h-6 rounded-full bg-ara-blue/10 flex items-center justify-center">
                                                    <svg class="w-3.5 h-3.5 text-ara-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                    </svg>
                                                </div>
                                                <span class="text-xs font-medium text-ara-slate-600">
                                                    {{ $article->author->name }}
                                                </span>
                                            </div>
                                        @endif

                                        {{-- Date --}}
                                        <time datetime="{{ $article->created_at->format('Y-m-d') }}" class="text-xs text-ara-slate-400 block mb-2">
                                            {{ $article->created_at->format('d M Y') }}
                                        </time>

                                        {{-- Title --}}
                                        <h3 class="text-lg font-bold text-ara-slate-700 mb-2 group-hover:text-ara-blue transition-colors line-clamp-2 leading-snug">
                                            <a href="{{ route('blog_article', $article->url) }}">
                                                {{ $article->title }}
                                            </a>
                                        </h3>

                                        {{-- Description --}}
                                        @if($article->short_description)
                                            <p class="text-ara-slate-400 text-sm leading-relaxed mb-3 line-clamp-2">
                                                {{ $article->short_description }}
                                            </p>
                                        @endif

                                        {{-- Read More --}}
                                        <a href="{{ route('blog_article', $article->url) }}" 
                                           class="inline-flex items-center gap-1.5 text-ara-blue font-medium text-xs group-hover:gap-2.5 transition-all">
                                            Leer más
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                            </svg>
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        {{-- Pagination --}}
                        @if($articles->hasPages())
                            <div class="mt-12">
                                {{ $articles->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-20">
                            <svg class="w-16 h-16 mx-auto text-ara-slate-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                            </svg>
                            <h3 class="text-xl font-bold text-ara-slate-700 mb-2">No hay artículos aún</h3>
                            <p class="text-ara-slate-400">Próximamente publicaremos contenido de valor para tu empresa.</p>
                        </div>
                    @endif
                </main>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <x-v2.cta-section 
        title="¿Necesitas asesoría tecnológica?"
        subtitle="Nuestro equipo está listo para ayudarte a encontrar la solución ideal para tu empresa."
        buttonText="Contactar Ahora"
    />

    @include('components.v2.footer')
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('blogNewsletterForm');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = form.querySelector('button[type="submit"]');
        var originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Enviando...';

        var formData = new FormData(form);
        var csrfToken = form.querySelector('input[name="_token"]').value;

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = originalText;
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Suscrito!',
                    text: data.message,
                    confirmButtonColor: '#0188EE',
                    timer: 4000,
                    timerProgressBar: true
                });
                form.reset();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Hubo un error. Intenta nuevamente.',
                    confirmButtonColor: '#0188EE'
                });
            }
        })
        .catch(function(err) {
            btn.disabled = false;
            btn.textContent = originalText;
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo procesar la suscripción. Intenta nuevamente.',
                confirmButtonColor: '#0188EE'
            });
        });
    });
});
</script>
@endpush
@endsection
