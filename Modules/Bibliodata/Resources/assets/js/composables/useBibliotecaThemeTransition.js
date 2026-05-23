import { ref } from 'vue';
import { useAppStore } from '@/stores/index';

const TRANSITION_MS = 420;

const ensureOverlay = () => {
    let overlay = document.getElementById('bib-reader-theme-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'bib-reader-theme-overlay';
        overlay.className = 'bib-reader-theme-overlay';
        overlay.setAttribute('aria-hidden', 'true');
        document.body.appendChild(overlay);
    }
    return overlay;
};

export function useBibliotecaThemeTransition() {
    const store = useAppStore();
    const isTransitioning = ref(false);

    const runOverlay = (targetTheme) => {
        if (isTransitioning.value) {
            return;
        }

        isTransitioning.value = true;
        const overlay = ensureOverlay();

        overlay.classList.toggle('is-to-dark', targetTheme === 'dark');
        overlay.classList.toggle('is-to-light', targetTheme === 'light');
        overlay.classList.add('is-visible');

        window.setTimeout(() => {
            store.toggleTheme(targetTheme);
            window.setTimeout(() => {
                overlay.classList.remove('is-visible', 'is-to-dark', 'is-to-light');
                isTransitioning.value = false;
            }, TRANSITION_MS);
        }, TRANSITION_MS * 0.45);
    };

    const setTheme = (theme) => {
        if (store.theme === theme) {
            return;
        }
        runOverlay(theme);
    };

    const toggleFromCheckbox = (checked) => {
        setTheme(checked ? 'dark' : 'light');
    };

    const enableSmoothTheme = () => {
        document.documentElement.classList.add('bib-reader-smooth-theme');
    };

    return { isTransitioning, setTheme, toggleFromCheckbox, enableSmoothTheme };
}
