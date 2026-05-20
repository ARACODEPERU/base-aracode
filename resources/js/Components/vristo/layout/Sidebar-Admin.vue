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

    const hasPermission = (permission) => {
        const permissions = page.props.auth?.permissions || [];

        return !permission || permissions.includes(permission);
    };

    const findAllowedModule = (moduleText) => {
        return menuData.value.find((module) => module.text === moduleText && hasPermission(module.permissions));
    };

    // Función para manejar clicks en los botones de módulos
    const handleModuleClick = (module) => {
        activeModule.value = module.text; // Guardar el módulo activo
        moduleSelected.value = module || []; // Cargar las opciones del módulo

        // Guardar solo claves estables; el contenido del menu debe venir siempre de MenuData.
        localStorage.setItem('activeModule', module.text);
        localStorage.removeItem('moduleSelected');

        // Actualizar las secciones expandidas si es necesario
        if (module && module.items) {
            expandedSections.value = module.text;
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

        // Restaurar estado desde localStorage de forma segura
        const savedModule = localStorage.getItem('activeModule');


        if (savedModule) {
            const allowedModule = findAllowedModule(savedModule);

            if (allowedModule) {
                activeModule.value = allowedModule.text;
                moduleSelected.value = allowedModule;
            } else {
                activeModule.value = optionsDefault.text || 'Dashboard';
                moduleSelected.value = optionsDefault;
                localStorage.removeItem('activeModule');
            }
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

        if (!moduleSelected.value?.text) {
            moduleSelected.value = optionsDefault;
        }
        localStorage.removeItem('moduleSelected');

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
            //clearSidebarState();
        }
    }, { deep: true });


    const getImage = (path) => {
        return xasset + 'storage/'+ path;
    }

    const isDashboardOption = (option) => option?.dashboard === true;
    const isOptionActive = (option) => activeOption.value === option?.text;
    const isOptionExpanded = (option) => expandedSections.value === option?.text && !isOptionActive(option);

    const optionLinkClasses = (option) => {
        if (isDashboardOption(option)) {
            return [
                'border bg-gradient-to-br shadow-sm',
                isOptionActive(option)
                    ? 'border-sky-400 from-sky-600 via-cyan-600 to-blue-700 text-white shadow-lg shadow-sky-300/40 dark:border-sky-500 dark:shadow-sky-950/60'
                    : 'border-sky-200/80 from-white via-sky-50 to-cyan-50 text-sky-800 shadow-sky-100/80 hover:border-sky-300 hover:shadow-md hover:shadow-sky-100 dark:border-sky-900/60 dark:from-slate-800 dark:via-slate-800 dark:to-sky-950/60 dark:text-sky-100 dark:shadow-sky-950/30 dark:hover:border-sky-700',
            ];
        }

        return {
            'border-blue-200 bg-blue-50 text-blue-800 shadow-sm shadow-blue-100 dark:border-blue-800 dark:bg-blue-950/50 dark:text-blue-100 dark:shadow-blue-950/30': isOptionActive(option),
            'border-orange-200 bg-orange-50 text-orange-800 shadow-sm shadow-orange-100 dark:border-orange-800 dark:bg-orange-950/40 dark:text-orange-100 dark:shadow-orange-950/30': isOptionExpanded(option),
        };
    };

    const optionIconBoxClasses = (option) => {
        if (isDashboardOption(option)) {
            return isOptionActive(option)
                ? 'bg-white/20 text-white shadow-sm'
                : 'bg-sky-100 text-sky-600 shadow-sm shadow-sky-100/70 dark:bg-sky-900/60 dark:text-sky-300';
        }

        return isOptionActive(option)
            ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200'
            : 'bg-slate-100 text-slate-500 group-hover:bg-orange-100 group-hover:text-orange-600 dark:bg-slate-700 dark:text-slate-300 dark:group-hover:bg-orange-900/50 dark:group-hover:text-orange-300';
    };

    const optionIconClasses = (option) => {
        if (isDashboardOption(option)) {
            return isOptionActive(option)
                ? 'text-white'
                : 'text-sky-600 dark:text-sky-300';
        }

        return 'text-current';
    };

    const optionTextClasses = (option) => {
        if (isDashboardOption(option)) {
            return isOptionActive(option)
                ? 'text-white'
                : 'text-sky-800 dark:text-sky-100';
        }

        if (isOptionActive(option)) {
            return 'text-blue-800 dark:text-blue-100';
        }

        return 'text-slate-700 group-hover:text-slate-900 dark:text-slate-300 dark:group-hover:text-white';
    };

    const dashboardBadgeClasses = (option) => {
        return isOptionActive(option)
            ? 'bg-white/20 text-white'
            : 'bg-sky-100 text-sky-700 dark:bg-sky-900/70 dark:text-sky-200';
    };

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
                <div class="relative flex min-w-[195px] flex-1 flex-col overflow-hidden bg-slate-50/80 dark:bg-slate-900/30">
                    <div class="border-b border-slate-200/70 px-4 pb-4 pt-5 dark:border-slate-700/60">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Modulo activo</p>
                        <div class="mt-2 flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm shadow-blue-300/50 dark:bg-blue-700 dark:shadow-blue-950/50">
                                <font-awesome-icon v-if="moduleSelected.icom" :icon="moduleSelected.icom" class="text-sm" />
                            </div>
                            <div class="min-w-0">
                                <h2 class="truncate text-lg font-bold text-slate-800 dark:text-slate-100">
                                    {{ moduleSelected.text || 'Dashboard' }}
                                </h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Opciones disponibles</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-hidden px-3 py-4">
                        <perfect-scrollbar
                            :options="{
                                swipeEasing: true,
                                wheelPropagation: false,
                                suppressScrollX: true
                            }"
                            class="h-full"
                        >
                        <!-- Opciones dinámicas del módulo activo -->
                        <div class="space-y-2">
                            <template v-for="(option, index) in (moduleSelected.items || [])" :key="index">
                                <template v-if="option.items && option.items.length> 0">
                                    <button
                                        v-can="option.permissions"
                                        @click="handleOptionClick(option.text)"
                                        class="group w-full rounded-xl border border-transparent bg-white/70 px-3 py-2.5 text-left shadow-sm shadow-slate-100/80 transition-all duration-200 hover:border-orange-200 hover:bg-orange-50 hover:shadow-orange-100/70 dark:bg-slate-800/80 dark:shadow-slate-950/20 dark:hover:border-orange-900/60 dark:hover:bg-orange-950/30"
                                        :class="optionLinkClasses(option)"
                                    >
                                        <div class="flex items-center justify-between">
                                            <div class="flex min-w-0 items-center space-x-3">
                                                <div
                                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg transition-all duration-200"
                                                    :class="optionIconBoxClasses(option)"
                                                >
                                                    <font-awesome-icon
                                                        v-if="option.icom"
                                                        :icon="option.icom"
                                                        class="ri-input-method-line text-sm"
                                                        :class="optionIconClasses(option)"
                                                    />
                                                </div>
                                                <div class="flex min-w-0 flex-col">
                                                    <span class="text-sm font-semibold leading-tight break-words" :class="optionTextClasses(option)">{{ option.text }}</span>
                                                    <span class="mt-0.5 text-[11px] text-slate-400 dark:text-slate-500">{{ option.items.length }} opciones</span>
                                                </div>
                                            </div>
                                            <i v-if="option.items && option.items.length > 0"
                                               class="ri-arrow-down-s-line text-slate-400 dark:text-slate-500 text-sm transition-transform flex-shrink-0"
                                               :class="{ 'rotate-180': activeOption === option.text }"
                                            ></i>
                                        </div>
                                    </button>
                                </template>
                                <template v-else>
                                    <Link
                                        v-can="option.permissions"
                                        :href="option.route"
                                        @click="handleOptionClick(option.text)"
                                        class="group relative block w-full overflow-hidden rounded-xl border border-transparent bg-white/70 px-3 py-2.5 text-left shadow-sm shadow-slate-100/80 transition-all duration-200 hover:border-orange-200 hover:bg-orange-50 hover:shadow-orange-100/70 dark:bg-slate-800/80 dark:shadow-slate-950/20 dark:hover:border-orange-900/60 dark:hover:bg-orange-950/30"
                                        :class="optionLinkClasses(option)"
                                    >
                                        <div class="relative flex items-center justify-between gap-3">
                                            <div class="flex min-w-0 items-center space-x-3">
                                                <div
                                                    class="flex flex-shrink-0 items-center justify-center rounded-lg transition-all duration-200"
                                                    :class="isDashboardOption(option) ? 'h-10 w-10 ' + optionIconBoxClasses(option) : 'h-8 w-8 ' + optionIconBoxClasses(option)"
                                                >
                                                    <font-awesome-icon
                                                        v-if="option.icom"
                                                        :icon="option.icom"
                                                        class="ri-input-method-line text-sm"
                                                        :class="optionIconClasses(option)"
                                                    />
                                                </div>
                                                <div class="flex min-w-0 flex-col">
                                                    <span class="text-sm font-semibold leading-tight break-words" :class="optionTextClasses(option)">{{ option.text }}</span>
                                                    <span
                                                        v-if="isDashboardOption(option)"
                                                        class="mt-1 w-fit rounded px-2 py-0.5 text-[10px] font-bold uppercase leading-none"
                                                        :class="dashboardBadgeClasses(option)"
                                                    >
                                                        Resumen
                                                    </span>
                                                </div>
                                            </div>
                                            <div
                                                v-if="isDashboardOption(option)"
                                                class="hidden h-7 w-1.5 rounded-full sm:block"
                                                :class="isOptionActive(option) ? 'bg-white/60' : 'bg-sky-300 dark:bg-sky-700'"
                                            ></div>
                                        </div>
                                    </Link>
                                </template>
                                <!-- Submenú desplegable si tiene subopciones -->
                                <VueCollapsible v-if="option.items && option.items.length> 0" :isOpen="expandedSections == option.text">
                                    <div class="ml-4 mt-2 space-y-1 border-l border-slate-200 pl-3 dark:border-slate-700">
                                        <template v-for="(subOption, subIndex) in option.items" :key="subIndex">
                                        <Link v-can="subOption.permissions"
                                                :href="subOption.route"
                                                @click="handleSubOptionClick(option.text, subOption.text)"
                                                class="block cursor-pointer rounded-lg px-3 py-2 text-sm text-slate-600 transition-all duration-200 hover:bg-orange-50 hover:text-orange-700 dark:text-slate-300 dark:hover:bg-orange-950/30 dark:hover:text-orange-200"
                                                :class="{
                                                    'bg-blue-50 text-blue-700 shadow-sm shadow-blue-100/50 dark:bg-blue-950/50 dark:text-blue-100 dark:shadow-blue-950/40': activeSubOption === subOption.text
                                                }">
                                            <div class="flex items-center space-x-3">
                                                <div
                                                    class="h-2 w-2 flex-shrink-0 rounded-full"
                                                    :class="activeSubOption === subOption.text ? 'bg-blue-500 dark:bg-blue-300' : 'bg-slate-300 dark:bg-slate-600'"
                                                ></div>
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
                            <div class="rounded-xl border border-dashed border-slate-200 bg-white/70 py-8 text-center text-slate-500 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-400">
                                <img :src="'/img/svg/site-stats-rafiki.svg'" class="max-w-[154px]" />
                            </div>
                        </template>

                        </perfect-scrollbar>
                    </div>

                    <div class="border-t border-slate-200/70 bg-white/70 px-4 py-3 dark:border-slate-700/60 dark:bg-slate-800/70">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-bold text-slate-700 dark:text-slate-200">{{ $page.props.auth.user.name }}</p>
                            <p class="truncate text-[10px] text-slate-500 dark:text-slate-400">{{ $page.props.auth.user.email }}</p>
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
