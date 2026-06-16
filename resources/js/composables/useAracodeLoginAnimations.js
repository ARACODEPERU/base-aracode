import { nextTick, onMounted, onUnmounted } from 'vue';
import { animate, stagger } from 'animejs';

const randomBetween = (min, max) => min + Math.random() * (max - min);

export function useAracodeLoginAnimations(rootRef) {
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

    const spawnBackground = (container) => {
        if (!container || prefersReducedMotion()) {
            return;
        }

        for (let i = 0; i < 5; i++) {
            const orb = document.createElement('div');
            orb.className = 'aracode-login-orb';
            const size = randomBetween(6, 14);
            orb.style.width = `${size}px`;
            orb.style.height = `${size}px`;
            orb.style.left = `${randomBetween(4, 92)}%`;
            orb.style.top = `${randomBetween(8, 88)}%`;
            container.appendChild(orb);
            dynamicElements.push(orb);

            track(
                animate(orb, {
                    opacity: [0.15, 0.85, 0.2],
                    scale: [0.6, 1.4, 0.8],
                    duration: randomBetween(3000, 6000),
                    ease: 'inOutSine',
                    loop: true,
                    delay: randomBetween(0, 2000),
                }),
            );
        }

        for (let i = 0; i < 3; i++) {
            const ring = document.createElement('div');
            ring.className = 'aracode-login-ring';
            const size = 120 + i * 100;
            ring.style.width = `${size}px`;
            ring.style.height = `${size}px`;
            ring.style.left = `${randomBetween(10, 60)}%`;
            ring.style.top = `${randomBetween(10, 50)}%`;
            container.appendChild(ring);
            dynamicElements.push(ring);

            track(
                animate(ring, {
                    rotate: ['0deg', '360deg'],
                    duration: 18000 + i * 4000,
                    ease: 'linear',
                    loop: true,
                }),
            );
        }
    };

    const animateEntrance = (root) => {
        if (!root || prefersReducedMotion()) {
            return;
        }

        const targets = root.querySelectorAll('[data-login-enter]');
        if (targets.length) {
            track(
                animate(targets, {
                    opacity: [0, 1],
                    translateY: ['28px', '0px'],
                    scale: [0.94, 1],
                    duration: 900,
                    ease: 'outExpo',
                    delay: stagger(80),
                }),
            );
        }

        const modules = root.querySelectorAll('[data-login-module]');
        if (modules.length) {
            track(
                animate(modules, {
                    opacity: [0, 1],
                    translateX: ['-16px', '0px'],
                    duration: 700,
                    ease: 'outCubic',
                    delay: stagger(60, { start: 400 }),
                }),
            );
        }
    };

    onMounted(async () => {
        await nextTick();
        const root = rootRef.value;
        if (!root) {
            return;
        }

        const bg = root.querySelector('[data-login-bg]');
        spawnBackground(bg);
        animateEntrance(root);
    });

    onUnmounted(cleanup);

    return { cleanup };
}
