<script setup>
import { computed, useSlots } from 'vue';
import SectionTitle from './SectionTitle.vue';

defineEmits(['submitted']);

const hasActions = computed(() => !!useSlots().actions);
</script>

<template>
    <section class="form-section w-full 2xl:max-w-[1680px] 2xl:mx-auto">
        <SectionTitle class="mb-5 sm:mb-6 lg:mb-8">
            <template #title>
                <slot name="title" />
            </template>
            <template #description>
                <slot name="description" />
            </template>
            <template #aside>
                <slot name="aside" />
            </template>
        </SectionTitle>

        <div
            class="form-section__card panel overflow-hidden border-0 p-0 shadow-[0_1px_3px_rgba(15,23,42,0.06)] ring-1 ring-gray-200/70 dark:shadow-none dark:ring-gray-700/60"
        >
            <form class="form-section__form" @submit.prevent="$emit('submitted')">
                <div
                    class="form-section__body px-4 py-5 sm:px-6 sm:py-6 md:px-7 md:py-7 lg:px-8 lg:py-8 xl:px-10 xl:py-9"
                >
                    <!-- Misma rejilla que el sistema: col-span-6 = ancho completo en lg -->
                    <div
                        class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:gap-5 lg:grid-cols-6 lg:gap-6 xl:gap-7 2xl:gap-8"
                    >
                        <slot name="form" />
                    </div>
                </div>

                <div
                    v-if="hasActions"
                    class="form-section__actions flex flex-wrap items-center justify-end gap-2 border-t border-gray-200/80 bg-gray-50/95 px-4 py-3.5 sm:gap-3 sm:px-6 sm:py-4 lg:px-8 lg:py-4 dark:border-gray-700/70 dark:bg-[#0e1726]/80"
                >
                    <slot name="actions" />
                </div>
            </form>
        </div>
    </section>
</template>
