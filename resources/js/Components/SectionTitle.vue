<script setup>
import { computed, useSlots } from 'vue';

const hasAside = computed(() => !!useSlots().aside);
</script>

<template>
    <div
        class="form-section-header flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between sm:gap-6"
    >
        <div class="min-w-0 flex-1 space-y-1.5 ltr:sm:pr-4 rtl:sm:pl-4">
            <h4
                class="text-lg font-semibold tracking-tight text-gray-900 dark:text-white-light sm:text-xl lg:text-[1.35rem] lg:leading-tight"
            >
                <slot name="title" />
            </h4>
            <p
                class="text-sm leading-relaxed text-gray-500 dark:text-gray-400 lg:text-[0.9375rem] lg:max-w-3xl 2xl:max-w-4xl"
            >
                <slot name="description" />
            </p>
        </div>
        <div
            v-if="hasAside"
            class="flex w-full shrink-0 flex-col gap-3 sm:w-auto sm:flex-row sm:items-center sm:justify-end"
        >
            <slot name="aside" />
        </div>
    </div>
</template>
