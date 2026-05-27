import '../sass/torneos-landing.scss';
import { animate, stagger } from 'animejs';

const prefersReducedMotion = () =>
    typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const running = [];

const track = (instance) => {
    if (instance) {
        running.push(instance);
    }
};

const cleanupAll = () => {
    running.forEach((instance) => {
        try {
            instance?.pause?.();
            instance?.cancel?.();
        } catch {
            /* ignore */
        }
    });
    running.length = 0;
};

const parseStatNumber = (raw) => {
    const text = String(raw ?? '').trim();
    const match = text.match(/-?\d+(\.\d+)?/);
    return match ? parseFloat(match[0]) : null;
};

function initMobileNav() {
    const toggle = document.querySelector('[data-se-menu-toggle]');
    const panel = document.querySelector('[data-se-mobile-nav]');
    if (!toggle || !panel) {
        return;
    }

    const close = () => {
        panel.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    };

    toggle.addEventListener('click', () => {
        const open = panel.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.style.overflow = open ? 'hidden' : '';
    });

    panel.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', close);
    });
}

function initHeaderScroll() {
    const header = document.querySelector('.se-header');
    if (!header) {
        return;
    }

    const onScroll = () => {
        header.classList.toggle('is-scrolled', window.scrollY > 24);
    };

    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
}

function initNavHighlight() {
    const links = document.querySelectorAll('.se-nav a[href^="#"], .se-mobile-nav a[href^="#"]');
    const sections = [...links]
        .map((a) => {
            const id = a.getAttribute('href')?.slice(1);
            const el = id ? document.getElementById(id) : null;
            return el ? { link: a, el } : null;
        })
        .filter(Boolean);

    if (!sections.length) {
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }
                const id = entry.target.id;
                document.querySelectorAll('.se-nav a').forEach((a) => {
                    a.classList.toggle('is-active', a.getAttribute('href') === `#${id}`);
                });
            });
        },
        { rootMargin: '-40% 0px -50% 0px', threshold: 0 },
    );

    sections.forEach(({ el }) => observer.observe(el));
}

function runHeroEntrance() {
    const heroTargets = document.querySelectorAll('[data-se-hero]');
    if (!heroTargets.length) {
        return;
    }

    track(
        animate(heroTargets, {
            opacity: [0, 1],
            y: ['32px', '0px'],
            scale: [0.97, 1],
            duration: 1000,
            ease: 'outExpo',
            delay: stagger(90, { start: 120 }),
        }),
    );

    const statValues = document.querySelectorAll('[data-se-count]');
    statValues.forEach((el) => {
        const end = parseStatNumber(el.dataset.seCount ?? el.textContent);
        if (end === null) {
            return;
        }
        const decimals = String(end).includes('.') ? 1 : 0;
        const counter = { val: 0 };
        track(
            animate(counter, {
                val: [0, end],
                duration: 1400,
                ease: 'outExpo',
                delay: 400,
                onUpdate: () => {
                    el.textContent = counter.val.toFixed(decimals);
                },
            }),
        );
    });
}

function runAmbientMotion() {
    const orbs = document.querySelectorAll('.se-ambient__orb');
    orbs.forEach((el, index) => {
        track(
            animate(el, {
                x: ['0px', `${(index % 2 === 0 ? 1 : -1) * (30 + index * 12)}px`, '0px'],
                y: ['0px', `${-20 - index * 15}px`, '0px'],
                scale: [1, 1.08, 1],
                opacity: [0.5, 0.85, 0.5],
                duration: 9000 + index * 2000,
                ease: 'inOutSine',
                loop: true,
            }),
        );
    });

    const grid = document.querySelector('.se-ambient__grid');
    if (grid) {
        track(
            animate(grid, {
                opacity: [0, 0.35],
                duration: 2000,
                ease: 'outQuad',
            }),
        );
    }
}

function initScrollReveal() {
    const items = document.querySelectorAll('[data-se-reveal]');
    if (!items.length) {
        return;
    }

    const revealed = new WeakSet();

    const observer = new IntersectionObserver(
        (entries) => {
            const visible = entries.filter((e) => e.isIntersecting).map((e) => e.target);
            if (!visible.length) {
                return;
            }
            track(
                animate(visible, {
                    opacity: [0, 1],
                    y: ['28px', '0px'],
                    duration: 700,
                    ease: 'outCubic',
                    delay: stagger(60, { from: 'first' }),
                }),
            );
            visible.forEach((el) => revealed.add(el));
        },
        { threshold: 0.12, rootMargin: '0px 0px -8% 0px' },
    );

    items.forEach((el) => observer.observe(el));

    return () => observer.disconnect();
}

function initBrandHover() {
    const brand = document.querySelector('.se-brand__icon');
    if (!brand) {
        return;
    }
    brand.addEventListener('mouseenter', () => {
        track(
            animate(brand, {
                rotate: ['0deg', '-8deg', '8deg', '0deg'],
                scale: [1, 1.08, 1],
                duration: 500,
                ease: 'outElastic(1, .6)',
            }),
        );
    });
}

function boot() {
    const body = document.body;
    if (!body.classList.contains('se-landing')) {
        return;
    }

    if (prefersReducedMotion()) {
        body.classList.add('se-motion-off');
        document.querySelectorAll('[data-se-reveal]').forEach((el) => {
            el.style.opacity = '1';
            el.style.transform = 'none';
        });
        initMobileNav();
        initHeaderScroll();
        initNavHighlight();
        return;
    }

    initMobileNav();
    initHeaderScroll();
    initNavHighlight();
    initBrandHover();
    runAmbientMotion();
    runHeroEntrance();
    initScrollReveal();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

window.addEventListener('pagehide', cleanupAll);
