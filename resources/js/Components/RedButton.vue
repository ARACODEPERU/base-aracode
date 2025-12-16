<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});
</script>
<template>
    <button
    :disabled="disabled"
    :style="disabled ? 'cursor: not-allowed': ''"
    type="button" class="btn btn-danger uppercase text-xs">
        <slot />
    </button>
</template>
