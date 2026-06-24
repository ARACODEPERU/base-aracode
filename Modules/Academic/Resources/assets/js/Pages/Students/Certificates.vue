<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { useForm, router } from '@inertiajs/vue3';
    import Pagination from "@/Components/Pagination.vue";
    import Swal from 'sweetalert2';
    import { computed, nextTick, ref, watch } from 'vue';
    import QRCode from 'qrcode';
    import { jsPDF } from 'jspdf';

    const props = defineProps({
        certificates: {
            type: Object,
            default: () => ({}),
        },
        courses: {
            type: Array,
            default: () => [],
        },
        filters: {
            type: Object,
            default: () => ({}),
        }
    });

    const form = useForm({
        course_id: props.filters.course_id || '',
        per_page: props.filters.per_page || 12,
    });

    const formatDate = (date) => {
        if (!date) return '-';
        return new Date(date).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    };

    const searchCertificates = () => {
        form.get(route('aca_student_certificates_all'));
    };

    const clearFilters = () => {
        form.course_id = '';
        form.per_page = 12;
        router.get(route('aca_student_certificates_all'));
    };

    // const downloadCertificate = (certificateId) => {
    //     window.open(route('aca_image_download', certificateId), '_blank');
    // };

    const loadingStates = ref({});

    const downloadCertificate = async (certificateId) => {
        loadingStates.value[certificateId] = true;

        try {
            const response = await axios.get(route('aca_image_download', certificateId), {
                responseType: 'blob',
            });

            // Intentar obtener el nombre del archivo desde los headers (Content-Disposition)
            const contentDisposition = response.headers['content-disposition'];
            let fileName = 'certificado_descarga'; // Nombre por defecto

            if (contentDisposition) {
                const match = contentDisposition.match(/filename="?([^"]+)"?/);
                if (match) fileName = match[1];
            } else {
                // Si el backend no envía el nombre, puedes asignar una extensión lógica
                // según lo que sepas que se está descargando (ej. .zip o .png)
                fileName = `Certificado_${certificateId}`;
            }

            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', fileName);
            document.body.appendChild(link);
            link.click();
            link.remove();

        } catch (error) {
            const title = error.response?.status === 404 ? 'Certificado no encontrado' : 'Error al descargar';
            const message = error.response?.data?.message || 'Falta configuración del administrador.';
            Swal.fire({
                icon: 'error',
                title: title,
                text: message,
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        } finally {
            loadingStates.value[certificateId] = false;
        }
    };
    const certificateModalOpen = ref(false);
    const previewLoading = ref(false);
    const certificatePreview = ref(null);
    const activeSideKey = ref('front');
    const stageRef = ref(null);
    const previewWrap = ref(null);
    const stageWidth = ref(900);
    const stageMaxHeight = ref(650);
    const loadedImages = ref({});
    const qrImages = ref({});

    const activeSide = computed(() => {
        return certificatePreview.value?.sides?.find((side) => side.key === activeSideKey.value) || certificatePreview.value?.sides?.[0] || null;
    });

    const stageScale = computed(() => {
        if (!activeSide.value?.width) return 1;

        const widthScale = stageWidth.value / activeSide.value.width;
        const heightScale = stageMaxHeight.value / activeSide.value.height;

        return Math.min(widthScale, heightScale, 1);
    });

    const stageConfig = computed(() => ({
        width: activeSide.value ? activeSide.value.width * stageScale.value : stageWidth.value,
        height: activeSide.value ? activeSide.value.height * stageScale.value : 500,
    }));
    const layerConfig = computed(() => ({
        scaleX: stageScale.value,
        scaleY: stageScale.value,
    }));

    const baseImage = computed(() => loadedImages.value[activeSide.value?.key] || null);
    const qrImage = computed(() => qrImages.value[activeSide.value?.key] || null);

    const loadImage = (src) => new Promise((resolve) => {
        if (!src) {
            resolve(null);
            return;
        }

        const image = new Image();
        image.crossOrigin = 'anonymous';
        image.onload = () => resolve(image);
        image.onerror = () => resolve(null);
        image.src = src;
    });

    const updateStageWidth = () => {
        stageWidth.value = previewWrap.value?.clientWidth || 900;
        stageMaxHeight.value = previewWrap.value?.clientHeight || 650;
    };

    const prepareSideAssets = async (side) => {
        if (!side) return;

        const image = await loadImage(side.base_image);
        loadedImages.value = { ...loadedImages.value, [side.key]: image };

        if (side.qr?.text) {
            const qrData = await QRCode.toDataURL(side.qr.text, {
                margin: 2,
                width: Math.max(side.qr.size || 120, 120),
            });
            const qr = await loadImage(qrData);
            qrImages.value = { ...qrImages.value, [side.key]: qr };
        }
    };

    const openCertificatePreview = async (certificateId) => {
        loadingStates.value[certificateId] = true;
        previewLoading.value = true;

        try {
            const response = await axios.get(route('aca_image_download', certificateId), {
                params: { preview: 1 },
            });

            certificatePreview.value = response.data;
            activeSideKey.value = response.data?.sides?.[0]?.key || 'front';
            certificateModalOpen.value = true;
            await nextTick();
            updateStageWidth();
            await Promise.all((response.data?.sides || []).map(prepareSideAssets));
        } catch (error) {
            const title = error.response?.status === 404 ? 'Certificado no encontrado' : 'Error al descargar';
            const message = error.response?.data?.message || 'Falta configuraciÃ³n del administrador.';
            Swal.fire({
                icon: 'error',
                title: title,
                text: message,
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        } finally {
            loadingStates.value[certificateId] = false;
            previewLoading.value = false;
        }
    };

    const closeCertificatePreview = () => {
        certificateModalOpen.value = false;
        certificatePreview.value = null;
        loadedImages.value = {};
        qrImages.value = {};
    };

    const textConfig = (item) => {
        const fontSize = Number(item.font_size || 18);
        const fontFamily = item.font_family || 'Arial';
        const align = item.align || 'left';
        const config = {
            x: Number(item.x || 0),
            y: Number(item.y || 0),
            text: item.text || '',
            fontSize,
            fontFamily,
            fill: item.color || '#000000',
            align,
            lineHeight: Number(item.line_height || 1.25),
            opacity: 0.96,
        };

        if (item.width !== null && item.width !== undefined && item.width !== '') {
            config.width = Number(item.width);
        }

        return config;
    };

    const qrAnchorToPosition = (align, x, y, size) => {
        const normalized = align || 'top-left';
        let positionX = Number(x || 0);
        let positionY = Number(y || 0);

        if (normalized.includes('right')) positionX = Number(activeSide.value?.width || 0) - size - positionX;
        if (normalized.includes('center')) positionX = (Number(activeSide.value?.width || 0) - size) / 2 + positionX;
        if (normalized.includes('bottom')) positionY = Number(activeSide.value?.height || 0) - size - positionY;

        return { x: positionX, y: positionY };
    };

    const qrConfig = computed(() => {
        if (!activeSide.value?.qr || !qrImage.value) return null;

        const size = Number(activeSide.value.qr.size || 120);
        const position = qrAnchorToPosition(
            activeSide.value.qr.align,
            activeSide.value.qr.x,
            activeSide.value.qr.y,
            size
        );

        return {
            image: qrImage.value,
            x: position.x,
            y: position.y,
            width: size,
            height: size,
            opacity: 0.98,
        };
    });

    const contentRows = (item) => {
        return (item.modules || []).map((module, index) => ({
            module: `${index + 1}. ${module.title || 'Modulo'}`,
            content: (module.themes || []).join(', '),
        }));
    };

    const rowHeight = (item, row) => {
        const fontSize = Number(item.font_size || 14);
        const contentLength = Math.max((row.content || '').length, 1);
        const lines = Math.max(1, Math.ceil(contentLength / 72));
        return Math.max(fontSize * (lines + 1.2), 34);
    };

    const rowY = (item, rowIndex) => {
        const rows = contentRows(item);
        return rows.slice(0, rowIndex).reduce((total, row) => total + rowHeight(item, row), 34);
    };

    const tableHeight = (item) => {
        return contentRows(item).reduce((total, row) => total + rowHeight(item, row), 34);
    };

    const contentText = (item) => {
        return (item.modules || []).map((module, index) => {
            const themes = (module.themes || []).map((theme) => `- ${theme}`).join('\n');
            return `${index + 1}. ${module.title || 'Modulo'}${themes ? `\n${themes}` : ''}`;
        }).join('\n\n');
    };

    const downloadDataUrl = (dataUrl, fileName) => {
        const link = document.createElement('a');
        link.href = dataUrl;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        link.remove();
    };

    const getStageDataUrl = () => {
        const stage = stageRef.value?.getStage();
        return stage?.toDataURL({ pixelRatio: 2 / stageScale.value, mimeType: 'image/png' });
    };

    const downloadPng = () => {
        const dataUrl = getStageDataUrl();
        if (!dataUrl || !activeSide.value) return;
        downloadDataUrl(dataUrl, `${certificatePreview.value?.file_name || 'certificado'}_${activeSide.value.key}.png`);
    };

    const downloadPdf = () => {
        const dataUrl = getStageDataUrl();
        if (!dataUrl || !activeSide.value) return;

        const orientation = activeSide.value.width >= activeSide.value.height ? 'landscape' : 'portrait';
        const pdf = new jsPDF({
            orientation,
            unit: 'px',
            format: [activeSide.value.width, activeSide.value.height],
        });

        pdf.addImage(dataUrl, 'PNG', 0, 0, activeSide.value.width, activeSide.value.height);
        pdf.save(`${certificatePreview.value?.file_name || 'certificado'}_${activeSide.value.key}.pdf`);
    };

    watch(certificateModalOpen, async (isOpen) => {
        if (isOpen) {
            await nextTick();
            updateStageWidth();
            window.addEventListener('resize', updateStageWidth);
        } else {
            window.removeEventListener('resize', updateStageWidth);
        }
    });
</script>

<template>
    <AppLayout title="Mis Certificados">
        <Navigation
            :routeModule="route('aca_dashboard')"
            :titleModule="'Academico'"
            :data="[
                { route: route('aca_mycourses'), title: 'Mis Cursos' },
                { title: 'Mis Certificados' }
            ]"
        />

        <div class="mt-5">
            <div class="panel p-0">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Mis Certificados</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Descarga tus certificados obtenidos
                    </p>
                </div>

                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col md:flex-row gap-3 items-end">
                        <div class="w-full md:w-64">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Curso</label>
                            <select v-model="form.course_id" class="form-select w-full">
                                <option value="">Todos los cursos</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">
                                    {{ course.description }}
                                </option>
                            </select>
                        </div>

                        <button @click="searchCertificates" class="btn btn-primary">
                            Buscar
                        </button>
                        <button
                            v-if="form.course_id"
                            @click="clearFilters"
                            class="btn btn-outline-danger"
                        >
                            Limpiar
                        </button>
                    </div>
                </div>

                <Pagination :data="certificates">
                <div class="p-5">
                    <div v-if="!certificates.data || certificates.data.length === 0" class="text-center py-16">
                        <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No hay certificados</h3>
                        <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                            Aun no has obtenido ningun certificado. Completa tus cursos para obtener tus certificados.
                        </p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div
                            v-for="certificate in certificates.data"
                            :key="certificate.id"
                            class="group relative bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300"
                        >
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 via-purple-500/5 to-pink-500/5 dark:from-blue-500/10 dark:via-purple-500/10 dark:to-pink-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                            <div class="relative h-48 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-gray-700 dark:via-gray-700 dark:to-gray-700 flex items-center justify-center overflow-hidden">
                                <div class="absolute inset-0 bg-gradient-to-t from-white/20 to-transparent dark:from-gray-600/20"></div>
                                <div class="relative z-10 text-center px-4">
                                    <div class="w-20 h-20 mx-auto mb-3 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg">
                                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                        </svg>
                                    </div>
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Certificado
                                    </span>
                                </div>
                                <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500"></div>
                            </div>

                            <div class="relative p-5">
                                <h3 class="font-bold text-gray-900 dark:text-white text-base mb-1 line-clamp-2">
                                    {{ certificate.course?.description || 'Curso' }}
                                </h3>
                                <p v-if="certificate.module" class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                                    {{ certificate.module.description }}
                                </p>
                                <div class="flex items-center justify-between mt-4">
                                    <div class="text-xs text-gray-400 dark:text-gray-500">
                                        <span class="block">Emitido</span>
                                        <span class="font-medium text-gray-600 dark:text-gray-300">{{ formatDate(certificate.created_at) }}</span>
                                    </div>
                                    <button
                                        @click="openCertificatePreview(certificate.id)"
                                        :disabled="loadingStates[certificate.id]"
                                        :class="[
                                            'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white text-sm font-medium transition-all duration-200 shadow-md',
                                            loadingStates[certificate.id] ? 'bg-gray-400 cursor-not-allowed' : 'bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 hover:shadow-lg'
                                        ]"
                                    >
                                        <svg v-if="loadingStates[certificate.id]" class="w-4 h-4 animate-spin" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>

                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>

                                        {{ loadingStates[certificate.id] ? 'Procesando...' : 'Ver certificado' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                </Pagination>
            </div>
        </div>

        <div
            v-if="certificateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
        >
            <div class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-lg bg-white shadow-2xl dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Certificado</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Visualiza el certificado antes de descargarlo</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-full p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                        @click="closeCertificatePreview"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                    <div class="flex gap-2">
                        <button
                            v-for="side in certificatePreview?.sides || []"
                            :key="side.key"
                            type="button"
                            :class="[
                                'rounded px-4 py-2 text-sm font-semibold',
                                activeSideKey === side.key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200'
                            ]"
                            @click="activeSideKey = side.key"
                        >
                            {{ side.label }}
                        </button>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" class="btn btn-primary" :disabled="previewLoading || !activeSide" @click="downloadPdf">
                            Descargar en PDF
                        </button>
                        <button type="button" class="btn btn-outline-primary" :disabled="previewLoading || !activeSide" @click="downloadPng">
                            Descargar en PNG
                        </button>
                    </div>
                </div>

                <div ref="previewWrap" class="min-h-0 flex-1 overflow-auto bg-gray-100 p-4 dark:bg-gray-950">
                    <div v-if="previewLoading" class="flex h-96 items-center justify-center text-gray-500">
                        Preparando certificado...
                    </div>
                    <div v-else-if="activeSide" class="mx-auto w-fit overflow-hidden rounded bg-white shadow">
                        <v-stage ref="stageRef" :config="stageConfig">
                            <v-layer :config="layerConfig">
                                <v-image
                                    v-if="baseImage"
                                    :config="{ image: baseImage, x: 0, y: 0, width: activeSide.width, height: activeSide.height }"
                                />
                                <v-text
                                    v-for="item in activeSide.texts"
                                    :key="item.id"
                                    :config="textConfig(item)"
                                />
                                <template v-for="item in activeSide.contents" :key="item.id">
                                    <v-group v-if="item.type === 'table'" :config="{ x: item.x, y: item.y }">
                                        <v-rect :config="{ x: 0, y: 0, width: item.width, height: tableHeight(item), stroke: item.color || '#111827', strokeWidth: 1 }" />
                                        <v-rect :config="{ x: 0, y: 0, width: item.width, height: 34, fill: 'rgba(243, 244, 246, 0.92)', stroke: item.color || '#111827', strokeWidth: 1 }" />
                                        <v-line :config="{ points: [item.width * 0.34, 0, item.width * 0.34, tableHeight(item)], stroke: item.color || '#111827', strokeWidth: 1 }" />
                                        <v-text :config="{ x: 8, y: 9, text: 'MODULO', fontSize: item.font_size, fontFamily: item.font_family || 'Arial', fontStyle: 'bold', fill: item.color || '#000000', width: item.width * 0.34 - 16 }" />
                                        <v-text :config="{ x: item.width * 0.34 + 8, y: 9, text: 'CONTENIDO', fontSize: item.font_size, fontFamily: item.font_family || 'Arial', fontStyle: 'bold', fill: item.color || '#000000', width: item.width * 0.66 - 16 }" />
                                        <template v-for="(row, rowIndex) in contentRows(item)" :key="`${item.id}-${rowIndex}`">
                                            <v-line :config="{ points: [0, rowY(item, rowIndex), item.width, rowY(item, rowIndex)], stroke: item.color || '#111827', strokeWidth: 1 }" />
                                            <v-text :config="{ x: 8, y: rowY(item, rowIndex) + 8, text: row.module, fontSize: item.font_size, fontFamily: item.font_family || 'Arial', fill: item.color || '#000000', width: item.width * 0.34 - 16 }" />
                                            <v-text :config="{ x: item.width * 0.34 + 8, y: rowY(item, rowIndex) + 8, text: row.content, fontSize: item.font_size, fontFamily: item.font_family || 'Arial', fill: item.color || '#000000', width: item.width * 0.66 - 16, lineHeight: 1.2 }" />
                                        </template>
                                    </v-group>
                                    <v-text
                                        v-else
                                        :config="{ x: item.x, y: item.y, text: contentText(item), fontSize: item.font_size, fontFamily: item.font_family || 'Arial', fill: item.color || '#000000', width: item.width, lineHeight: 1.25 }"
                                    />
                                </template>
                                <v-image
                                    v-if="qrConfig"
                                    :config="qrConfig"
                                />
                            </v-layer>
                        </v-stage>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
