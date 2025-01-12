<script setup>
    import { Link } from '@inertiajs/vue3';
    
    defineProps({
        subscriptions: {
            type: Object,
            default: () => ({})
        }
    });
</script>

<template>
    <!-- Pricing -->
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        <!-- Title -->
        <div class="max-w-2xl mx-auto text-center mb-10 lg:mb-14">
            <h2 class="text-2xl font-bold md:text-4xl md:leading-tight dark:text-white">Suscripciones</h2>
            <p class="mt-1 text-gray-600 dark:text-neutral-400">Sea cual sea su estatus, nuestras ofertas evolucionan según sus necesidades.</p>
        </div>
        <!-- End Title -->

        <!-- Grid -->
        <div class="flex flex-wrap justify-center gap-6">
            <!-- Card --> 
            <div v-for="(subscription, index) in subscriptions" class="flex flex-col border border-gray-200 text-center rounded-xl p-8 dark:border-neutral-800 w-[300px]">
                <h4 class="font-medium text-lg text-gray-800 dark:text-neutral-200">{{ subscription.period }}</h4>
                <span class="mt-7 font-bold text-5xl text-gray-800 dark:text-neutral-200">{{ subscription.title }}</span>
                <p class="mt-2 text-sm text-gray-500 dark:text-neutral-500">{{ subscription.description }}</p>
                <ul class="mt-7 space-y-2.5 text-sm">
                    <li v-for="(item, key) in JSON.parse(subscription.details)" class="flex gap-x-2">
                        <svg class="shrink-0 mt-0.5 size-4 text-blue-600 dark:text-blue-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span class="text-gray-800 dark:text-neutral-400">{{ item.label }}</span>
                    </li>
                </ul>
                <Link :href="route('academic_step_account', subscription.id)" class="mt-5 py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none dark:bg-transparent dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">Acceder</Link>
            </div>
            <!-- End Card -->
        </div>
        <!-- End Grid -->
    </div>

    <!-- End Pricing -->
</template>