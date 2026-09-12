<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { ref } from 'vue';
import Swal2 from "sweetalert2";
import { faSitemap, faSync, faFileCode } from "@fortawesome/free-solid-svg-icons";

const props = defineProps({
    sitemapContent: {
        type: String,
        default: '',
    },
    sitemapExists: {
        type: Boolean,
        default: false,
    },
    appUrl: {
        type: String,
        default: '',
    },
});

const content = ref(props.sitemapContent);
const exists = ref(props.sitemapExists);
const loading = ref(false);
const totalUrls = ref(0);

const form = useForm({});

const parseXml = (xml) => {
    try {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(xml, 'text/xml');
        const urlNodes = xmlDoc.querySelectorAll('url');
        totalUrls.value = urlNodes.length;
        parsedUrls.value = Array.from(urlNodes).map((urlNode) => {
            return {
                loc: urlNode.querySelector('loc')?.textContent || '',
                lastmod: urlNode.querySelector('lastmod')?.textContent || '',
                changefreq: urlNode.querySelector('changefreq')?.textContent || '',
                priority: urlNode.querySelector('priority')?.textContent || '',
            };
        });
    } catch (e) {
        parsedUrls.value = [];
    }
};

const generateSitemap = () => {
    Swal2.fire({
        title: '¿Generar Sitemap?',
        text: exists.value ? 'Se actualizará el archivo sitemap.xml con las rutas actuales del proyecto.' : 'Se creará el archivo sitemap.xml con las rutas del proyecto.',
        icon: 'question',
        backdrop: true,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '¡Sí, Generar!',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return axios.post(route('cms_sitemap_generate')).then((res) => {
                if (!res.data.success) {
                    Swal2.showValidationMessage(res.data.message || 'No se pudo generar el sitemap');
                }
                return res;
            }).catch((error) => {
                const msg = error?.response?.data?.message || error?.response?.data?.exception || 'Error al generar el sitemap';
                Swal2.showValidationMessage(msg);
                return false;
            });
        },
        allowOutsideClick: () => !Swal2.isLoading(),
    }).then((result) => {
        if (result.isConfirmed && result.value?.data?.success) {
            content.value = result.value.data.content;
            exists.value = true;
            totalUrls.value = result.value.data.total_urls;
            parseXml(content.value);

            if (result.value.data.warning) {
                Swal2.fire({
                    title: 'Sitemap generado con advertencia',
                    html: `<p>${result.value.data.message}</p><p class="text-sm text-left mt-3">${result.value.data.warning}</p>`,
                    icon: 'warning',
                });
            } else {
                Swal2.fire({
                    title: '¡Sitemap Generado!',
                    text: result.value.data.message,
                    icon: 'success',
                });
            }
        }
    });
};

// Parse the XML to count URLs and display nicely
const parsedUrls = ref([]);
if (content.value) {
    parseXml(content.value);
}
</script>

<template>
    <AppLayout title="Sitemap">
        <Navigation :routeModule="route('cms_dashboard')" :titleModule="'CMS'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Sitemap</span>
            </li>
        </Navigation>

        <div class="mt-5">
            <!-- Header with action button -->
            <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-900 rounded-full w-10 h-10">
                        <font-awesome-icon :icon="faSitemap" class="text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
                            Gestión de Sitemap
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            URL base: {{ appUrl }}
                        </p>
                    </div>
                </div>
                <button
                    @click="generateSitemap"
                    class="flex items-center gap-2 px-6 py-2.5 bg-blue-900 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out"
                >
                    <font-awesome-icon :icon="faSync" />
                    {{ exists ? 'Actualizar Sitemap' : 'Crear Sitemap' }}
                </button>
            </div>

            <!-- Status info -->
            <div class="panel mb-6">
                <div class="flex items-center gap-3 p-4">
                    <div
                        :class="exists
                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                            : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'"
                        class="text-xs font-medium px-2.5 py-0.5 rounded"
                    >
                        {{ exists ? 'Activo' : 'No existe' }}
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-300">
                        <template v-if="exists">
                            <font-awesome-icon :icon="faFileCode" class="mr-1" />
                            sitemap.xml — {{ totalUrls }} URLs registradas
                        </template>
                        <template v-else>
                            No se encontró el archivo sitemap.xml. Haz clic en "Crear Sitemap" para generarlo.
                        </template>
                    </span>
                </div>
            </div>

            <!-- Parsed URL table -->
            <div v-if="parsedUrls.length > 0" class="panel p-0 mt-6">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>URL</th>
                                <th>Última Modificación</th>
                                <th>Frecuencia</th>
                                <th>Prioridad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="(url, index) in parsedUrls" :key="index">
                                <tr>
                                    <td>{{ index + 1 }}</td>
                                    <td>
                                        <a
                                            :href="url.loc"
                                            target="_blank"
                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline"
                                        >
                                            {{ url.loc }}
                                        </a>
                                    </td>
                                    <td>{{ url.lastmod }}</td>
                                    <td>
                                        <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-gray-300">
                                            {{ url.changefreq }}
                                        </span>
                                    </td>
                                    <td>{{ url.priority }}</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Raw XML preview -->
            <div v-if="content" class="panel mt-6">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Vista previa XML</h3>
                <pre class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-xs leading-relaxed max-h-[500px] overflow-y-auto"><code>{{ content }}</code></pre>
            </div>

            <!-- Empty state -->
            <div v-if="!exists && parsedUrls.length === 0" class="panel mt-6">
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="flex items-center justify-center bg-yellow-100 dark:bg-yellow-900 rounded-full w-16 h-16 mb-4">
                        <font-awesome-icon :icon="faSitemap" class="text-yellow-500 dark:text-yellow-400 text-2xl" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">
                        Sitemap no encontrado
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mb-6">
                        El archivo <code class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">sitemap.xml</code> no existe en el directorio público.
                        Haz clic en "Crear Sitemap" para generarlo automáticamente con las rutas del proyecto.
                    </p>
                    <button
                        @click="generateSitemap"
                        class="flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-medium text-sm rounded-lg hover:bg-blue-700 transition duration-150 ease-in-out"
                    >
                        <font-awesome-icon :icon="faSync" />
                        Crear Sitemap
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
