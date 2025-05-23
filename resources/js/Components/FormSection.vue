<script setup>
    import { computed, useSlots } from 'vue';
    import SectionTitle from './SectionTitle.vue';

    defineEmits(['submitted']);

    const hasActions = computed(() => !! useSlots().actions);
</script>

<template>
    <div class="">
        <SectionTitle>
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

        <div class="mt-5 panel p-0">
            <form @submit.prevent="$emit('submitted')">
                <div class="">
                    <div class="p-6">
                        <div class="grid grid-cols-6 gap-6 ">
                            <slot name="form" />
                        </div>
                    </div>
                    <div v-if="hasActions" class="flex items-center justify-end px-4 py-3 bg-gray-50 text-right sm:px-6 shadow sm:rounded-bl-md sm:rounded-br-md dark:bg-gray-800">
                        <slot name="actions" />
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
