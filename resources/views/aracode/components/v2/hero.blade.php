{{-- Hero Section V2 - ARACODE --}}
<section class="ara-hero">
    {{-- Background elements --}}
    <div class="ara-hero-grid"></div>
    <canvas id="hero-particles" class="ara-hero-canvas"></canvas>
    <div class="ara-orb ara-orb-blue w-[500px] h-[500px] top-[-10%] right-[-5%]"></div>
    <div class="ara-orb ara-orb-green w-[300px] h-[300px] bottom-[10%] left-[5%]"></div>

    <div class="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-32 ara-hero-inner">
        <div class="max-w-4xl mx-auto text-center ara-hero-copy">
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10 mb-8 reveal ara-hero-badge">
                <span class="w-2 h-2 rounded-full bg-ara-green animate-pulse ara-dot"></span>
                <span class="text-white/80 text-sm font-medium">Enterprise Software & AI Solutions</span>
            </div>

            {{-- Main Heading --}}
            <h1 class="text-4xl sm:text-5xl lg:text-7xl font-bold text-white mb-6 leading-tight reveal reveal-delay-1">
                Desarrollamos soluciones digitales
                <span class="text-gradient">que impulsan el crecimiento</span>
                de tu empresa
            </h1>

            {{-- Subtitle --}}
            <p class="text-lg sm:text-xl text-white/70 max-w-2xl mx-auto mb-10 reveal reveal-delay-2">
                Software empresarial, automatización de procesos e inteligencia artificial diseñados para escalar tu negocio y optimizar cada operación.
            </p>

            {{-- CTAs --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 reveal reveal-delay-3">
                {{-- Abre el modal de asesoría (ver components/v2/asesoria-modal.blade.php).
                     El enlace se conserva para cuando el JavaScript no cargue. --}}
                <a href="{{ route('contacto') }}" data-asesoria class="ara-btn ara-btn-primary ara-btn-lg">
                    Solicitar Asesoría
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
                <a href="{{ route('soluciones') }}" class="ara-btn ara-btn-secondary ara-btn-lg">
                    Ver Soluciones
                </a>
            </div>

        </div>{{-- /ara-hero-copy --}}

        {{-- Stats Bar: cuatro tarjetas separadas. El sufijo va en su propio
             span para pintarlo en verde; el contador solo escribe el número. --}}
        <div class="mt-16 pt-8 border-t border-white/10 reveal reveal-delay-4 ara-hero-stats">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="text-center ara-hero-stat">
                    <div class="ara-stat-value"><span data-counter data-target="100" data-suffix="">0</span><i class="ara-suf">+</i></div>
                    <div class="ara-stat-caption">Empresas atendidas</div>
                </div>
                <div class="text-center ara-hero-stat">
                    <div class="ara-stat-value"><span data-counter data-target="5" data-suffix="">0</span><i class="ara-suf">+</i></div>
                    <div class="ara-stat-caption">Años de experiencia</div>
                </div>
                <div class="text-center ara-hero-stat">
                    <div class="ara-stat-value"><span data-counter data-target="500" data-suffix="">0</span><i class="ara-suf">+</i></div>
                    <div class="ara-stat-caption">Usuarios activos</div>
                </div>
                <div class="text-center ara-hero-stat">
                    <div class="ara-stat-value"><span data-counter data-target="99" data-suffix="">0</span><i class="ara-suf">%</i></div>
                    <div class="ara-stat-caption">Uptime garantizado</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Particle Constellation Script --}}
    <script>
    (function() {
        var canvas = document.getElementById('hero-particles');
        if (!canvas) return;

        // La constelación es parte de la identidad del hero: se anima siempre,
        // incluso si el sistema pide movimiento reducido.

        var ctx = canvas.getContext('2d');
        var particles = [];
        var isMobile = window.innerWidth < 768;
        // Densidad proporcional al ancho: la constelación gana presencia en
        // pantallas grandes sin cargar los móviles.
        function calcularDensidad() {
            var w = window.innerWidth;
            if (w < 768) return 30;
            return Math.min(88, Math.round(w / 18));
        }

        var particleCount = calcularDensidad();
        var connectionDistance = 165;
        var animationId;
        var initialized = false;
        var startTime = performance.now();
        var inView = true;

        // Ratón: las partículas cercanas se apartan y se conectan al cursor
        var pointer = { x: 0, y: 0, active: false };
        var pointerRadius = 140;
        var pointerLink = 170;

        function pointerMove(event) {
            var rect = canvas.getBoundingClientRect();
            var x = event.clientX - rect.left;
            var y = event.clientY - rect.top;
            pointer.x = x;
            pointer.y = y;
            pointer.active = x >= 0 && y >= 0 && x <= rect.width && y <= rect.height;
        }

        var heroSection = canvas.parentElement;
        if (heroSection) {
            heroSection.addEventListener('pointermove', pointerMove, { passive: true });
            heroSection.addEventListener('pointerleave', function () { pointer.active = false; });
        }

        // Se pausa al salir de pantalla: el hero no cuesta nada al bajar
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                inView = entries[0].isIntersecting;
            }, { threshold: 0 }).observe(canvas);
        }

        function getColors() {
            var dark = document.documentElement.classList.contains('dark');
            return {
                core: dark ? 'rgba(120, 210, 255, 1)' : 'rgba(1, 136, 238, 0.95)',
                glow: dark ? 'rgba(1, 136, 238, 0.6)' : 'rgba(1, 136, 238, 0.4)',
                glowBig: dark ? 'rgba(1, 136, 238, 0.14)' : 'rgba(1, 136, 238, 0.1)',
                line: dark ? 'rgba(120, 210, 255, 0.5)' : 'rgba(1, 136, 238, 0.32)',
                lightning: dark ? 'rgba(170, 230, 255, 0.8)' : 'rgba(1, 136, 238, 0.65)',
                special: dark ? 'rgba(78, 220, 120, 0.95)' : 'rgba(78, 174, 51, 0.75)',
                specialGlow: dark ? 'rgba(78, 200, 100, 0.35)' : 'rgba(78, 174, 51, 0.22)'
            };
        }

        function resize() {
            var hero = canvas.parentElement;
            var w = hero ? hero.offsetWidth : window.innerWidth;
            var h = hero ? hero.offsetHeight : 720;
            canvas.width = w || 1280;
            canvas.height = h || 720;
            isMobile = window.innerWidth < 768;
            particleCount = calcularDensidad();
        }

        function Particle() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.vx = (Math.random() - 0.5) * 0.35;
            this.vy = (Math.random() - 0.5) * 0.35;
            this.radius = Math.random() * 2.5 + 1.5;
            this.baseOpacity = Math.random() * 0.3 + 0.65;
            this.pulseSpeed = Math.random() * 0.012 + 0.004;
            this.pulsePhase = Math.random() * Math.PI * 2;
            this.isSpecial = Math.random() < 0.1;
        }

        Particle.prototype.update = function() {
            this.x += this.vx;
            this.y += this.vy;
            this.pulsePhase += this.pulseSpeed;
            this.currentOpacity = this.baseOpacity + Math.sin(this.pulsePhase) * 0.2;

            // Repulsión suave alrededor del cursor
            if (pointer.active) {
                var pdx = this.x - pointer.x;
                var pdy = this.y - pointer.y;
                var pd2 = pdx * pdx + pdy * pdy;
                if (pd2 < pointerRadius * pointerRadius && pd2 > 0.01) {
                    var pd = Math.sqrt(pd2);
                    var fuerza = (1 - pd / pointerRadius) * 1.1;
                    this.x += (pdx / pd) * fuerza;
                    this.y += (pdy / pd) * fuerza;
                }
            }
            if (this.x < -10) this.x = canvas.width + 10;
            if (this.x > canvas.width + 10) this.x = -10;
            if (this.y < -10) this.y = canvas.height + 10;
            if (this.y > canvas.height + 10) this.y = -10;
        };

        Particle.prototype.draw = function(colors) {
            var r = this.radius;
            var op = Math.max(0.15, this.currentOpacity);
            var col = this.isSpecial ? colors.special : colors.core;
            var glowCol = this.isSpecial ? colors.specialGlow : colors.glow;
            var glowBigCol = this.isSpecial ? colors.specialGlow : colors.glowBig;

            // Outer glow (large, soft bloom)
            ctx.beginPath();
            ctx.arc(this.x, this.y, r * 5, 0, Math.PI * 2);
            ctx.fillStyle = glowBigCol;
            ctx.globalAlpha = op * 0.6;
            ctx.fill();

            // Inner glow (medium bloom)
            ctx.beginPath();
            ctx.arc(this.x, this.y, r * 2.5, 0, Math.PI * 2);
            ctx.fillStyle = glowCol;
            ctx.globalAlpha = op * 0.75;
            ctx.fill();

            // Core (bright dot)
            ctx.beginPath();
            ctx.arc(this.x, this.y, r, 0, Math.PI * 2);
            ctx.fillStyle = col;
            ctx.globalAlpha = op;
            ctx.fill();

            // White hot center
            ctx.beginPath();
            ctx.arc(this.x, this.y, r * 0.4, 0, Math.PI * 2);
            ctx.fillStyle = '#fff';
            ctx.globalAlpha = op * 0.85;
            ctx.fill();

            ctx.globalAlpha = 1;
        };

        function drawLightning(x1, y1, x2, y2, colors, alpha) {
            var segments = 5;
            var dx = (x2 - x1) / segments;
            var dy = (y2 - y1) / segments;
            var points = [{x: x1, y: y1}];
            var jitter = 18;
            for (var s = 1; s < segments; s++) {
                var midX = x1 + dx * s + (Math.random() - 0.5) * jitter;
                var midY = y1 + dy * s + (Math.random() - 0.5) * jitter;
                points.push({x: midX, y: midY});
            }
            points.push({x: x2, y: y2});

            // Glow layer (wide, soft)
            ctx.beginPath();
            ctx.moveTo(points[0].x, points[0].y);
            for (var p = 1; p < points.length; p++) {
                ctx.lineTo(points[p].x, points[p].y);
            }
            ctx.strokeStyle = colors.glow;
            ctx.globalAlpha = alpha * 0.22;
            ctx.lineWidth = 3.5;
            ctx.stroke();

            // Main bolt
            ctx.beginPath();
            ctx.moveTo(points[0].x, points[0].y);
            for (var p = 1; p < points.length; p++) {
                ctx.lineTo(points[p].x, points[p].y);
            }
            ctx.strokeStyle = colors.lightning;
            ctx.globalAlpha = alpha * 0.55;
            ctx.lineWidth = 1;
            ctx.stroke();

            // Bright core
            ctx.beginPath();
            ctx.moveTo(points[0].x, points[0].y);
            for (var p = 1; p < points.length; p++) {
                ctx.lineTo(points[p].x, points[p].y);
            }
            ctx.strokeStyle = '#fff';
            ctx.globalAlpha = alpha * 0.3;
            ctx.lineWidth = 0.4;
            ctx.stroke();

            ctx.globalAlpha = 1;
        }

        function drawPointerLinks(colors) {
            if (!pointer.active) return;

            for (var i = 0; i < particles.length; i++) {
                var dx = particles[i].x - pointer.x;
                var dy = particles[i].y - pointer.y;
                var dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < pointerLink) {
                    ctx.beginPath();
                    ctx.moveTo(pointer.x, pointer.y);
                    ctx.lineTo(particles[i].x, particles[i].y);
                    ctx.strokeStyle = colors.line;
                    ctx.globalAlpha = (1 - dist / pointerLink) * 0.5;
                    ctx.lineWidth = 0.6;
                    ctx.stroke();
                }
            }

            ctx.beginPath();
            ctx.arc(pointer.x, pointer.y, 2.5, 0, Math.PI * 2);
            ctx.fillStyle = colors.core;
            ctx.globalAlpha = 0.85;
            ctx.fill();
            ctx.globalAlpha = 1;
        }

        function drawConnections(colors) {
            for (var i = 0; i < particles.length; i++) {
                for (var j = i + 1; j < particles.length; j++) {
                    var dx = particles[i].x - particles[j].x;
                    var dy = particles[i].y - particles[j].y;
                    var dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < connectionDistance) {
                        var alpha = 1 - (dist / connectionDistance);
                        if (alpha > 0.35) {
                            drawLightning(particles[i].x, particles[i].y, particles[j].x, particles[j].y, colors, alpha);
                        } else {
                            ctx.beginPath();
                            ctx.moveTo(particles[i].x, particles[i].y);
                            ctx.lineTo(particles[j].x, particles[j].y);
                            ctx.strokeStyle = colors.line;
                            ctx.globalAlpha = alpha * 0.55;
                            ctx.lineWidth = 0.5;
                            ctx.stroke();
                            ctx.globalAlpha = 1;
                        }
                    }
                }
            }
        }

        function init() {
            resize();
            particles = [];
            for (var i = 0; i < particleCount; i++) particles.push(new Particle());
        }

        // Primer fotograma: asegura constelación visible aunque la pestaña
        // esté oculta (requestAnimationFrame no corre en ese caso).
        function drawStatic() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            var colors = getColors();
            for (var i = 0; i < particles.length; i++) {
                particles[i].currentOpacity = particles[i].baseOpacity;
                particles[i].draw(colors);
            }
            drawConnections(colors);
        }

        function animate() {
            animationId = requestAnimationFrame(animate);
            // Pestaña oculta o hero fuera de vista: no se dibuja
            if (document.hidden || !inView) return;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            var colors = getColors();
            for (var i = 0; i < particles.length; i++) {
                particles[i].update();
                particles[i].draw(colors);
            }
            drawConnections(colors);
            drawPointerLinks(colors);
        }

        function startParticles() {
            if (!initialized) {
                init();
                drawStatic();
                animate();
                initialized = true;
            }
        }

        window.addEventListener("resize", function() {
            resize();
            while (particles.length < particleCount) particles.push(new Particle());
            while (particles.length > particleCount) particles.pop();
            if (!initialized) startParticles();
        });

        // Intento inicial: esperar hasta que el canvas tenga dimensiones reales (máx 1.5s)
        function tryStart() {
            var ww = canvas.parentElement ? canvas.parentElement.offsetWidth : 0;
            if (ww > 0 && canvas.width > 0) {
                startParticles();
            } else if (performance.now() - startTime < 1500) {
                requestAnimationFrame(tryStart);
            } else {
                // Fallback: forzar resize e iniciar
                resize();
                startParticles();
            }
        }

        requestAnimationFrame(tryStart);
    })();
    </script>
</section>