<script setup>
    import { ref, onMounted } from 'vue';

    const totalSalesData = ref(null);
    const displayCardData = ref(false);

    const getTotalSalesOnline = () => {
        displayCardData.value = true;
        console.log('aca llega')
        axios({
            method: "post",
            url: route('onlineshop_dashboard_total_sales'),
        }).then((result) => {
            totalSalesData.value = result.data.sales
        }).finally(() => {
            displayCardData.value = false;
        });
    }

    onMounted(() => {
        console.log('aca uno')
        getTotalSalesOnline();
    });
</script>
<template>
    <div class="flex flex-col bg-white border border-gray-200 border-t-4 border-t-blue-600 shadow-2xs rounded-xl dark:bg-neutral-900 dark:border-neutral-700 dark:border-t-blue-500 dark:shadow-neutral-700/70">
        <div v-if="displayCardData" class="p-4 md:p-5">
            <div class="flex items-center gap-x-2">
                <div class="h-3 bg-gray-200 rounded-full dark:bg-gray-700 w-32"></div>
                <div class="hs-tooltip">
                    <div v-tippy="{content: 'Cantidad total de ventas en linea que se han realizado hasta la fecha', placement: 'bottom'}" class="hs-tooltip-toggle">
                        <svg class="shrink-0 size-4 text-gray-500 dark:text-neutral-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
                    </div>
                </div>
            </div>
            <div class="mt-1 flex items-center gap-x-2">
                <div class="h-3 bg-gray-200 rounded-full dark:bg-gray-700 w-32"></div>
                <span class="flex items-center gap-x-1 text-green-600">
                    <svg class="inline-block size-4 self-center" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                    <span class="inline-block text-sm">
                       <div class="h-2.5 ms-2 bg-gray-300 rounded-full dark:bg-gray-600 w-24"></div>
                    </span>
                </span>
            </div>
             <div class="h-2.5 bg-gray-200 rounded-full dark:bg-gray-700 w-full"></div>
        </div>
        <!-- <div v-else class="p-4 md:p-5">
            <div class="flex items-center gap-x-2">
                <p class="text-xs uppercase text-gray-500 dark:text-neutral-500">
                    Total ventas
                </p>
                <div class="hs-tooltip">
                    <div v-tippy="{content: 'Cantidad total de ventas en linea que se han realizado hasta la fecha', placement: 'bottom'}" class="hs-tooltip-toggle">
                        <svg class="shrink-0 size-4 text-gray-500 dark:text-neutral-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
                    </div>
                </div>
            </div>
            <div class="mt-1 flex items-center gap-x-2">
                <h3 class="text-xl sm:text-2xl font-medium text-gray-800 dark:text-neutral-200">
                    {{ totalSalesData.total_sales_count }}
                </h3>
                <span class="flex items-center gap-x-1 text-green-600">
                    <svg class="inline-block size-4 self-center" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                    <span class="inline-block text-sm">
                        {{ totalSalesData.this_year_sales_count }}
                    </span>
                </span>
            </div>
            <span class="mt-6 block text-xs text-gray-500 dark:text-neutral-500">
                {{ totalSalesData.summary_message }}
            </span>
        </div> -->
    </div>
</template>
