<script setup>
    import { usePage } from '@inertiajs/vue3';
    import { useAppStore } from '@/stores/index';

    const store = useAppStore();
    const xasset = assetUrl;
    const company = usePage().props.company;
    const userData = usePage().props.auth.user;

    const getImage = (path) => {
        return xasset + 'storage/'+ path;
    }

    const props = defineProps({
        content: {
            type: Object,
            default: () => ({})
        }
    })
</script>
<template>
    <nav class="bg-white border-gray-200 dark:bg-gray-900">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <div class="flex items-center space-x-3 rtl:space-x-reverse">
                <template v-if="store.theme === 'light' || store.theme === 'system'">
                    <img v-if="company.isotipo == '/img/isotipo.png'" class="h-8" :src="xasset+company.isotipo" alt="" />
                    <img v-else class="h-8" :src="xasset+'storage/'+company.isotipo" alt="" />
                </template>
                <template v-if="store.theme === 'dark'">
                    <img v-if="company.isotipo_negative == '/img/isotipo_negativo.png'" :src="`${xasset}/img/isotipo_negativo.png`" alt="Logo" class="h-8" />
                    <img v-else :src="`${xasset}storage/${company.isotipo_negative}`" alt="Logo" class="h-8" />
                </template>
                <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">{{ company.name }}</span>
            </div>
            <div class="flex items-center md:order-2 space-x-3 md:space-x-0 rtl:space-x-reverse">
                <button type="button" class="flex text-sm bg-gray-800 rounded-full md:me-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom">
                    <span class="sr-only">Open user {{ userData.name }}</span>
                    <img v-if="userData.avatar"
                        class="w-8 h-8 rounded-full"
                        :src="getImage(userData.avatar)"
                        :alt="userData.name"
                    />
                    <img v-else :src="`https://ui-avatars.com/api/?name=${userData.name}&size=150&rounded=true`" class="w-8 h-8 rounded-full" :alt="userData.name"/>
                </button>
            </div>
            <div class="items-center justify-between hidden w-full md:flex md:w-auto md:order-1" id="navbar-user">
                <div class="relative hidden md:block">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                        </svg>
                        <span class="sr-only">Buscar</span>
                    </div>
                    <input type="text" id="search-navbar" class="block w-full p-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Buscar...">
                </div>
            </div>
        </div>
    </nav>

    <div class="p-9">
        <div class="panel">
            <h4 class="mb-4 text-4xl text-center font-extrabold leading-none tracking-tight text-gray-900 md:text-5xl lg:text-6xl dark:text-white">{{ content.exam.description }}</h4>
            <ol class="w-full space-y-4 text-gray-500 list-decimal list-inside dark:text-gray-400">
                <template v-for="(question, key) in content.exam.questions">
                    <li >
                        <span class="font-semibold text-gray-900 dark:text-white">{{ question.description }}</span>
                        <ul class="ps-5 mt-2 space-y-1 list-none list-inside">
                        <template v-for="(answer, index) in question.answers">
                            <template v-if="question.type_answers == 'Escribir'">
                                 <li>
                                    {{ answer.description }}
                                    <div>
                                        <textarea id="ctnTextarea" rows="4" class="form-textarea" placeholder="Enter Textarea" required></textarea>
                                    </div>
                                </li>
                            </template>
                            <template v-if="question.type_answers == 'Alternativas'">
                                 <li>
                                    <label class="inline-flex">
                                        <input type="radio" :name="'rbtcolor'+ key" class="form-radio peer" />
                                        <span class="peer-checked:text-primary">{{ answer.description }}</span>
                                    </label>
                                </li>
                            </template>
                            <template v-if="question.type_answers == 'Varias respuestas'">
                                 <li>
                                    <label class="inline-flex">
                                        <input type="checkbox" class="form-checkbox" />
                                        <span>{{ answer.description }}</span>
                                    </label>
                                </li>
                            </template>
                            <template v-if="question.type_answers == 'Subir Archivo'">
                                 <li>
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" :for="'file_input'+key">{{ answer.description }}</label>
                                    <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" :id="'file_input'+key" type="file">
                                </li>
                            </template>
                        </template>
                        </ul>
                    </li>
                </template>
            </ol>
        </div>
    </div>
</template>
