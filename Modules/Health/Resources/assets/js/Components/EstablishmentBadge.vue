<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const settings = computed(() => page.props.healthSettings || {});

const logoUrl = computed(() => {
    if (!settings.value?.establishment_logo) return null;
    const logo = settings.value.establishment_logo;
    if (logo.startsWith('http')) return logo;
    return `/storage/${logo}`;
});

const name = computed(() => settings.value?.establishment_name || '');
</script>

<template>
    <div v-if="name || logoUrl" class="flex items-center gap-2.5">
        <img
            v-if="logoUrl"
            :src="logoUrl"
            :alt="name"
            class="h-8 w-8 flex-shrink-0 rounded-full object-contain ring-2 ring-primary/20"
        />
        <div v-else class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary ring-2 ring-primary/20">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
        <span class="text-sm font-semibold text-gray-700 dark:text-white-dark">{{ name }}</span>
    </div>
</template>
