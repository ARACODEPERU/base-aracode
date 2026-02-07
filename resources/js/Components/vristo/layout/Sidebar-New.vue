<script setup>
    // Asumiendo que usas Pinia para el store
    import { useAppStore } from '@/stores/index';
    import { computed, ref, onMounted, nextTick, watch } from 'vue';
    import VueCollapsible from 'vue-height-collapsible/vue3';
    import { Link, usePage } from '@inertiajs/vue3';
    import menuData from './MenuData.js';
    import { Tooltip } from 'ant-design-vue'

    const store = useAppStore();
    const xasset = assetUrl;
    const page = usePage();


    // Computed para determinar si está colapsado
    const isCollapsed = computed(() => {
        return store.menu === 'collapsible-vertical';
    });

    // Estado para controlar qué sección está expandida
    const expandedSections = ref({});

    // Estado para manejar la URL actual y restaurar estados
    const currentPath = ref('');

    // Estado para controlar qué módulo está activo
    const activeModule = ref('Dashboard'); // Por defecto el módulo edit está activo
    const optionsDefault = menuData.value[0] ?? [];
    // Estado para almacenar las opciones del módulo activo
    const moduleSelected = ref([]);

    // Estado para controlar la opción activa actual
    const activeOption = ref(null);
    const activeSubOption = ref(null);

    // Función para manejar clicks en los botones de módulos
    const handleModuleClick = (module) => {
        isCollapsed.value = false;
        activeModule.value = module.text; // Guardar el módulo activo
        moduleSelected.value = module || []; // Cargar las opciones del módulo

        // Guardar estado en localStorage (revertir temporalmente para estabilidad)
        localStorage.setItem('activeModule', module.text);
        localStorage.setItem('moduleSelected', JSON.stringify(module));

        // Actualizar las secciones expandidas si es necesario
        if (module && module.items) {
            expandedSections.value[module.text] = true;
            localStorage.setItem('expandedSections', JSON.stringify(expandedSections.value));
        }
    };

    // Función para manejar clicks en opciones principales
    const handleOptionClick = (optionText) => {
        expandedSections.value = optionText;
        // Actualizar la opción activa
        activeOption.value = optionText;
        activeSubOption.value = null; // Resetear subopción

        // Guardar estado
        localStorage.setItem('expandedSections', JSON.stringify(expandedSections.value));
        localStorage.setItem('activeOption', optionText);
    };

    // Función para manejar clicks en subopciones
    const handleSubOptionClick = (optionText, subOptionText) => {
        // Actualizar opciones activas
        activeOption.value = optionText;
        activeSubOption.value = subOptionText;

        // Asegurar que la sección esté expandida
        expandedSections.value = optionText;

        // Guardar estado
        localStorage.setItem('expandedSections', JSON.stringify(expandedSections.value));
        localStorage.setItem('activeOption', optionText);
        localStorage.setItem('activeSubOption', subOptionText);
    };

    onMounted(() => {

        isCollapsed.value = !isCollapsed.value;

        // Restaurar estado desde localStorage de forma segura
        const savedModule = localStorage.getItem('activeModule');


        if (savedModule) {
            activeModule.value = savedModule;
        }

        const savedExpandedSections = localStorage.getItem('expandedSections');
        if (savedExpandedSections) {
            try {
                expandedSections.value = JSON.parse(savedExpandedSections);
            } catch (e) {
                expandedSections.value = {};
            }
        } else {
            expandedSections.value = {};
        }

        const savedModuleSelected = localStorage.getItem('moduleSelected');
        if (savedModuleSelected) {
            try {
                moduleSelected.value = JSON.parse(savedModuleSelected);
            } catch (e) {
                //console.warn('Error parsing moduleSelected from localStorage:', e);
            }
        } else {
            moduleSelected.value = optionsDefault;
        }

        // Restaurar opciones activas desde localStorage PRIMERO
        const savedActiveOption = localStorage.getItem('activeOption');
        const savedActiveSubOption = localStorage.getItem('activeSubOption');

        if (savedActiveOption) {
            activeOption.value = savedActiveOption;
            expandedSections.value = savedActiveOption;
        }

        if (savedActiveSubOption) {
            activeSubOption.value = savedActiveSubOption;
        }

    });

    watch(() => page.props.auth, (newAuth, oldAuth) => {
        // Si el usuario cambió o se desautenticó
        if (!newAuth?.user || newAuth?.user !== oldAuth?.user) {
            clearSidebarState();
        }
    }, { deep: true });


    const getImage = (path) => {
        return xasset + 'storage/'+ path;
    }

    const colorTooltip = 'purple';
    const fontTitleTooltip = 'font-bold text-gray-200';

