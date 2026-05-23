import { nextTick, onMounted, onUnmounted } from 'vue';
import { animate, stagger } from 'animejs';

const PARTICLE_COUNT = 16;
const RING_COUNT = 3;

const randomBetween = (min, max) => min + Math.random() * (max - min);

export function useReaderAccessBlockedAnimation(rootRef) {
    const runningAnimations = [];
    let dynamicElements = [];

    const prefersReducedMotion = () =>
        typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const track = (instance) => {
        if (instance) {
            runningAnimations.push(instance);
        }
    };

    const cleanup = () => {
        runningAnimations.forEach((instance) => {
            try {
                instance?.pause?.();
                instance?.cancel?.();
            } catch {
                /* ignore */
            }
        });
        runningAnimations.length = 0;
        dynamicElements.forEach((el) => el.remove());
        dynamicElements = [];
    };

    const createParticleElement = () => {
        const el = document.createElement('span');
        el.className = 'bib-reader-access-blocked__particle';
        el.textContent = Math.random() > 0.5 ? '◆' : '●';
        el.style.left = `${randomBetween(5, 92)}%`;
        el.style.top = `${randomBetween(8, 88)}%`;
        return el;
    };

    const createRingElement = (index) => {
        const el = document.createElement('div');
        el.className = 'bib-reader-access-blocked__ring';
        const size = 140 + index * 90;
        el.style.width = `${size}px`;
        el.style.height = `${size}px`;
        return el;
    };

    const startAmbientAnimations = () => {
        const particles = dynamicElements.filter((el) =>
            el.classList.contains('bib-reader-access-blocked__particle'),
        );
        const rings = dynamicElements.filter((el) => el.classList.contains('bib-reader-access-blocked__ring'));
        const heroWrap = rootRef.value?.querySelector('.bib-reader-access-blocked__hero-wrap');
        const heroCard = rootRef.value?.querySelector('.bib-reader-access-blocked__hero-card');
        const bookArt = rootRef.value?.querySelector('.bib-reader-blocked-art__book');
        const lockArt = rootRef.value?.querySelector('.bib-reader-blocked-art__lock');
        const glow = rootRef.value?.querySelector('.bib-reader-access-blocked__glow');
        const title = rootRef.value?.querySelector('.bib-reader-access-blocked__title');
        const subtitle = rootRef.value?.querySelector('.bib-reader-access-blocked__subtitle');
        const btn = rootRef.value?.querySelector('.bib-reader-access-blocked__btn');

        if (heroWrap) {
            track(
                animate(heroWrap, {
                    y: ['12px', '0px'],
                    opacity: [0, 1],
                    duration: 900,
                    ease: 'outExpo',
                }),
            );
            track(
                animate(heroWrap, {
                    y: ['0px', '-8px', '0px'],
                    duration: 4200,
                    ease: 'inOutSine',
                    loop: true,
                }),
            );
        }

        if (heroCard) {
            track(
                animate(heroCard, {
                    scale: [0.92, 1],
                    duration: 900,
                    ease: 'outExpo',
                }),
            );
        }

        if (glow) {
            track(
                animate(glow, {
                    scale: [0.85, 1.08, 0.85],
                    opacity: [0.45, 0.75, 0.45],
                    duration: 3200,
                    ease: 'inOutSine',
                    loop: true,
                }),
            );
        }

        if (lockArt) {
            track(
                animate(lockArt, {
                    scale: [1, 1.06, 1],
                    duration: 2400,
                    ease: 'inOutSine',
                    loop: true,
                }),
            );
        }

        if (bookArt) {
            track(
                animate(bookArt, {
                    rotate: ['-1deg', '1deg', '-1deg'],
                    duration: 5000,
                    ease: 'inOutSine',
                    loop: true,
                }),
            );
        }

        const orbs = rootRef.value?.querySelectorAll('.bib-reader-blocked-art__orb');
        if (orbs?.length) {
            track(
                animate(orbs, {
                    y: ['0px', '-10px', '0px'],
                    opacity: [0.35, 0.75, 0.35],
                    duration: () => randomBetween(2800, 4200),
                    ease: 'inOutSine',
                    loop: true,
                    delay: stagger(200),
                }),
            );
        }

        if (title) {
            track(
                animate(title, {
                    opacity: [0, 1],
                    y: ['14px', '0px'],
                    duration: 750,
                    ease: 'outExpo',
                    delay: 280,
                }),
            );
        }

        if (subtitle) {
            track(
                animate(subtitle, {
                    opacity: [0, 1],
                    y: ['10px', '0px'],
                    duration: 700,
                    ease: 'outExpo',
                    delay: 380,
                }),
            );
        }

        if (btn) {
            track(
                animate(btn, {
                    opacity: [0, 1],
                    scale: [0.96, 1],
                    duration: 600,
                    ease: 'outExpo',
                    delay: 480,
                }),
            );
        }

        track(
            animate(particles, {
                y: () => [`${randomBetween(-10, 0)}px`, `${randomBetween(-50, -25)}px`],
                opacity: [0, 0.7, 0],
                rotate: () => `${randomBetween(-30, 30)}deg`,
                duration: () => randomBetween(3000, 5500),
                ease: 'inOutQuad',
                loop: true,
                delay: stagger(150),
            }),
        );

        rings.forEach((el, index) => {
            track(
                animate(el, {
                    scale: [0.92, 1.04, 0.92],
                    opacity: [0.1, 0.28, 0.1],
                    duration: 9000 + index * 1200,
                    ease: 'inOutSine',
                    loop: true,
                }),
            );
        });
    };

    onMounted(async () => {
        await nextTick();
        const root = rootRef.value;
        if (!root) {
            return;
        }

        const fxLayer = root.querySelector('.bib-reader-access-blocked__fx');
        if (!fxLayer) {
            return;
        }

        if (prefersReducedMotion()) {
            root.classList.add('bib-reader-access-blocked--static');
            return;
        }

        dynamicElements = [
            ...Array.from({ length: PARTICLE_COUNT }, () => {
                const particle = createParticleElement();
                fxLayer.appendChild(particle);
                return particle;
            }),
            ...Array.from({ length: RING_COUNT }, (_, i) => {
                const ring = createRingElement(i);
                const ringsHost = root.querySelector('.bib-reader-access-blocked__rings');
                if (ringsHost) {
                    ringsHost.appendChild(ring);
                }
                return ring;
            }),
        ];

        track(
            animate(root, {
                opacity: [0, 1],
                duration: 450,
                ease: 'outQuad',
            }),
        );

        startAmbientAnimations();
    });

    onUnmounted(cleanup);

    return { cleanup };
}
