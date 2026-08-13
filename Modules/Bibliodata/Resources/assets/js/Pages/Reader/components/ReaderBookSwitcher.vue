<script setup>
import { ref } from 'vue';

const props = defineProps({
    openedBooks: { type: Array, default: () => [] },
    currentBookId: { type: Number, default: null },
});

const emit = defineEmits(['open', 'shelf']);

const popOpen = ref(false);

const toggle = () => {
    popOpen.value = !popOpen.value;
};

const openBook = (id) => {
    popOpen.value = false;
    emit('open', id);
};

const goShelf = () => {
    popOpen.value = false;
    emit('shelf');
};
</script>

<template>
    <div class="bib-reader-switcher">
        <Transition name="bib-reader-switcher-pop">
            <div v-if="popOpen" class="bib-reader-switcher__pop" role="dialog" aria-label="Cambiar de libro">
                <div class="bib-reader-switcher__pop-header">
                    <p class="bib-reader-switcher__pop-title">Mis libros</p>
                </div>

                <div class="bib-reader-switcher__pop-list">
                    <button
                        v-for="item in openedBooks"
                        :key="item.id"
                        type="button"
                        class="bib-reader-switcher__item"
                        :class="{ 'is-current': Number(item.id) === Number(currentBookId) }"
                        @click="openBook(item.id)"
                    >
                        <span class="bib-reader-switcher__thumb">
                            <img
                                v-if="item.coverUrl"
                                :src="item.coverUrl"
                                :alt="item.title"
                                class="h-full w-full object-cover"
                            />
                            <span v-else class="bib-reader-switcher__thumb-fallback">
                                {{ (item.title || '?').charAt(0) }}
                            </span>
                        </span>
                        <span class="bib-reader-switcher__item-title">{{ item.title }}</span>
                    </button>
                </div>

                <button type="button" class="bib-reader-switcher__all" @click="goShelf">
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                        aria-hidden="true"
                    >
                        <path d="M4 5H20M4 12H20M4 19H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                    Ver todos los libros
                </button>
            </div>
        </Transition>

        <button
            type="button"
            class="bib-reader-switcher__fab"
            :class="{ 'is-active': popOpen }"
            :aria-expanded="popOpen"
            aria-label="Cambiar de libro"
            @click="toggle"
        >
            <svg
                class="bib-reader-switcher__fab-icon"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true"
            >
                <path d="M4 6C4 4.89543 4.89543 4 6 4H10C11.1046 4 12 4.89543 12 6V20C12 20 11 18 8 18C5 18 4 20 4 20V6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                <path d="M20 6C20 4.89543 19.1046 4 18 4H14C12.8954 4 12 4.89543 12 6V20C12 20 13 18 16 18C19 18 20 20 20 20V6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
            </svg>
        </button>
    </div>
</template>

<style scoped>
.bib-reader-switcher {
    position: fixed;
    right: 1.25rem;
    bottom: 5.25rem;
    z-index: 41;
}

.bib-reader-switcher__fab {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    border-radius: 9999px;
    border: 1px solid rgba(34, 211, 238, 0.35);
    background: linear-gradient(135deg, #0891b2, #06b6d4);
    color: #fff;
    box-shadow: 0 6px 18px rgba(8, 145, 178, 0.45);
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
}

.bib-reader-switcher__fab-icon {
    width: 1.35rem;
    height: 1.35rem;
}

.bib-reader-switcher__fab:hover {
    transform: scale(1.04);
    box-shadow: 0 8px 24px rgba(8, 145, 178, 0.6);
}

.bib-reader-switcher__fab.is-active {
    background: #0e7490;
}

.bib-reader-switcher__pop {
    position: absolute;
    right: 0;
    bottom: calc(100% + 0.75rem);
    width: min(19rem, calc(100vw - 2.5rem));
    padding: 0.75rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.98);
    border: 1px solid rgb(226 232 240);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
    backdrop-filter: blur(12px);
}

.dark .bib-reader-switcher__pop {
    background: rgba(15, 23, 42, 0.98);
    border-color: rgb(51 65 85);
}

.bib-reader-switcher__pop-header {
    padding: 0.15rem 0.25rem 0.5rem;
}

.bib-reader-switcher__pop-title {
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    color: rgb(71 85 105);
}

.dark .bib-reader-switcher__pop-title {
    color: rgb(203 213 225);
}

.bib-reader-switcher__pop-list {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    max-height: 14rem;
    overflow-y: auto;
    margin-bottom: 0.6rem;
}

.bib-reader-switcher__item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    width: 100%;
    padding: 0.35rem 0.4rem;
    border-radius: 0.6rem;
    border: none;
    background: transparent;
    text-align: left;
    cursor: pointer;
    transition: background 0.18s ease;
}

.bib-reader-switcher__item:hover {
    background: rgb(241 245 249);
}

.dark .bib-reader-switcher__item:hover {
    background: rgb(30 41 59);
}

.bib-reader-switcher__item.is-current {
    box-shadow: inset 0 0 0 1.5px rgba(34, 211, 238, 0.55);
}

.bib-reader-switcher__thumb {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.1rem;
    height: 2.9rem;
    overflow: hidden;
    border-radius: 0.35rem;
    background: rgb(226 232 240);
}

.bib-reader-switcher__thumb-fallback {
    font-size: 0.95rem;
    font-weight: 700;
    color: rgb(100 116 139);
}

.bib-reader-switcher__item-title {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.85rem;
    font-weight: 500;
    color: rgb(30 41 59);
}

.dark .bib-reader-switcher__item-title {
    color: rgb(226 232 240);
}

.bib-reader-switcher__all {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    width: 100%;
    padding: 0.55rem;
    border-radius: 0.65rem;
    border: 1px solid rgb(34 211 238 / 0.4);
    background: rgb(236 254 255);
    color: rgb(14 116 144);
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.18s ease;
}

.dark .bib-reader-switcher__all {
    background: rgb(8 51 68);
    color: rgb(103 232 249);
}

.bib-reader-switcher__all:hover {
    background: rgb(207 250 254);
}

.dark .bib-reader-switcher__all:hover {
    background: rgb(14 116 144);
}

.bib-reader-switcher-pop-enter-active,
.bib-reader-switcher-pop-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.bib-reader-switcher-pop-enter-from,
.bib-reader-switcher-pop-leave-to {
    opacity: 0;
    transform: translateY(8px);
}

@media (max-width: 767px) {
    .bib-reader-switcher {
        right: 1rem;
        bottom: 5rem;
    }
}
</style>