</script>

<template>
    <div :class="{ 'dark': store.semidark || store.theme === 'dark' }">
        <nav
            class="sidebar fixed min-h-screen h-full top-0 bottom-0 shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] dark:shadow-[5px_0_25px_0_rgba(0,0,0,0.3)] z-50 transition-all duration-300"
            :class="isCollapsed ? 'w-[70px]' : 'w-[300px]'"
        >
            <div id="divSiempreVisible" class="bg-white dark:bg-slate-800 h-full flex overflow-hidden">
                <!-- BOTONES MODULOS -->
                <div
                    class="flex flex-col w-[70px] min-w-[70px] items-center bg-blue-50 dark:bg-slate-700 border-r border-slate-200/50 dark:border-slate-600/50 pt-4 pb-2.5 h-full z-10"
                >
                    <div class="mb-6 flex h-10 w-10 flex items-center justify-center rounded-xl bg-blue-50 text-white shadow-sm dark:bg-blue-700 ">
                    >
                        <Link :href="route('dashboard')" class="text-center">
                            <template v-if="store.theme === 'light' || store.theme === 'system'">
                                <img v-if="$page.props.company.isotipo == '/img/isotipo.png'" class="w-8 h-8" :src="xasset+$page.props.company.isotipo" alt="" />
                                <img v-else class="w-8 h-8" :src="xasset+'storage/'+$page.props.company.isotipo" alt="" />
                            </template>
                            <template v-if="store.theme === 'dark'">
                                <img v-if="$page.props.company.isotipo_negative == '/img/isotipo_negativo.png'" :src="`${xasset}/img/isotipo_negativo.png`" alt="Logo" class="w-8 h-8" />
                                <img v-else :src="`${xasset}storage/${$page.props.company.isotipo_negative}`" alt="Logo" class="w-8 h-8" />
                            </template>
                        </Link>
                    </div>

                    <div class="flex-1 overflow-hidden flex flex-col">
                        <div class="flex-1 overflow-y-auto pb-3 min-w-[70px] ">
                            <perfect-scrollbar
                                :options="{
                                    swipeEasing: true,
                                    wheelPropagation: false
                                }"
                                class="h-full"
                            >
                                <div class="w-full flex flex-col gap-3 items-center py-3">
                                    <template v-for="menu in menuData">
                                        <template v-if="menu.route == null">
                                            <Tooltip :color="colorTooltip" placement="right">
                                                <template #title>
                                                    <span class="uppercase" :class="fontTitleTooltip">{{ menu.text }}</span>
                                                </template>
                                                <button
                                                    v-can="menu.permissions"
                                                    @click="handleModuleClick(menu)"
                                                    class="group relative w-12 h-12 rounded-xl flex items-center justify-center hover:bg-blue-100 dark:hover:bg-blue-800/40 transition-all duration-200 hover:scale-105 hover:shadow-sm hover:shadow-blue-200/50 dark:hover:shadow-blue-900/50"
                                                    :class="activeModule === menu.text ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-md dark:bg-blue-700 dark:hover:bg-blue-800' : ''"
                                                >
                                                    <font-awesome-icon
                                                        :icon="menu.icom"
                                                        :class="activeModule === menu.text ? 'text-white' : 'ri-layout-grid-line text-xl text-slate-600 dark:text-slate-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors'"
                                                    />
                                                    <!-- Badge indicador activo -->
                                                    <div v-if="activeModule === menu.text" class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-green-400 border-2 border-white dark:border-gray-800"></div>
                                                </button>
                                            </Tooltip>
                                        </template>
                                         <template v-else-if="menu.route == 'module'">
                                            <Tooltip :color="colorTooltip" placement="right">
                                                <template #title>
                                                    <span class="uppercase" :class="fontTitleTooltip">{{ menu.text }}</span>
                                                </template>
                                                <button
                                                    v-can="menu.permissions"
                                                    @click="handleModuleClick(menu)"
                                                    class="group relative w-12 h-12 rounded-xl flex items-center justify-center hover:bg-blue-100 dark:hover:bg-blue-800/40 transition-all duration-200 hover:scale-105 hover:shadow-sm hover:shadow-blue-200/50 dark:hover:shadow-blue-900/50"
                                                    :class="activeModule === menu.text ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-md dark:bg-blue-700 dark:hover:bg-blue-800' : ''"
                                                >
                                                    <font-awesome-icon
                                                        :icon="menu.icom"
                                                        :class="activeModule === menu.text ? 'text-white' : 'ri-layout-grid-line text-xl text-slate-600 dark:text-slate-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors'"
                                                    />
                                                    <!-- Badge indicador activo -->
                                                    <div v-if="activeModule === menu.text" class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-green-400 border-2 border-white dark:border-gray-800"></div>
                                                </button>
                                            </Tooltip>
                                        </template>
                                        <template v-else>
                                            <Tooltip :color="colorTooltip" placement="right">
                                                <template #title>
                                                    <span class="uppercase" :class="fontTitleTooltip">{{ menu.text }}</span>
                                                </template>
                                                <Link
                                                    v-can="menu.permissions"
                                                    :href="menu.route"
                                                    @click="handleModuleClick(menu)"
                                                    class="group relative w-12 h-12 rounded-xl flex items-center justify-center hover:bg-blue-100 dark:hover:bg-blue-800/40 transition-all duration-200 hover:scale-105 hover:shadow-sm hover:shadow-blue-200/50 dark:hover:shadow-blue-900/50"
                                                    :class="activeModule === menu.text ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-md dark:bg-blue-700 dark:hover:bg-blue-800' : ''"
                                                >
                                                    <font-awesome-icon
                                                        :icon="menu.icom"
                                                        :class="activeModule === menu.text ? 'text-white' : 'ri-layout-grid-line text-xl text-slate-600 dark:text-slate-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors'"
                                                    />
                                                    <!-- Badge indicador activo -->
                                                    <div v-if="activeModule === menu.text" class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-green-400 border-2 border-white dark:border-gray-800"></div>
                                                </Link>
                                            </Tooltip>
                                        </template>
                                    </template>
                                </div>
                            </perfect-scrollbar>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 items-end pb-0">
                        <!-- Settings -->
                        <!-- <button
                            @click="handleModuleClick({text: 'settings', icon: 'ri-settings-3-line'})"
                            class="group relative w-12 h-12 rounded-xl flex items-center justify-center hover:bg-blue-100 dark:hover:bg-blue-800/40 transition-all duration-200 hover:scale-105 hover:shadow-sm hover:shadow-blue-200/50 dark:hover:shadow-blue-900/50"
                            :class="activeModule === 'settings' ? 'bg-blue-500 text-white hover:bg-blue-600 shadow-md' : ''"
                        >
                            <i :class="activeModule === 'settings' ? 'text-white' : 'ri-settings-3-line text-xl text-slate-600 dark:text-slate-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors'"></i>
                            <div v-if="activeModule === 'settings'" class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-green-400 border-2 border-white"></div>
                        </button> -->

                        <!-- Avatar alineado con el footer -->
                        <Link
                            :href="route('profile.edit')"
                            class="group relative w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-200 hover:scale-105 hover:shadow-blue-200/50 dark:hover:shadow-blue-800/30"
                            :class="activeModule === 'profile' ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-md dark:bg-blue-700 dark:hover:bg-blue-800' : ''"
                        >
                            <div class="h-10 w-10 rounded-full ring-2 ring-blue-200/50 dark:ring-blue-700/50 overflow-hidden bg-blue-100 dark:bg-blue-800 p-0.5">
                                <img v-if="$page.props.auth.user.avatar" class="rounded-full" :src="getImage($page.props.auth.user.avatar)" alt="" />
                                <img v-else :src="`https://ui-avatars.com/api/?name=${$page.props.auth.user.name}&size=150&rounded=true`" class="rounded-full" :alt="$page.props.auth.user.name"/>
                            </div>
                            <!-- Badge indicador activo -->
                            <div v-if="activeModule === 'profile'" class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-green-400 border-2 border-white"></div>
                        </Link>
                    </div>
                </div>
                <!-- END BOTONES MODULOS -->
                <!--Opciones de módulos -->
                <div
                    class="flex-1 pt-6 overflow-hidden relative min-w-[195px] flex flex-col"

                >
                    <h2 class="mb-6 ml-4 text-xl font-bold text-slate-800 dark:text-slate-200">
                        {{ moduleSelected.text || 'Dashboard' }}
                    </h2>

                    <div class="mt-2 ml-2 flex-1 overflow-hidden">
                        <perfect-scrollbar
                            :options="{
                                swipeEasing: true,
                                wheelPropagation: false,
                                suppressScrollX: true
                            }"
                            class="h-full"
                        >
                        <!-- Opciones dinámicas del módulo activo -->
                        <div class="mb-1">
                            <template v-for="(option, index) in (moduleSelected.items || [])" :key="index">
                                <template v-if="option.items && option.items.length> 0">
                                    <button
                                        @click="handleOptionClick(option.text)"
                                        class="w-full text-left py-2 px-2 rounded-lg transition-all duration-200 rounded-lg rounded-r-none hover:bg-orange-100 dark:hover:bg-orange-800/40"
                                        :class="{
                                            'bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-100 shadow-md shadow-blue-200/50 dark:shadow-blue-900/40': activeOption === option.text,
                                            'bg-orange-100 dark:bg-orange-800 text-orange-800 dark:text-orange-100 shadow-md shadow-orange-200/50 dark:shadow-orange-900/40': expandedSections[option.text] && activeOption !== option.text
                                        }"
                                    >
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-6 h-6 rounded-lg bg-orange-100 dark:bg-orange-800 flex items-center justify-center flex-shrink-0">
                                                    <font-awesome-icon
                                                        v-if="option.icom"
                                                        :icon="option.icom"
                                                        class="ri-input-method-line text-orange-600 dark:text-orange-300 text-sm"
                                                    />
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300 leading-tight break-words">{{ option.text }}</span>
                                                </div>
                                            </div>
                                            <i v-if="option.items && option.items.length > 0"
                                               class="ri-arrow-down-s-line text-slate-500 dark:text-slate-400 text-sm transition-transform flex-shrink-0 mt-1"
                                               :class="{ 'rotate-180': activeOption === option.text }"
                                            ></i>
                                        </div>
                                    </button>
                                </template>
                                <template v-else>
                                    <Link
                                        :href="option.route"
                                        @click="handleOptionClick(option.text)"
                                        class="w-full text-left py-2 px-2 rounded-lg transition-all duration-200 rounded-lg rounded-r-none block hover:bg-orange-100"
                                        :class="{
                                            'bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-100 shadow-md shadow-blue-200/50 dark:shadow-blue-900/40': activeOption === option.text,
                                            'bg-orange-100 dark:bg-orange-800 text-orange-800 dark:text-orange-100 shadow-md shadow-orange-200/50 dark:shadow-orange-900/40': expandedSections[option.text] && activeOption !== option.text
                                        }"
                                    >
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-6 h-6 rounded-lg bg-orange-100 dark:bg-orange-800 flex items-center justify-center flex-shrink-0">

                                                    <font-awesome-icon
                                                        v-if="option.icom"
                                                        :icon="option.icom"
                                                        class="ri-input-method-line text-orange-600 dark:text-orange-300 text-sm"
                                                    />
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300 leading-tight break-words">{{ option.text }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                </template>
                                <!-- Submenú desplegable si tiene subopciones -->
                                <VueCollapsible v-if="option.items && option.items.length> 0" :isOpen="expandedSections == option.text">
                                    <div class="mt-1 ml-4 dark:border-slate-600 space-y-1">
                                        <template v-for="(subOption, subIndex) in option.items" :key="subIndex">
                                        <Link :href="subOption.route"
                                                @click="handleSubOptionClick(option.text, subOption.text)"
                                                class="py-2 px-3 text-sm text-slate-600 dark:text-slate-300 hover:bg-orange-50 dark:hover:bg-orange-800/40 rounded-lg rounded-r-none transition-all duration-200 cursor-pointer block"
                                                :class="{
                                                    'bg-blue-50 dark:bg-blue-800 text-blue-700 dark:text-blue-100 shadow-sm shadow-blue-100/50 dark:shadow-blue-900/40': activeSubOption === subOption.text
                                                }">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-2 h-2 rounded-full bg-slate-400 dark:bg-slate-600 flex-shrink-0 "></div>
                                                <span class="text-slate-700 dark:text-slate-200 leading-tight break-words">{{ subOption.text }}</span>
                                            </div>
                                        </Link>
                                        </template>
                                    </div>
                                </VueCollapsible>

                            </template>
                        </div>
                        <!-- Opciones estáticas si no hay módulo activo -->
                        <template v-if="!moduleSelected.items || moduleSelected.items.length === 0">
                            <div class="text-center text-slate-500 dark:text-slate-400 py-8">
                                <img :src="'/img/svg/site-stats-rafiki.svg'" class="max-w-[154px]" />
                            </div>
                        </template>

                        </perfect-scrollbar>
                    </div>

                    <div class="border-t max-w-[90%] border-slate-200/50 dark:border-slate-700/50 pt-4 pl-4 mb-4">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <p class="text-xs font-bold text-slate-700 dark:text-slate-300 leading-none">{{ $page.props.auth.user.name }}</p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 italic">{{ $page.props.auth.user.email }}</p>
                            </div>
                        </div>
                    </div>

                </div>
                <!--End Opciones de módulos -->
            </div>
        </nav>
    </div>
</template>

<style scoped>
@import url('https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css');

.sidebar {
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.4);
    border-radius: 10px;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(71, 85, 105, 0.5);
}

</style>
