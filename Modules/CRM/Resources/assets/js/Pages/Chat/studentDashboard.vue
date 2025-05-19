<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { usePage } from '@inertiajs/vue3';
    import { onMounted, nextTick, ref, onUnmounted } from 'vue';
    import { useAppStore } from '@/stores/index';
    const store = useAppStore();

    const company = usePage().props.company;
    const user = usePage().props.auth.user;
    const dataMessages = ref([]);
    const baseUrl = assetUrl;

    const getImage = (path) => {
        return baseUrl + 'storage/'+ path;
    }

    const props = defineProps({
        participants: {
            type: Object,
            default: () => ({})
        },
        messages: {
            type: Object,
            default: () => ({})
        },
        conversationId: {
            type: [Number, String],
            default: 0
        }
    });

    const appCodeUnique = import.meta.env.VITE_APP_CODE ?? 'ARACODE';
    const channelListenChat = "message-notification-" + appCodeUnique;

    onMounted(() => {
        dataMessages.value = props.messages
        nextTick(() => {
            window.scrollTo(0, document.body.scrollHeight);
        });

        window.socketIo.on(channelListenChat, (result) => {
            let participants = result.data.participants;
            let tempConversationId = result.data.message.conversation_id;

            const newmsg = result.data.message;

            participants.forEach(item => {
                if(user.id == item){
                    if(tempConversationId == props.conversationId){
                        dataMessages.value.push(newmsg);
                        window.scrollTo(0, document.body.scrollHeight);
                    }
                }
            });
        });
    });

    onUnmounted(() => {
        window.socketIo.off(channelListenChat); // Dejar el canal cuando se desmonte el componente
    });

    const messageText = ref(null);


    const xasset = assetUrl;
    const isShowLoadingSend = ref(false);

    const sendMessageChatBox = () => {
        if (messageText.value.trim()) {
            isShowLoadingSend.value = true;
            const msg = {
                conversationId: props.conversationId,
                text: messageText.value,
                type: 'text',
            };
            axios.post(route('crm_application_ai_prompt_send_message'),msg).then((response) => {
                return response.data;
            }).then((res) => {
                if(res.success){
                    dataMessages.value.push(res.message);
                    messageText.value = '';
                    window.scrollTo(0, document.body.scrollHeight);
                }else{
                    showMessage('No puede enviar mensajes en este momento. Por favor, complete su información personal en su perfil para habilitar esta función.','info');
                }
                isShowLoadingSend.value = false;
            });
        }
    };

    const showMessage = (msg = '', type = 'success') => {
        const toast = Swal.mixin({
            toast: true,
            position: 'top',
            showConfirmButton: false,
            timer: 3000,
            customClass: { container: 'toast' },
        });
        toast.fire({
            icon: type,
            title: msg,
            padding: '10px 20px',
        });
    };
