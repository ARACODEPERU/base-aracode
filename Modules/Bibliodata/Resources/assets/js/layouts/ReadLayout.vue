<script setup>
    import { Link, router, usePage } from '@inertiajs/vue3';
    import { useAppStore } from '@/stores/index';
    import { ref } from 'vue';
    import VueCollapsible from 'vue-height-collapsible/vue3';
    import iconFolder from '@/Components/vristo/icon/icon-folder.vue';
    import iconCaretDown from '@/Components/vristo/icon/icon-caret-down.vue';
    import iconTxtFile from '@/Components/vristo/icon/icon-txt-file.vue';
    import iconX from '@/Components/vristo/icon/icon-x.vue';
    import iconTableList from '@/Components/vristo/icon/icon-table-list.vue';
    const store = useAppStore();

const overlayVisible = ref(false);
const submenu1 = ref(null);
const submenu2 = ref(null);
const mobileMenu = ref(false);

const options = ref([
  {
    name: "Opción Uno",
    chapters: [
      { title: "Capítulo 1", subchapters: ["Tema 1.1", "Tema 1.2", "Tema 1.3"] },
      { title: "Capítulo 2", subchapters: ["Tema 2.1", "Tema 2.2"] },
    ],
  },
  {
    name: "Opción Dos",
    chapters: [
      { title: "Capítulo A", subchapters: ["A1", "A2"] },
      { title: "Capítulo B", subchapters: ["B1", "B2", "B3"] },
    ],
  },
]);

const openSubmenu = (index) => {
  submenu1.value = options.value[index];
  submenu2.value = null;
  overlayVisible.value = true;
  mobileMenu.value = false;
};

const openSubSubmenu = (chapter) => {
  submenu2.value = chapter;
};

const closeAll = () => {
  submenu1.value = null;
  submenu2.value = null;
  overlayVisible.value = false;
};

</script>

