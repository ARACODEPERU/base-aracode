<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import TotalSales from '../Components/TotalSales.vue';
</script>
<template>
    <AppLayout>
        <!-- Grid -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <!-- Card -->
            <TotalSales />
        </div>
    </AppLayout>
</template>
