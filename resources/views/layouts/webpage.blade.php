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
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "ARACODE Smart Solutions",
        "url": "{{ url('/') }}",
        "logo": "{{ asset('themes/webpage/images/logo.png') }}",
        "image": "{{ asset('themes/webpage/images/logo.png') }}",
        "description": "Empresa peruana especializada en desarrollo de software empresarial, automatización de procesos e inteligencia artificial.",
        "address": {
            "@type": "PostalAddress",
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
       aria-label="WhatsApp">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.768.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.288.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.68 9.68 0 01-5.23-1.7c-.432-.26-.76-.29-1.225-.29-.464 0-.967.074-1.36.372-.393.297-1.172 1.02-1.125 2.479.074 1.462.76 2.13.883 2.207.124.074.298.149.447.223.149.074.223.149.347.298.124.149.268.372.268.596 0 .297-.223.52-.397.596-.174.074-.664.223-1.413.074-.571-.149-1.065-.431-1.554-.707-.489-.278-1.172-.967-1.395-1.462-.221-.496-.298-1.02-.074-1.454.224-.434.596-.707.967-.781zm1.872-3.465q.048.006.074.006.596 0 .792-.677.195-.678.12-1.084-.074-.372-.371-.57-.52-.792-.867-1.04-1.36-1.124-1.638-.645-1.883-1.51.024 2.812.024 2.812.867 3.149 2.206 3.986 1.338 1.073 2.327 1.412.989.34.865 1.253-.124.967-.694 1.51-.57.544-1.237.618-.665.074-1.167-.149-.502-.223-1.171-.149-.67.075-1.04-.223z"/>
        </svg>
    </a>

    @stack('scripts')
</body>
</html>
