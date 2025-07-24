<script setup>
defineProps({
    value: {
        type: String,
    },
});
</script>

<template>
    <label class="dark:text-white-dark">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
