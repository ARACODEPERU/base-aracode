<template>
    <div>
        <div
            class="fixed inset-0 bg-[black]/60 z-[51] px-4 hidden transition-[display]"
            :class="{ '!block': store.showThemeCustomizer }"
            @click="store.closeThemeCustomizer()"
        ></div>

        <nav
            class="bg-white fixed ltr:right-0 rtl:left-0 top-0 bottom-0 w-full max-w-[400px] shadow-[-5px_0_25px_0_rgba(94,92,154,0.1)] transition-[transform] duration-300 z-[52] dark:bg-[#0e1726] p-4 ltr:translate-x-full rtl:-translate-x-full"
            :class="{ '!translate-x-0': store.showThemeCustomizer }"
            aria-label="Personalizar interfaz"
        >
            <perfect-scrollbar
                :options="{
                    swipeEasing: true,
                    wheelPropagation: false,
                }"
                class="relative h-full overflow-x-hidden ltr:pr-3 rtl:pl-3 ltr:-mr-3 rtl:-ml-3"
            >
                <div>
                    <div class="text-center relative pb-5">
                        <button
                            type="button"
                            class="absolute top-0 ltr:right-0 rtl:left-0 opacity-30 hover:opacity-100 dark:text-white"
                            aria-label="Cerrar personalizador"
                            @click="store.closeThemeCustomizer()"
                        >
                            <icon-x class="w-5 h-5" />
                        </button>
                        <h4 class="mb-1 dark:text-white">Personalizar interfaz</h4>
                        <p class="text-white-dark text-sm">Ajusta el aspecto y comportamiento del panel. Tus preferencias se guardan en este navegador.</p>
                    </div>
                    <div class="border border-dashed border-[#e0e6ed] dark:border-[#1b2e4b] rounded-md mb-3 p-3">
                        <h5 class="mb-1 text-base dark:text-white leading-none">Tema de color</h5>
                        <p class="text-white-dark text-xs">Modo claro, oscuro o según el sistema.</p>
                        <div class="grid grid-cols-3 gap-2 mt-3">
                            <button
                                type="button"
                                class="btn"
                                :class="[store.theme === 'light' ? 'btn-primary' : 'btn-outline-primary']"
                                @click="store.toggleTheme('light')"
                            >
                                <icon-sun class="w-5 h-5 shrink-0 ltr:mr-2 rtl:ml-2" />
                                Claro
                            </button>
                            <button
                                type="button"
                                class="btn"
                                :class="[store.theme === 'dark' ? 'btn-primary' : 'btn-outline-primary']"
                                @click="store.toggleTheme('dark')"
                            >
                                <icon-moon class="w-5 h-5 shrink-0 ltr:mr-2 rtl:ml-2" />
                                Oscuro
                            </button>
                            <button
                                type="button"
                                class="btn"
                                :class="[store.theme === 'system' ? 'btn-primary' : 'btn-outline-primary']"
                                @click="store.toggleTheme('system')"
                            >
                                <icon-laptop class="w-5 h-5 shrink-0 ltr:mr-2 rtl:ml-2" />
                                Sistema
                            </button>
                        </div>
                    </div>

                    <div class="border border-dashed border-[#e0e6ed] dark:border-[#1b2e4b] rounded-md mb-3 p-3">
                        <h5 class="mb-1 text-base dark:text-white leading-none">Menú de navegación</h5>
                        <p class="text-white-dark text-xs">Disposición del menú principal.</p>
                        <div class="grid grid-cols-3 gap-2 mt-3">
                            <button
                                type="button"
                                class="btn"
                                :class="[store.menu === 'horizontal' ? 'btn-primary' : 'btn-outline-primary']"
                                @click="store.toggleMenu('horizontal')"
                            >
                                Horizontal
                            </button>
                            <button
                                type="button"
                                class="btn"
                                :class="[store.menu === 'vertical' ? 'btn-primary' : 'btn-outline-primary']"
                                @click="store.toggleMenu('vertical')"
                            >
                                Vertical
                            </button>
                            <button
                                type="button"
                                class="btn"
                                :class="[store.menu === 'collapsible-vertical' ? 'btn-primary' : 'btn-outline-primary']"
                                @click="store.toggleMenu('collapsible-vertical')"
                            >
                                Colapsable
                            </button>
                        </div>
                        <div class="mt-5 text-primary">
                            <label class="inline-flex mb-0">
                                <input v-model="store.semidark" type="checkbox" class="form-checkbox" @change="store.toggleSemidark(store.semidark)" />
                                <span>Semi oscuro (barra y menú lateral)</span>
                            </label>
                        </div>
                    </div>
                    <div class="border border-dashed border-[#e0e6ed] dark:border-[#1b2e4b] rounded-md mb-3 p-3">
                        <h5 class="mb-1 text-base dark:text-white leading-none">Ancho del contenido</h5>
                        <p class="text-white-dark text-xs">Contenedor centrado o a ancho completo.</p>
                        <div class="flex gap-2 mt-3">
                            <button
                                type="button"
                                class="btn flex-auto"
                                :class="[store.layout === 'boxed-layout' ? 'btn-primary' : 'btn-outline-primary']"
                                @click="store.toggleLayout('boxed-layout')"
                            >
                                Centrado
                            </button>
                            <button
                                type="button"
                                class="btn flex-auto"
                                :class="[store.layout === 'full' ? 'btn-primary' : 'btn-outline-primary']"
                                @click="store.toggleLayout('full')"
                            >
                                Completo
                            </button>
                        </div>
                    </div>
                    <div class="border border-dashed border-[#e0e6ed] dark:border-[#1b2e4b] rounded-md mb-3 p-3">
                        <h5 class="mb-1 text-base dark:text-white leading-none">Dirección del texto</h5>
                        <p class="text-white-dark text-xs">Izquierda a derecha o derecha a izquierda.</p>
                        <div class="flex gap-2 mt-3">
                            <button
                                type="button"
                                class="btn flex-auto"
                                :class="[store.rtlClass === 'ltr' ? 'btn-primary' : 'btn-outline-primary']"
                                @click="store.toggleRTL('ltr')"
                            >
                                LTR
                            </button>
                            <button
                                type="button"
                                class="btn flex-auto"
                                :class="[store.rtlClass === 'rtl' ? 'btn-primary' : 'btn-outline-primary']"
                                @click="store.toggleRTL('rtl')"
                            >
                                RTL
                            </button>
                        </div>
                    </div>

                    <div class="border border-dashed border-[#e0e6ed] dark:border-[#1b2e4b] rounded-md mb-3 p-3">
                        <h5 class="mb-1 text-base dark:text-white leading-none">Barra superior</h5>
                        <p class="text-white-dark text-xs">Fija, flotante o estática.</p>
                        <div class="mt-3 flex flex-wrap items-center gap-3 text-primary">
                            <label class="inline-flex mb-0">
                                <input
                                    :checked="store.navbar === 'navbar-sticky'"
                                    type="radio"
                                    value="navbar-sticky"
                                    class="form-radio"
                                    @change="store.toggleNavbar('navbar-sticky')"
                                />
                                <span>Fija</span>
                            </label>
                            <label class="inline-flex mb-0">
                                <input
                                    :checked="store.navbar === 'navbar-floating'"
                                    type="radio"
                                    value="navbar-floating"
                                    class="form-radio"
                                    @change="store.toggleNavbar('navbar-floating')"
                                />
                                <span>Flotante</span>
                            </label>
                            <label class="inline-flex mb-0">
                                <input
                                    :checked="store.navbar === 'navbar-static'"
                                    type="radio"
                                    value="navbar-static"
                                    class="form-radio"
                                    @change="store.toggleNavbar('navbar-static')"
                                />
                                <span>Estática</span>
                            </label>
                        </div>
                    </div>

                    <div class="border border-dashed border-[#e0e6ed] dark:border-[#1b2e4b] rounded-md mb-3 p-3">
                        <h5 class="mb-1 text-base dark:text-white leading-none">Transición de páginas</h5>
                        <p class="text-white-dark text-xs">Animación al cambiar de vista.</p>
                        <div class="mt-3">
                            <select v-model="store.animation" class="form-select border-primary text-primary" @change="store.toggleAnimation()">
                                <option value="">Ninguna</option>
                                <option value="animate__fadeIn">Desvanecer</option>
                                <option value="animate__fadeInDown">Desvanecer abajo</option>
                                <option value="animate__fadeInUp">Desvanecer arriba</option>
                                <option value="animate__fadeInLeft">Desvanecer izquierda</option>
                                <option value="animate__fadeInRight">Desvanecer derecha</option>
                                <option value="animate__slideInDown">Deslizar abajo</option>
                                <option value="animate__slideInLeft">Deslizar izquierda</option>
                                <option value="animate__slideInRight">Deslizar derecha</option>
                                <option value="animate__zoomIn">Zoom</option>
                            </select>
                        </div>
                    </div>
                </div>
            </perfect-scrollbar>
        </nav>
    </div>
</template>

<script lang="ts" setup>
    import { onMounted, onUnmounted } from 'vue';
    import { useAppStore } from '@/stores/index';
    import IconX from '@/Components/vristo/icon/icon-x.vue';
    import IconSun from '@/Components/vristo/icon/icon-sun.vue';
    import IconMoon from '@/Components/vristo/icon/icon-moon.vue';
    import IconLaptop from '@/Components/vristo/icon/icon-laptop.vue';

    const store = useAppStore();

    const onEscape = (event: KeyboardEvent) => {
        if (event.key === 'Escape' && store.showThemeCustomizer) {
            store.closeThemeCustomizer();
        }
    };

    onMounted(() => {
        document.addEventListener('keydown', onEscape);
    });

    onUnmounted(() => {
        document.removeEventListener('keydown', onEscape);
    });
</script>
