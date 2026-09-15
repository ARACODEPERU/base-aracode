<script setup>
    import { ref, onMounted } from 'vue'

    const items = ref([])
    const displayLoading = ref(true)

    const loadItems = async () => {
        displayLoading.value = true

        try {
            const res = await axios.get(route('security_users_online')) // tu ruta
            items.value = res.data.usersOnline
            //console.log(items.value)
        } catch (error) {
            //console.error("Error al cargar items", error)
        } finally {
            displayLoading.value = false
        }
    }

    onMounted(() => {
        loadItems()
    });

    const baseUrl = assetUrl;

    const getImage = (path) => {
        return baseUrl + 'storage/'+ path;
    }

</script>

<template>
    <div class="bg-white border border-gray-200 border-t-4 border-t-blue-600 shadow-2xs rounded-xl dark:bg-neutral-900 dark:border-neutral-700 dark:border-t-blue-500 dark:shadow-neutral-700/70">

        <!-- LOADING STATE - Skeleton -->
        <div v-if="displayLoading">
            <div class="animate-pulse space-y-3">

                <!-- Skeleton item -->
                <div class="flex items-center space-x-4 p-4 border rounded-lg">
                    <div class="h-10 w-10 bg-gray-300 rounded-full"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-gray-300 rounded w-3/4"></div>
                        <div class="h-4 bg-gray-300 rounded w-1/2"></div>
                    </div>
                </div>

                <div class="flex items-center space-x-4 p-4 border rounded-lg">
                    <div class="h-10 w-10 bg-gray-300 rounded-full"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-gray-300 rounded w-3/4"></div>
                        <div class="h-4 bg-gray-300 rounded w-1/2"></div>
                    </div>
                </div>

                <!-- Puedes duplicar si deseas más skeletons -->
            </div>
        </div>

        <!-- DATA STATE -->
        <div v-else class="px-4 py-2.5">
            <h5 class="font-semibold text-lg mb-6">
                🟢 Usuarios en línea
            </h5>
            <ul>
                <li v-for="item in items.data" :key="item.id" class="w-full">
                    <div class="flex items-center justify-between">
                        <div class="mx-3 ltr:text-left rtl:text-right">
                            <p class="mb-1 font-semibold">{{ item.name }}</p>
                            <p class="text-xs text-white-dark truncate max-w-[185px]">{{ item.description }}</p>
                        </div>
                        <div class="flex items-center gap-2 font-semibold whitespace-nowrap text-xs">
                            <span v-if="item.online">🟢</span>
                            <p>{{ item.last_activity }}</p>
                        </div>
                    </div>
                </li>
            </ul>
            <!-- Si tuvieras lista vacía -->
            <div v-if="items.length === 0" class="text-center text-gray-500 py-8">
                No hay datos para mostrar.
            </div>
        </div>

    </div>
</template>