<template>
  <div class="flex flex-col h-screen">
    <!-- HEADER -->
        <header class="flex items-center justify-between px-6 py-3 bg-white border-b shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <Link :href="route('dashboard')" class="flex items-center space-x-4">

                <template v-if="store.theme === 'dark'">
                    <img v-if="$page.props.company.isotipo_negative == '/img/isotipo_negativo.png'" :src="`${baseUrl}/img/isotipo_negativo.png`" alt="Logo" class="w-8 ltr:-ml-1 rtl:-mr-1 inline" />
                    <img v-else :src="`/storage/${$page.props.company.isotipo_negative}`" alt="Logo" class="w-8 ltr:-ml-1 rtl:-mr-1 inline" />
                </template>
                <template v-else>
                    <img v-if="$page.props.company.isotipo == '/img/isotipo.png'" class="w-8 ltr:-ml-1 rtl:-mr-1 inline" :src="`/${$page.props.company.isotipo}`" alt="" />
                    <img v-else class="w-8 ltr:-ml-1 rtl:-mr-1 inline" :src="`/storage/${$page.props.company.isotipo}`" alt="" />
                </template>

                <span
                    class="text-2xl ltr:ml-1.5 rtl:mr-1.5 font-semibold align-middle hidden md:inline dark:text-white-light transition-all duration-300"
                    >{{ $page.props.company.name }}</span
                >
            </Link>

            <div class="flex items-center space-x-4">
                <input
                    type="text"
                    placeholder="Buscar..."
                    class="form-input"
                />
                <button class="btn btn-primary btn-sm">
                Inscribirse
                </button>
            </div>
        </header>

        <!-- MAIN -->
        <div class="relative flex h-screen overflow-hidden bg-white dark:bg-gray-900">
            <div data-dial-init class="fixed end-6 bottom-6 group">
                <div id="speed-dial-menu-bottom-right" class="flex flex-col items-center hidden mb-4 space-y-2">
                </div>
            </div>
            <!-- OSCURECIMIENTO -->
            <div
                v-if="overlayVisible"
                class="absolute inset-0 bg-black bg-opacity-40 z-10 transition-opacity duration-300"
                @click="closeAll"
            ></div>

            <!-- SIDEBAR PRINCIPAL -->
            <aside
                class="w-full sm:w-80 lg:w-96 bg-gray-50 border-r p-4 dark:bg-gray-900 dark:border-gray-700 z-20 flex-shrink-0 hidden md:block"
                >
                <h2 class="font-semibold text-gray-700 mb-3 dark:text-gray-200">Índice</h2>

                <ul>
                    <li v-for="(option, index) in options" :key="index">
                        <button
                            class="flex items-center w-full text-left py-2 px-3 rounded hover:bg-gray-100 dark:hover:bg-gray-800"
                            @click="openSubmenu(index)"
                        >
                            <icon-folder class="text-primary inline relative -top-1 ltr:mr-2 rtl:ml-2" />
                            {{ option.name }}
                        </button>
                    </li>
                </ul>
            </aside>
            <!-- BOTÓN MENU MÓVIL -->
            <button
                @click="mobileMenu = !mobileMenu"
                class="absolute bottom-4 left-4 md:hidden z-50 btn btn-info text-white rounded p-1.5"
                >
                <icon-table-list class="w-8 h-8" />
            </button>
            <!-- SIDEBAR MÓVIL -->
            <transition name="mobile-slide">
                <aside
                    v-if="mobileMenu"
                    class="fixed inset-y-0 left-0 w-full sm:w-80 lg:w-96 bg-gray-900/50 dark:bg-gray-900 border-r dark:border-gray-700 p-4 z-50 md:hidden"
                >
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="font-semibold text-gray-700 dark:text-gray-200">Índice</h2>
                        <button
                            class="md:hidden text-gray-700 dark:text-gray-200"
                            @click="mobileMenu = !mobileMenu"
                        >
                            <icon-x class="w-6 h-6" />
                        </button>
                    </div>
                    <ul>
                        <li v-for="(option, index) in options" :key="index">
                            <button
                            class="w-full text-left py-2 px-3 rounded hover:bg-gray-100 dark:hover:bg-gray-800"
                            @click="openSubmenu(index)"
                            >
                            {{ option.name }}
                            </button>
                        </li>
                    </ul>
                </aside>
            </transition>

            <!-- SUBMENU NIVEL 1 -->
            <transition name="from-sidebar">
                <aside
                    v-if="submenu1"
                    class="absolute top-0 left-0 md:left-80 lg:left-96 w-full sm:w-80 lg:w-96 h-full bg-white border-r p-4 dark:bg-gray-800 dark:border-gray-700 z-30"
                >
                    <h2 class="font-semibold text-gray-700 mb-3 dark:text-gray-200">{{ submenu1.name }}</h2>
                    <ul>
                        <li v-for="(chapter, idx) in submenu1.chapters" :key="idx">
                            <button
                            class="flex items-center w-full text-left py-2 px-3 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                            @click="openSubSubmenu(chapter)"
                            >
                                <icon-folder class="text-primary inline relative -top-1 ltr:mr-2 rtl:ml-2" />
                                {{ chapter.title }}
                            </button>
                        </li>
                    </ul>
                </aside>
            </transition>

            <!-- SUBMENU NIVEL 2 -->
            <transition name="from-sidebar-delay">
                <aside
                    v-if="submenu2"
                    class="absolute top-0 left-0 md:left-[40rem] lg:left-[48rem] w-full sm:w-80 lg:w-96 h-full bg-white border-r p-4 dark:bg-gray-800 dark:border-gray-700 z-40"
                >
                    <h2 class="font-semibold text-gray-700 mb-3 dark:text-gray-200">{{ submenu2.title }}</h2>
                    <ul>
                        <li v-for="(subchapter, i) in submenu2.subchapters" :key="i" class="py-2 px-3 flex items-center">
                            <icon-txt-file class="text-primary inline relative -top-1 ltr:mr-2 rtl:ml-2" />
                            {{ subchapter }}
                        </li>
                    </ul>
                </aside>
            </transition>

            <!-- CONTENIDO PRINCIPAL -->
            <main class="flex-1 p-8 overflow-y-auto relative z-0">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Biblioteca Virtual</h1>
                <p class="mt-4 text-gray-600 dark:text-gray-300">
                    Selecciona una opción del menú lateral para navegar por capítulos y secciones.
                </p>
            </main>
        </div>
    </div>
</template>
<style scoped>
    /* === Transiciones generales === */
    .from-sidebar-enter-active,
    .from-sidebar-leave-active,
    .from-sidebar-delay-enter-active,
    .from-sidebar-delay-leave-active,
    .mobile-slide-enter-active,
    .mobile-slide-leave-active {
        transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1),
                    opacity 0.35s ease;
    }

    /* === Submenu nivel 1 (sale desde el sidebar) === */
    .from-sidebar-enter-from {
        transform: translateX(2rem);
        opacity: 0;
    }
    .from-sidebar-enter-to {
        transform: translateX(0);
        opacity: 1;
    }
    .from-sidebar-leave-from {
        transform: translateX(0);
        opacity: 1;
    }
    .from-sidebar-leave-to {
        transform: translateX(2rem);
        opacity: 0;
    }

    /* === Submenu nivel 2 (con leve retraso y sale igual desde el sidebar anterior) === */
    .from-sidebar-delay-enter-from {
        transform: translateX(2rem);
        opacity: 0;
    }
    .from-sidebar-delay-enter-to {
        transform: translateX(0);
        opacity: 1;
    }
    .from-sidebar-delay-leave-from {
        transform: translateX(0);
        opacity: 1;
    }
    .from-sidebar-delay-leave-to {
        transform: translateX(2rem);
        opacity: 0;
    }

    /* === Sidebar móvil (desliza desde la izquierda) === */
    .mobile-slide-enter-from {
        transform: translateX(-100%);
        opacity: 0;
    }
    .mobile-slide-enter-to {
        transform: translateX(0);
        opacity: 1;
    }
    .mobile-slide-leave-from {
        transform: translateX(0);
        opacity: 1;
    }
    .mobile-slide-leave-to {
        transform: translateX(-100%);
        opacity: 0;
    }
</style>



