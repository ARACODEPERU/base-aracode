@extends('layouts.webpage')

@section('meta_title', 'Blog | ARACODE Smart Solutions')
@section('meta_description', 'Artículos, noticias y recursos sobre tecnología, automatización, inteligencia artificial y transformación digital.')

@section('content')
    @include('components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-16 bg-ara-navy relative overflow-hidden">
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

    {{-- Categories Filter --}}
    @if($categories->count() > 0)
    <section class="py-6 bg-white border-b border-ara-slate-100 sticky top-16 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3 overflow-x-auto pb-2">
                <a href="{{ route('blog_principal') }}" 
                   class="px-4 py-2 rounded-full text-sm font-medium transition-all whitespace-nowrap
                          {{ !request('category') ? 'bg-ara-blue text-white' : 'bg-ara-slate-50 text-ara-slate-500 hover:bg-ara-slate-100' }}">
                    Todos
                </a>
                @foreach($categories as $category)
                    <a href="{{ route('blog_principal') }}?category={{ $category->id }}" 
                       class="px-4 py-2 rounded-full text-sm font-medium transition-all whitespace-nowrap
                              {{ request('category') == $category->id ? 'bg-ara-blue text-white' : 'bg-ara-slate-50 text-ara-slate-500 hover:bg-ara-slate-100' }}">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Articles Grid --}}
    <section class="py-16 lg:py-20 bg-ara-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($articles->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($articles as $index => $article)
                        <article class="ara-card group overflow-hidden p-0 reveal reveal-delay-{{ ($index % 3) + 1 }}">
                            {{-- Image --}}
                            <div class="relative overflow-hidden aspect-[16/10]">
                                <img src="{{ $article->imagen }}" 
                                     alt="{{ $article->title }}" 
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                     loading="lazy">
                                @if($article->category)
                                    <span class="absolute top-4 left-4 ara-badge ara-badge-blue">
                                        {{ $article->category->name }}
                                    </span>
                                @endif
                            </div>
                            
                            {{-- Content --}}
                            <div class="p-6">
                                {{-- Date & Author --}}
                                <div class="flex items-center gap-3 text-sm text-ara-slate-400 mb-3">
                                    <time datetime="{{ $article->created_at->format('Y-m-d') }}">
                                        {{ $article->created_at->format('d M Y') }}
                                    </time>
                                    @if($article->author)
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            {{ $article->author->name }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Title --}}
                                <h2 class="text-xl font-bold text-ara-slate-700 mb-3 group-hover:text-ara-blue transition-colors line-clamp-2">
                                    <a href="{{ route('blog_article', $article->url) }}">
                                        {{ $article->title }}
                                    </a>
                                </h2>

                                {{-- Description --}}
                                @if($article->short_description)
                                    <p class="text-ara-slate-400 text-sm leading-relaxed mb-4 line-clamp-3">
                                        {{ $article->short_description }}
                                    </p>
                                @endif

                                {{-- Read More --}}
                                <a href="{{ route('blog_article', $article->url) }}" 
                                   class="inline-flex items-center gap-2 text-ara-blue font-medium text-sm group-hover:gap-3 transition-all">
                                    Leer más
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        </div>
    </section>

    {{-- CTA --}}
    <x-v2.cta-section 
        title="¿Necesitas asesoría tecnológica?"
        subtitle="Nuestro equipo está listo para ayudarte a encontrar la solución ideal para tu empresa."
        buttonText="Contactar Ahora"
    />

    @include('components.v2.footer')
@endsection
