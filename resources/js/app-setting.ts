import { $themeConfig } from '../theme.config';
import { useAppStore } from '@/stores/index';

export default {
    applyLayoutDefaults() {
        const version = $themeConfig.defaultsVersion ?? 1;
        const stored = parseInt(localStorage.getItem('theme_defaults_version') || '0', 10);

        if (stored >= version) {
            return;
        }

        localStorage.setItem('menu', $themeConfig.menu);
        localStorage.setItem('sidebar', 'false');
        localStorage.setItem('theme_defaults_version', String(version));
    },

    init() {
        const store = useAppStore();

        this.applyLayoutDefaults();

        // set default styles
        let val: any = localStorage.getItem('theme'); // light, dark, system
        val = val || $themeConfig.theme;
        store.toggleTheme(val);

        val = localStorage.getItem('menu'); // vertical, collapsible-vertical, horizontal
        if (!val || !['vertical', 'collapsible-vertical', 'horizontal'].includes(val)) {
            val = $themeConfig.menu;
        }
        store.initMenu(val);

        val = localStorage.getItem('sidebar');
        store.sidebar = val === 'true';

        val = localStorage.getItem('layout'); // full, boxed-layout
        val = val || $themeConfig.layout;
        store.toggleLayout(val);

        val = localStorage.getItem('i18n_locale'); // en, da, de, el, es, fr, hu, it, ja, pl, pt, ru, sv, tr, zh

        val = val || $themeConfig.locale;

        const list = store.languageList;
        const item = list.find((item: any) => item.code === val);
        if (item) {
            this.toggleLanguage(item);
        }

        val = localStorage.getItem('rtlClass'); // rtl, ltr
        val = val || $themeConfig.rtlClass;
        store.toggleRTL(val);

        val = localStorage.getItem('animation'); // animate__fadeIn, animate__fadeInDown, animate__fadeInUp, animate__fadeInLeft, animate__fadeInRight, animate__slideInDown, animate__slideInLeft, animate__slideInRight, animate__zoomIn
        val = val || $themeConfig.animation;
        store.toggleAnimation(val);

        val = localStorage.getItem('navbar'); // navbar-sticky, navbar-floating, navbar-static
        val = val || $themeConfig.navbar;
        store.toggleNavbar(val);

        val = localStorage.getItem('semidark');
        val = val === 'true' ? true : $themeConfig.semidark;
        store.toggleSemidark(val);
    },

    toggleLanguage(item: any) {
        const store = useAppStore();

        let lang: any = null;
        if (item) {
            lang = item;
        } else {
            let code = store.locale || null;
            if (!code) {
                code = localStorage.getItem('i18n_locale');
            }

            item = store.languageList.find((d: any) => d.code === code);
            if (item) {
                lang = item;
            }
        }

        if (!lang) {
            lang = store.languageList.find((d: any) => d.code === 'en');
        }

        store.toggleLocale(lang.code);
        return lang;
    },

    changeAnimation(type = 'add') {
        const store = useAppStore();
        if (store.animation) {
            const eleanimation: any = document.querySelector('.animation');
            if (type === 'add') {
                eleanimation?.classList.add('animate__animated');
                eleanimation?.classList.add(store.animation);
            } else {
                eleanimation?.classList.remove('animate__animated');
                eleanimation?.classList.remove(store.animation);
            }
        }
    },
};
