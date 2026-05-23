<script setup>
import { useAuthUserAvatar } from '../composables/useAuthUserAvatar.js';

const props = defineProps({
    size: { type: Number, default: 100 },
    rounded: { type: Boolean, default: false },
    imgClass: { type: String, default: '' },
});

const { authUser, getImage, uiAvatarsUrl } = useAuthUserAvatar(props.size, props.rounded);
</script>

<template>
    <img
        v-if="authUser?.avatar"
        :src="getImage(authUser.avatar)"
        :alt="authUser.name"
        :class="imgClass"
    />
    <img v-else :src="uiAvatarsUrl" :alt="authUser?.name" :class="imgClass" />
</template>
