import { nextTick, onMounted, onUnmounted } from 'vue';
import { animate, stagger } from 'animejs';

const BLOB_COUNT = 6;
const ORB_COUNT = 18;
const RING_COUNT = 4;

const randomBetween = (min, max) => min + Math.random() * (max - min);

const createBlobElement = (index) => {
    const el = document.createElement('div');
    const variant = index % 3;
    el.className = `bib-auth-blob ${
        variant === 0 ? 'bib-auth-blob--cyan' : variant === 1 ? 'bib-auth-blob--violet' : 'bib-auth-blob--indigo'
    }`;
    const size = 220 + Math.random() * 280;
    el.style.width = `${size}px`;
    el.style.height = `${size}px`;
    el.style.left = `${randomBetween(0, 72)}%`;
    el.style.top = `${randomBetween(0, 72)}%`;
    return el;
};

const createOrbElement = (index) => {
    const el = document.createElement('div');
    const tone = index % 4;
    el.className = `bib-auth-orb ${
        tone === 0
            ? 'bib-auth-orb--cyan'
            : tone === 1
              ? 'bib-auth-orb--violet'
              : tone === 2
                ? 'bib-auth-orb--amber'
                : 'bib-auth-orb--white'
    }`;
    const size = 4 + Math.random() * 10;
    el.style.width = `${size}px`;
    el.style.height = `${size}px`;
    el.style.left = `${randomBetween(2, 96)}%`;
    el.style.top = `${randomBetween(2, 96)}%`;
    return el;
};

const createRingElement = (index) => {
    const el = document.createElement('div');
    el.className = 'bib-auth-ring';
    const size = 140 + index * 90;
    el.style.width = `${size}px`;
    el.style.height = `${size}px`;
    el.style.left = `${randomBetween(5, 75)}%`;
    el.style.top = `${randomBetween(5, 75)}%`;
    return el;
};

export function useBibliotecaAuthBackground(containerRef) {
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

    const startAmbientAnimations = () => {
        const blobs = dynamicElements.filter((el) => el.classList.contains('bib-auth-blob'));
        const orbs = dynamicElements.filter((el) => el.classList.contains('bib-auth-orb'));
        const rings = dynamicElements.filter((el) => el.classList.contains('bib-auth-ring'));

        blobs.forEach((el, index) => {
            const driftX = randomBetween(-120, 120);
            const driftY = randomBetween(-100, 100);
            track(
                animate(el, {
                    x: ['0px', `${driftX}px`, `${-driftX * 0.6}px`, '0px'],
                    y: ['0px', `${driftY}px`, `${-driftY * 0.5}px`, '0px'],
                    scale: [0.75, 1.15, 0.9, 0.75],
                    opacity: [0.35, 0.7, 0.45, 0.35],
                    duration: 12000 + index * 1500,
                    ease: 'inOutSine',
                    loop: true,
                }),
            );
        });

        track(
            animate(orbs, {
                x: () => `${randomBetween(-90, 90)}px`,
                y: () => `${randomBetween(-70, 70)}px`,
                scale: [0.4, 1.4],
                opacity: [0.15, 0.95, 0.2],
                rotate: () => `${randomBetween(-180, 180)}deg`,
                duration: () => randomBetween(3500, 7000),
                ease: 'inOutQuad',
                loop: true,
                alternate: true,
                delay: stagger(80, { from: 'center' }),
            }),
        );

        rings.forEach((el, index) => {
            track(
                animate(el, {
                    scale: [0.85, 1.08, 0.85],
                    rotate: ['0deg', index % 2 === 0 ? '120deg' : '-120deg', '0deg'],
                    opacity: [0.08, 0.28, 0.08],
                    duration: 14000 + index * 2000,
                    ease: 'inOutSine',
                    loop: true,
                }),
            );
        });
    };

    const runEntrance = () => {
        const grid = containerRef.value?.querySelector('.bib-auth-grid');
        if (grid) {
            track(
                animate(grid, {
                    opacity: [0, 0.45],
                    duration: 2000,
                    ease: 'outQuad',
                }),
            );
        }
    };

    onMounted(async () => {
        await nextTick();
        const container = containerRef.value;
        if (!container) {
            return;
        }

        if (prefersReducedMotion()) {
            container.classList.add('bib-auth-bg--static');
            return;
        }

        dynamicElements = [
            ...Array.from({ length: BLOB_COUNT }, (_, i) => {
                const blob = createBlobElement(i);
                container.appendChild(blob);
                return blob;
            }),
            ...Array.from({ length: ORB_COUNT }, (_, i) => {
                const orb = createOrbElement(i);
                container.appendChild(orb);
                return orb;
            }),
            ...Array.from({ length: RING_COUNT }, (_, i) => {
                const ring = createRingElement(i);
                container.appendChild(ring);
                return ring;
            }),
        ];

        startAmbientAnimations();
        runEntrance();
    });

    onUnmounted(cleanup);

    return { cleanup };
}

/** Animación de entrada para elementos del formulario (anime.js) */
export function animateBibliotecaAuthEntrance(rootRef) {
    const instances = [];

    onMounted(async () => {
        await nextTick();
        if (typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const root = rootRef.value;
        if (!root) {
            return;
        }

        const targets = root.querySelectorAll('[data-bib-auth-enter]');
        if (!targets.length) {
            return;
        }

        instances.push(
            animate(targets, {
                opacity: [0, 1],
                y: ['24px', '0px'],
                scale: [0.96, 1],
                duration: 900,
                ease: 'outExpo',
                delay: stagger(100),
            }),
        );
    });

    onUnmounted(() => {
        instances.forEach((i) => {
            try {
                i?.cancel?.();
            } catch {
                /* ignore */
            }
        });
    });
}
