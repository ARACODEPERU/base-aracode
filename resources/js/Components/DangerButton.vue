<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});
</script>
<template>
    <button
        :type="type"
        class="btn btn-danger text-xs uppercase"
    >
        <slot />
    </button>
</template>
