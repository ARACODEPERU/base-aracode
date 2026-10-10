<!DOCTYPE html>
<html lang="es" x-data x-init="$store.theme.init()" :class="$store.theme.dark ? 'dark' : ''">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    {{-- SEO Meta --}}
    <title>@yield('meta_title', 'ARACODE Smart Solutions | Software Empresarial, IA y Automatización')</title>
    <meta name="description" content="@yield('meta_description', 'ARACODE Smart Solutions - Empresa peruana especializada en desarrollo de software empresarial, automatización de procesos, inteligencia artificial y soluciones SaaS.')">
    <meta name="keywords" content="desarrollo software, automatización, inteligencia artificial, SaaS, facturación electrónica, LMS, plataforma digital, Perú">
    <meta name="author" content="ARACODE Smart Solutions">
    
    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('og_title', 'ARACODE Smart Solutions')">
    <meta property="og:description" content="@yield('og_description', 'Soluciones digitales potenciadas con IA para empresas y organizaciones.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('themes/webpage/images/logo.png'))">
    <meta property="og:site_name" content="ARACODE Smart Solutions">
    
    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', 'ARACODE Smart Solutions')">
    <meta name="twitter:description" content="@yield('og_description', 'Soluciones digitales potenciadas con IA para empresas y organizaciones.')">
    {{-- Schema.org JSON-LD --}}
    <script type="application/ld+json">
    {
        {{-- El @@ escapa la directiva @context de Blade (Laravel 12): sin esto el JSON-LD
             abre un if() que nunca cierra y la vista deja de compilar. --}}
        "@@context": "https://schema.org",
        "@@type": "Organization",
        "name": "ARACODE Smart Solutions",
        "url": "{{ url('/') }}",
        "logo": "{{ asset('themes/webpage/images/logo.png') }}",
        "image": "{{ asset('themes/webpage/images/logo.png') }}",
        "description": "Empresa peruana especializada en desarrollo de software empresarial, automatización de procesos e inteligencia artificial.",
        "address": {
            "@@type": "PostalAddress",
            "addressLocality": "Nuevo Chimbote",
            "addressCountry": "PE"
        },
        "telephone": "+51917295856",
        "email": "contacto@aracodeperu.com",
        "sameAs": [
            "https://www.facebook.com/aracodeperu",
            "https://www.instagram.com/aracode_peru/",
            "https://www.linkedin.com/in/aracode-smart-solution-0b3663365",
            "https://www.youtube.com/@AracodePeru"
        ]
    }
    </script>
    @stack('json-ld')

    
    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('themes/webpage/images/icon.png') }}" type="image/png" sizes="32x32">
    
    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    {{-- Tailwind CSS (compiled) --}}
    <link rel="stylesheet" href="{{ asset('css/website.css') }}">
    
    {{-- Custom CSS --}}
    <link rel="stylesheet" href="{{ asset('css/webpage-v2.css') }}">
    
    {{-- Extra Head --}}
    @stack('head')
</head>
<body class="font-ara-sans antialiased" x-data>
    
    @yield('content')
    
    {{-- Scroll Reveal Script --}}
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Intersection Observer for reveal animations
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    // Don't unobserve - allow re-animation if needed
                }
            });
        }, observerOptions);

        // Observe all reveal elements
        document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale').forEach(function(el) {
            observer.observe(el);
        });

        // Navbar scroll effect
        const nav = document.querySelector('.ara-nav');
        if (nav) {
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    nav.classList.add('scrolled');
                } else {
                    nav.classList.remove('scrolled');
                }
            });
        }

        // Mobile menu toggle
        const menuBtn = document.querySelector('.ara-menu-btn');
        const mobileMenu = document.querySelector('.ara-mobile-menu');
        const menuClose = document.querySelector('.ara-menu-close');

        if (menuBtn && mobileMenu) {
            menuBtn.addEventListener('click', function() {
                mobileMenu.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        }

        if (menuClose && mobileMenu) {
            menuClose.addEventListener('click', function() {
                mobileMenu.classList.remove('active');
                document.body.style.overflow = '';
            });
        }

        // Close mobile menu on link click
        document.querySelectorAll('.ara-mobile-menu .nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                mobileMenu.classList.remove('active');
                document.body.style.overflow = '';
            });
        });

        // Counter animation
        function animateCounter(el) {
            const target = parseInt(el.getAttribute('data-target'));
            const suffix = el.getAttribute('data-suffix') || '';
            const duration = 2000;
            const start = 0;
            const increment = target / (duration / 16);
            
            let current = start;
            const timer = setInterval(function() {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                el.textContent = Math.floor(current) + suffix;
            }, 16);
        }

        // Observe counters
        const counterObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('[data-counter]').forEach(function(el) {
            counterObserver.observe(el);
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // ==========================================
        // Dark Mode Toggle
        // ==========================================
        const html = document.documentElement;
        const themeKey = 'aracode-theme';

        // Load saved theme or default to light
        const savedTheme = localStorage.getItem(themeKey);
        if (savedTheme === 'dark') {
            html.classList.add('dark');
        }

        function toggleTheme() {
            html.classList.toggle('dark');
            const isDark = html.classList.contains('dark');
            localStorage.setItem(themeKey, isDark ? 'dark' : 'light');
        }

        // Attach to all toggle buttons
        document.querySelectorAll('#theme-toggle, #theme-toggle-mobile').forEach(function(btn) {
            btn.addEventListener('click', toggleTheme);
        });
    });
    </script>
    

    {{-- Scroll to Top Button --}}
    <button class="ara-scroll-top" id="araScrollTop" aria-label="Volver arriba">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
        </svg>
    </button>

    {{-- Scroll Progress Bar --}}
    <div class="ara-scroll-progress" id="araScrollProgress"></div>

    {{-- WhatsApp Float Button --}}
    <a href="https://wa.me/51917295856?text=Hola%20ARACODE%2C%20me%20interesa%20una%20asesoría" 
       class="ara-whatsapp-float" 
       target="_blank" 
       rel="noopener"
       aria-label="Escríbenos por WhatsApp"
       title="Escríbenos por WhatsApp">
        <span class="ara-phone-ring" aria-hidden="true"></span>
        <span class="ara-phone-ring" aria-hidden="true"></span>
        <svg class="ara-phone-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
        </svg>
    </a>

    @stack('scripts')
</body>
</html>
