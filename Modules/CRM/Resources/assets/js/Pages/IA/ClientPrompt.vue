<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { usePage } from '@inertiajs/vue3';
    import { onMounted, ref } from 'vue';

    const company = usePage().props.company;
    const user = usePage().props.auth.user;
    const dataMessages = ref([]);
    const baseUrl = assetUrl;

    const getImage = (path) => {
        return baseUrl + 'storage/'+ path;
    }

    const props = defineProps({
        chats: {
            type: Object,
            default: () => ({})
        },
        messages: {
            type: Object,
            default: () => ({})
        }
    });

    onMounted(() => {
        dataMessages.value.items = props.messages
    });

    const messageText = ref(null);
    const addMessageChat = () => {
        dataMessages.value.push({
            userId: user.id,
            text: messageText.value,
            time: 'En este momento'
        });
        window.scrollTo(0, document.body.scrollHeight);
    };
</script>
<template>
    <AppLayout title="Contactos">
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Mensaje de solicitud de inteligencia artificial</span>
            </li>
        </Navigation>
        <div class="mt-5 panel">
            <!-- Content -->
            <div class="relative ">
                <div  class="py-10 lg:py-14">


                    <ul v-if="dataMessages.length > 0" class="mt-16 space-y-5">
                        <template v-for="(message, key) in dataMessages">
                            <li v-if="message.id = 1">
                                <div class="w-full sm:w-4/5 ml-auto">
                                    <div class="flex items-start gap-3 justify-end">
                                        <div class="space-y-2">
                                        <div class="flex items-center gap-3">
                                            <div class="dark:bg-gray-800 p-4 py-2 rounded-md bg-black/10 ltr:rounded-br-none rtl:rounded-bl-none">
                                            {{ message.text }}
                                            </div>
                                        </div>
                                        <div class="text-xs text-white-dark ltr:text-right rtl:text-left">{{ message.time }}</div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </template>

                    </ul>
                    <!-- Title -->
                    <div v-else class="max-w-4xl px-4 sm:px-6 lg:px-8 mx-auto text-center">
                        <a class="inline-block mb-4 flex-none focus:outline-hidden focus:opacity-80" href="../examples/html/ai-with-sidebar.html" aria-label="Preline">
                            <img class="w-28 h-auto mx-auto" :src="getImage(company.isotipo)" />
                        </a>

                        <h1 class="text-3xl font-bold text-gray-800 sm:text-4xl dark:text-white">
                            Bienvenido a {{ company.name }} AI
                        </h1>
                        <p class="mt-3 text-gray-600 dark:text-neutral-400">
                            Tu docente impulsado por IA para la web
                        </p>
                    </div>
                    <!-- End Title -->
                </div>

                <div class="sticky bottom-0 z-10 bg-white border-t border-gray-200 pt-2 pb-3 sm:pt-4 sm:pb-6 dark:bg-neutral-900 dark:border-neutral-700">
                    <!-- Textarea -->
                    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-0">
                        <div class="flex justify-between items-center mb-3">
                            <button type="button" class="inline-flex justify-center items-center gap-x-2 rounded-lg font-medium text-gray-800 hover:text-blue-600 focus:outline-hidden focus:text-blue-600 text-xs sm:text-sm dark:text-neutral-200 dark:hover:text-blue-500 dark:focus:text-blue-500">
                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            New chat
                            </button>

                            <button type="button" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-xs font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                            <svg class="size-3" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M5 3.5h6A1.5 1.5 0 0 1 12.5 5v6a1.5 1.5 0 0 1-1.5 1.5H5A1.5 1.5 0 0 1 3.5 11V5A1.5 1.5 0 0 1 5 3.5z"/>
                            </svg>
                            Stop generating
                            </button>
                        </div>

                    <!-- Input -->
                    <div class="relative">
                        <textarea v-model="messageText" class="form-textarea p-3 sm:p-4 pb-12 sm:pb-12 block w-full" placeholder="Pregúntame cualquier cosa..."></textarea>

                        <!-- Toolbar -->
                        <div class="absolute bottom-px inset-x-px p-2 rounded-b-lg bg-white dark:bg-neutral-900">
                            <div class="flex flex-wrap justify-between items-center gap-2">
                                <!-- Button Group -->
                                <div class="flex items-center">
                                <!-- Mic Button -->
                                <button type="button" class="inline-flex shrink-0 justify-center items-center size-8 rounded-lg text-gray-500 hover:bg-gray-100 focus:z-10 focus:outline-hidden focus:bg-gray-100 dark:text-neutral-500 dark:hover:bg-neutral-700 dark:focus:bg-neutral-700">
                                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><line x1="9" x2="15" y1="15" y2="9"/></svg>
                                </button>
                                <!-- End Mic Button -->

                                <!-- Attach Button -->
                                <button type="button" class="inline-flex shrink-0 justify-center items-center size-8 rounded-lg text-gray-500 hover:bg-gray-100 focus:z-10 focus:outline-hidden focus:bg-gray-100 dark:text-neutral-500 dark:hover:bg-neutral-700 dark:focus:bg-neutral-700">
                                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                </button>
                                <!-- End Attach Button -->
                                </div>
                                <!-- End Button Group -->

                                <!-- Button Group -->
                                <div class="flex items-center gap-x-1">
                                <!-- Mic Button -->
                                <button type="button" class="inline-flex shrink-0 justify-center items-center size-8 rounded-lg text-gray-500 hover:bg-gray-100 focus:z-10 focus:outline-hidden focus:bg-gray-100 dark:text-neutral-500 dark:hover:bg-neutral-700 dark:focus:bg-neutral-700">
                                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/></svg>
                                </button>
                                <!-- End Mic Button -->

                                <!-- Send Button -->
                                <button @click="addMessageChat" type="button" class="inline-flex shrink-0 justify-center items-center size-8 rounded-lg text-white bg-blue-600 hover:bg-blue-500 focus:z-10 focus:outline-hidden focus:bg-blue-500">
                                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 0 0-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 0 0 .886-.083l6-15Zm-1.833 1.89L6.637 10.07l-.215-.338a.5.5 0 0 0-.154-.154l-.338-.215 7.494-7.494 1.178-.471-.47 1.178Z"/>
                                    </svg>
                                </button>
                                <!-- End Send Button -->
                                </div>
                                <!-- End Button Group -->
                            </div>
                            </div>
                            <!-- End Toolbar -->
                        </div>
                        <!-- End Input -->
                    </div>
                    <!-- End Textarea -->
                </div>
            </div>
            <!-- End Content -->
        </div>
    </AppLayout>
</template>

