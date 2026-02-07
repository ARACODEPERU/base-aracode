<script setup>
import { Link } from '@inertiajs/vue3';
import iconHome from '../icon/icon-home.vue';
import { Tooltip, Dropdown, Menu, MenuItem } from 'ant-design-vue';

const props = defineProps({
    routeModule: { type: String, default: null },
    titleModule: { type: String, default: null },
    data: { type: Array, default: () => [] },
    maxChars: { type: Number, default: 80 }
});

const truncate = (text) => {
    if (!text) return '';
    return text.length > props.maxChars ? text.substring(0, props.maxChars) + '...' : text;
};

const colorTooltip = 'blue';
const fontTitleTooltip = 'text-xs text-white';
</script>

<template>
    <nav class="flex mb-5 overflow-x-auto no-scrollbar" aria-label="Breadcrumb">
        <ol class="inline-flex items-center p-1 space-x-1 bg-slate-200/50 dark:bg-slate-800/50 rounded-xl whitespace-nowrap shadow-sm border border-slate-200/50 dark:border-slate-700/50">
            <li class="inline-flex items-center">
                <Link :href="route('dashboard')"
                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-slate-600 rounded-lg hover:bg-white hover:text-blue-600 hover:shadow-sm dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white transition-all">
                    <iconHome class="w-4 h-4" />
                    <span class="hidden sm:inline ml-2 text-xs uppercase tracking-wider font-semibold">Inicio</span>
                </Link>
            </li>

            <li v-if="titleModule" class="flex items-center">
                <span class="text-slate-300 dark:text-slate-600 mx-1 select-none font-extralight text-lg">
                    /
                </span>
                <Link :href="routeModule"
                    class="px-3 py-1.5 text-sm font-medium text-slate-600 rounded-lg hover:bg-white hover:text-blue-600 hover:shadow-sm dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white transition-all">
                    {{ truncate(titleModule) }}
                </Link>
            </li>

            <template v-for="(item, index) in data" :key="index">
                <li class="flex items-center">
                    <span v-if="index !== data.length - 1" class="text-slate-300 dark:text-slate-600 mx-1 select-none font-extralight text-lg">
                        /
                    </span>
                    <div class="flex items-center">
                        <Tooltip :title="item.title.length > maxChars ? item.title : ''" :color="colorTooltip" placement="bottom">

                            <Link v-if="item.route && index !== data.length - 1"
                                :href="item.route"
                                class="px-3 py-1.5 text-sm font-medium text-slate-600 rounded-lg hover:bg-white hover:text-blue-600 hover:shadow-sm dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white transition-all">
                                {{ truncate(item.title) }}
                            </Link>
                            <template v-else>
                                <span v-if="data.length - 1 == index"
                                    class="px-4 py-1.5 text-sm font-semibold text-blue-700 bg-white/50 dark:bg-blue-900/30 dark:text-blue-300 rounded-lg border border-blue-200 dark:border-blue-800/50">
                                    {{ truncate(item.title || '') }}
                                    <slot v-if="index === data.length - 1 && !$slots.default" />
                                </span>
                                <span v-else
                                    class="px-3 py-1.5 text-sm font-medium text-blue-800 rounded-lg hover:bg-white hover:text-blue-600 hover:shadow-sm dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white transition-all">
                                    {{ truncate(item.title) }}
                                </span>
                            </template>

                        </Tooltip>

                        <Dropdown v-if="item.children && item.children.length > 0" trigger="click" placement="bottomLeft">
                            <button class="ml-1 p-1 text-slate-400 hover:text-blue-600 hover:bg-white dark:hover:bg-slate-700 rounded-md transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <template #overlay>
                                <Menu class="!rounded-xl !p-2 !shadow-2xl border dark:border-slate-700 dark:bg-slate-800">
                                    <MenuItem v-for="(child, cIdx) in item.children" :key="cIdx" class="!rounded-lg">
                                        <template v-if="child.permissions">
                                            <Link v-can="child.permissions" :href="child.route" class="flex items-center px-2 py-1 text-sm text-slate-700 dark:text-slate-300 hover:text-blue-600">
                                                <div class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-2"></div>
                                                {{ child.title }}
                                            </Link>
                                        </template>
                                        <template v-else>
                                            <Link :href="child.route" class="flex items-center px-2 py-1 text-sm text-slate-700 dark:text-slate-300 hover:text-blue-600">
                                                <div class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-2"></div>
                                                {{ child.title }}
                                            </Link>
                                        </template>
                                    </MenuItem>
                                </Menu>
                            </template>
                        </Dropdown>
                    </div>
                </li>
            </template>
        </ol>
    </nav>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Ajustes para Ant Design Dropdown para que no rompa tu estilo */
:deep(.ant-dropdown-menu) {
    min-width: 160px;
}
</style>
