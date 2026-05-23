import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Mismo criterio que resources/js/Components/vristo/layout/Header.vue:
 * avatar en storage o ui-avatars.com generado desde el nombre.
 */
export function useAuthUserAvatar(size = 100, rounded = false) {
    const authUser = computed(() => usePage().props.auth?.user ?? null);

    const baseUrl = typeof assetUrl !== 'undefined' ? assetUrl : '/';

    const getImage = (path) => `${baseUrl}storage/${path}`;

    const uiAvatarsUrl = computed(() => {
        const name = authUser.value?.name ?? '';
        const roundedParam = rounded ? '&rounded=true' : '';

        return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&size=${size}${roundedParam}`;
    });

    return { authUser, getImage, uiAvatarsUrl };
}