</script>
<template>
    <AppLayout title="Contactos">
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Asesorias</span>
            </li>
        </Navigation>
        <div class="mt-5 panel">
            <!-- Content -->
            <div class="relative ">
                <div  class="py-10 lg:py-14">


                    <ul v-if="dataMessages.length > 0" class="mt-16 space-y-5">
                        <template v-for="(message, key) in dataMessages">
                            <li v-if="user.person_id == message.person_id" >
                                <div class="w-full sm:w-4/5 ml-auto">
                                    <div class="flex items-start gap-3 justify-end">
                                        <div class="space-y-2">
                                        <div class="flex items-center gap-3">
                                            <div v-html="message.content" class="dark:bg-gray-800 p-4 py-2 rounded-md bg-black/10 ltr:rounded-br-none rtl:rounded-bl-none">
                                            </div>
                                        </div>
                                        <div class="text-xs text-white-dark ltr:text-right rtl:text-left">{{ message.time }}</div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li v-else >
                                <div class="max-w-2xl flex gap-x-2 sm:gap-x-2">
                                    <div class="ltr:mr-4 rtl:ml-4">
                                        <div class="bg-gray-100 items-center rounded-full px-2 py-2 dark:bg-gray-700">
                                            <template v-if="store.theme === 'light' || store.theme === 'system'">
                                                <img v-if="company.isotipo == '/img/isotipo.png'" class="w-6" :src="xasset+$page.props.company.isotipo" alt="" />
                                                <img v-else class="w-6" :src="xasset+'storage/'+company.isotipo" alt="" />
                                            </template>
                                            <template v-if="store.theme === 'dark'">
                                                <img v-if="company.isotipo_negative == '/img/isotipo_negativo.png'" :src="`${xasset}/img/isotipo_negativo.png`" alt="Logo" class="w-6" />
                                                <img v-else :src="`${xasset}storage/${company.isotipo_negative}`" alt="Logo" class="w-6" />
                                            </template>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <p v-html="message.content" class="text-gray-800 dark:text-neutral-200"></p>
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
                            Bienvenido a {{ company.name }}
                        </h1>
                        <p class="mt-3 text-gray-600 dark:text-neutral-400">
                            Consulta con nuestros docentes expertos.
                        </p>
                    </div>
                    <!-- End Title -->
                </div>

                <div class="sticky bottom-0 z-10 bg-white border-t border-gray-200 pt-2 pb-3 sm:pt-4 sm:pb-6 dark:bg-neutral-900 dark:border-neutral-700">
                    <!-- Textarea -->
                    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-0">
                        <div class="flex justify-between items-center mb-3">
                            <!-- <button type="button" class="inline-flex justify-center items-center gap-x-2 rounded-lg font-medium text-gray-800 hover:text-blue-600 focus:outline-hidden focus:text-blue-600 text-xs sm:text-sm dark:text-neutral-200 dark:hover:text-blue-500 dark:focus:text-blue-500">
                                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                New chat
                                </button>

                            <button type="button" class="py-1.5 px-2 inline-flex items-center gap-x-2 text-xs font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                                <svg class="size-3" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M5 3.5h6A1.5 1.5 0 0 1 12.5 5v6a1.5 1.5 0 0 1-1.5 1.5H5A1.5 1.5 0 0 1 3.5 11V5A1.5 1.5 0 0 1 5 3.5z"/>
                                </svg>
                                Stop generating
                            </button> -->
                        </div>

                        <!-- Input -->
                        <div class="relative">
                            <textarea v-model="messageText" @keyup.enter.exact="sendMessageChatBox()" class="form-textarea p-3 sm:p-4 pb-12 sm:pb-12 block w-full" placeholder="Pregúntame cualquier cosa..."></textarea>

                            <!-- Toolbar -->
                            <div class="absolute bottom-px inset-x-px p-2 rounded-b-lg bg-white dark:bg-neutral-900">
                                <div class="flex flex-wrap justify-between items-center gap-2">
                                    <!-- Button Group -->
                                    <div class="flex items-center">
                                        <!-- <button type="button" class="inline-flex shrink-0 justify-center items-center size-8 rounded-lg text-gray-500 hover:bg-gray-100 focus:z-10 focus:outline-hidden focus:bg-gray-100 dark:text-neutral-500 dark:hover:bg-neutral-700 dark:focus:bg-neutral-700">
                                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><line x1="9" x2="15" y1="15" y2="9"/></svg>
                                        </button>

                                        <button type="button" class="inline-flex shrink-0 justify-center items-center size-8 rounded-lg text-gray-500 hover:bg-gray-100 focus:z-10 focus:outline-hidden focus:bg-gray-100 dark:text-neutral-500 dark:hover:bg-neutral-700 dark:focus:bg-neutral-700">
                                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                        </button> -->
                                    </div>
                                    <!-- End Button Group -->

                                    <!-- Button Group -->
                                    <div class="flex items-center gap-x-1">
                                        <!-- Mic Button -->
                                        <!-- <button type="button" class="inline-flex shrink-0 justify-center items-center size-8 rounded-lg text-gray-500 hover:bg-gray-100 focus:z-10 focus:outline-hidden focus:bg-gray-100 dark:text-neutral-500 dark:hover:bg-neutral-700 dark:focus:bg-neutral-700">
                                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/></svg>
                                        </button> -->
                                        <!-- End Mic Button -->

                                        <!-- Send Button -->
                                        <button @click="sendMessageChatBox" type="button" class="inline-flex shrink-0 justify-center items-center size-8 rounded-lg text-white bg-blue-600 hover:bg-blue-500 focus:z-10 focus:outline-hidden focus:bg-blue-500">
                                            <svg v-if="isShowLoadingSend" aria-hidden="true" role="status" class="shrink-0 size-3.5 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                                <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                            </svg>
                                            <svg v-else class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
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

