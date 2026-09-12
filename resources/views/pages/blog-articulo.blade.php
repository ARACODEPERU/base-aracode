@extends('layouts.webpage')

@section('meta_title', $article->title . ' | Blog ARACODE')
@section('meta_description', $article->short_description ?? 'Artículo del blog de ARACODE Smart Solutions')
@section('og_title', $article->title)
@section('og_description', $article->short_description ?? '')
@section('og_image', $article->imagen)

@section('content')
    @include('components.v2.navbar')

    {{-- Article Header --}}
    <section class="pt-32 pb-12 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-white/50 mb-8 reveal" aria-label="Breadcrumb">
                <a href="{{ route('index_main') }}" class="hover:text-white transition-colors">Inicio</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <a href="{{ route('blog_principal') }}" class="hover:text-white transition-colors">Blog</a>
                @if($article->category)
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-white/70">{{ $article->category->name }}</span>
                @endif
            </nav>

            {{-- Category Badge --}}
            @if($article->category)
                <span class="ara-badge ara-badge-blue mb-4 inline-block reveal reveal-delay-1">
                    {{ $article->category->name }}
                </span>
            @endif

            {{-- Title --}}
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-6 leading-tight reveal reveal-delay-2">
                {{ $article->title }}
            </h1>

            {{-- Meta --}}
            <div class="flex flex-wrap items-center gap-4 text-sm text-white/60 reveal reveal-delay-3">
                @if($article->author)
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        {{ $article->author->name }}
                    </span>
                @endif
                <span class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <time datetime="{{ $article->created_at->format('Y-m-d') }}">
                        {{ $article->created_at->format('d \d\e F, Y') }}
                    </time>
                </span>
                @if($article->views)
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        {{ $article->views }} vistas
                    </span>
                @endif
            </div>
        </div>
    </section>

    {{-- Article Content --}}
    <section class="py-12 lg:py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-12">
                {{-- Main Content --}}
                <article class="reveal">
                    {{-- Featured Image --}}
                    @if($article->imagen)
                        <div class="rounded-2xl overflow-hidden mb-8 shadow-lg">
                            <img src="{{ $article->imagen }}" 
                                 alt="{{ $article->title }}" 
                                 class="w-full aspect-video object-cover"
                                 loading="lazy">
                        </div>
                    @endif

                    {{-- Article Body --}}
                    <div class="prose prose-lg max-w-none
                                prose-headings:font-bold prose-headings:text-ara-slate-700
                                prose-p:text-ara-slate-500 prose-p:leading-relaxed
                                prose-a:text-ara-blue prose-a:no-underline hover:prose-a:underline
                                prose-img:rounded-xl
                                prose-strong:text-ara-slate-700">
                        {!! $article->content_text !!}
                    </div>

                    {{-- Share --}}
                    <div class="mt-12 pt-8 border-t border-ara-slate-100">
                        <h4 class="text-sm font-semibold text-ara-slate-700 mb-4 uppercase tracking-wide">Compartir artículo</h4>
                        <div class="flex items-center gap-3">
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" 
                               target="_blank" rel="noopener"
                               class="w-10 h-10 rounded-full bg-ara-slate-100 flex items-center justify-center hover:bg-ara-blue hover:text-white transition-colors text-ara-slate-500"
                               aria-label="Compartir en Facebook">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.77 7.46H14.5v-1.9c0-.9.6-1.1 1-1.1h3V.5h-4.33C10.24.5 9.5 3.44 9.5 5.32v2.15h-3v4h3v12h5v-12h3.85l.42-4z"/></svg>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($article->title) }}" 
                               target="_blank" rel="noopener"
                               class="w-10 h-10 rounded-full bg-ara-slate-100 flex items-center justify-center hover:bg-ara-blue hover:text-white transition-colors text-ara-slate-500"
                               aria-label="Compartir en Twitter">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </a>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(url()->current()) }}&title={{ urlencode($article->title) }}" 
                               target="_blank" rel="noopener"
                               class="w-10 h-10 rounded-full bg-ara-slate-100 flex items-center justify-center hover:bg-ara-blue hover:text-white transition-colors text-ara-slate-500"
                               aria-label="Compartir en LinkedIn">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                            <a href="https://wa.me/?text={{ urlencode($article->title . ' ' . url()->current()) }}" 
                               target="_blank" rel="noopener"
                               class="w-10 h-10 rounded-full bg-ara-slate-100 flex items-center justify-center hover:bg-green-500 hover:text-white transition-colors text-ara-slate-500"
                               aria-label="Compartir en WhatsApp">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </a>
                        </div>
                    </div>
                </article>

                {{-- Sidebar --}}
                <aside class="hidden lg:block">
                    {{-- Categories --}}
                    @if($categories->count() > 0)
                        <div class="ara-card mb-6">
                            <h3 class="text-lg font-bold text-ara-slate-700 mb-4">Categorías</h3>
                            <ul class="space-y-2">
                                @foreach($categories as $category)
                                    <li>
                                        <a href="{{ route('blog_principal') }}?category={{ $category->id }}" 
                                           class="flex items-center justify-between text-ara-slate-500 hover:text-ara-blue transition-colors text-sm py-1">
                                            <span>{{ $category->name }}</span>
                                            @if(isset($articlesByCategory[$category->id]))
                                                <span class="text-ara-slate-300">{{ $articlesByCategory[$category->id]->count() }}</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Latest Articles --}}
                    @if($latest_articles->count() > 0)
                        <div class="ara-card">
                            <h3 class="text-lg font-bold text-ara-slate-700 mb-4">Últimos artículos</h3>
                            <div class="space-y-4">
                                @foreach($latest_articles->take(4) as $latest)
                                    @if($latest->url !== $article->url)
                                        <a href="{{ route('blog_article', $latest->url) }}" class="flex gap-3 group">
                                            <img src="{{ $latest->imagen }}" 
                                                 alt="{{ $latest->title }}" 
                                                 class="w-16 h-16 rounded-lg object-cover flex-shrink-0"
                                                 loading="lazy">
                                            <div>
                                                <h4 class="text-sm font-semibold text-ara-slate-700 group-hover:text-ara-blue transition-colors line-clamp-2">
                                                    {{ $latest->title }}
                                                </h4>
                                                <time class="text-xs text-ara-slate-400">
                                                    {{ $latest->created_at->format('d M Y') }}
                                                </time>
                                            </div>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <x-v2.cta-section />

    @include('components.v2.footer')
@endsection
