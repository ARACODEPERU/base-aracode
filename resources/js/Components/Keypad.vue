<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex flex-col items-center space-x-2 md:flex-row md:justify-end space-y-2 md:space-y-0">

        <Link :href="route('dashboard')" type="button" class="btn btn-info text-xs uppercase">
            Panel de Inicio
        </Link>
        <slot name="botones" />

    </div>
</template>
