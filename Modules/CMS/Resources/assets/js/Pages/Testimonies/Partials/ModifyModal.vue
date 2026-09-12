<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import ModalLargeX from '@/Components/ModalLargeX.vue';
import InputError from '@/Components/InputError.vue';
import IaCorrectionMenu from '../../../Components/IaCorrectionMenu.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    testimony: {
        type: Object,
        default: null,
    },
    // Texto ya corregido por la IA (cuando se usa el boton IA de la lista)
    initialComment: {
        type: String,
        default: null,
    },
    // Modo de la correccion aplicada desde la lista: writing | spelling
    initialIaMode: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);

const baseUrl = assetUrl;

const iaLoading = ref(false);
const iaMode = ref(null);
const photoPreview = ref(null);
const removeImage = ref(false);
const removeVideo = ref(false);

const form = useForm({
    id: null,
    author_name: '',
    author_role: '',
    rating: 5,
    description: '',
    image: null,
    remove_image: false,
    video: '',
    remove_video: false,
    status: true,
    approval_status: 'approved',
    ia_correction_mode: null,
});

const isEditorial = computed(() => (props.testimony?.source || 'cms') !== 'student');

const currentImage = computed(() => {
    if (removeImage.value) return null;
    if (photoPreview.value) return photoPreview.value;
    return props.testimony?.image ? baseUrl + 'storage/' + props.testimony.image : null;
});

const videoPreview = computed(() => (removeVideo.value ? null : form.video));

const load = () => {
    if (!props.testimony) return;

    const t = props.testimony;

    form.reset();
    form.clearErrors();
    removeImage.value = false;
    removeVideo.value = false;
    photoPreview.value = null;

    form.id = t.id;
    form.author_name = t.author_name || '';
    form.author_role = t.author_role || '';
    form.rating = t.rating || 5;
    form.description = props.initialComment || t.description || '';
    form.video = t.video || '';
    form.status = t.status === true || t.status === 1;
    form.approval_status = t.approval_status === 'approved' ? 'approved' : 'pending';

    // Si el texto llego ya corregido desde la lista, conservamos el modo para
    // registrar la correccion al guardar.
    iaMode.value = props.initialComment ? (props.initialIaMode || 'writing') : null;
    form.ia_correction_mode = iaMode.value;
};

watch(() => props.show, (value) => {
    if (value) load();
});

const close = () => {
    if (form.processing || iaLoading.value) return;
    emit('close');
};

const setRating = (value) => {
    form.rating = value;
};

const onImageChange = (event) => {
    const file = event.target.files[0] || null;
    form.image = file;
    removeImage.value = false;
    photoPreview.value = file ? URL.createObjectURL(file) : null;
};

const markRemoveImage = () => {
    form.image = null;
    removeImage.value = true;
    photoPreview.value = null;
};

const markRemoveVideo = () => {
    removeVideo.value = true;
};

/**
 * Correccion con IA: se mantiene el texto corregido en el comentario para que
 * el admin lo revise antes de guardar.
 */
const correctWithIa = (mode) => {
    iaLoading.value = true;

    axios
        .post(route('cms_testimonies_ia_correct'), {
            id: form.id,
            text: form.description,
            mode: mode,
        })
        .then((response) => {
            if (!response.data.success) {
                Swal2.fire({
                    icon: 'error',
                    title: 'No se pudo corregir',
                    text: response.data.message || 'Inténtalo nuevamente.',
                    confirmButtonText: 'Entendido',
                });
                return;
            }

            form.description = response.data.corrected;
            iaMode.value = mode;
            form.ia_correction_mode = mode;

            Swal2.fire({
                icon: 'success',
                title: 'Texto corregido',
                text: 'Revisa la corrección y guarda para aplicarla.',
                timer: 2000,
                showConfirmButton: false,
            });
        })
        .catch(() => {
            Swal2.fire({
                icon: 'error',
                title: 'No se pudo corregir',
                text: 'Hubo un problema al conectar con la IA.',
                confirmButtonText: 'Entendido',
            });
        })
        .finally(() => {
            iaLoading.value = false;
        });
};

const save = () => {
    form.remove_image = removeImage.value;
    form.remove_video = removeVideo.value;
    form.status = form.approval_status === 'approved' ? true : form.status;

    form.post(route('cms_testimonies_admin_update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            emit('close');
            Swal2.fire({
                icon: 'success',
                title: 'Testimonio actualizado',
                timer: 1600,
                showConfirmButton: false,
            });
        },
        onError: () => {
            Swal2.fire({
                icon: 'error',
                title: 'Revisa el formulario',
                text: 'Hubo un problema al guardar. Revisa los campos marcados.',
                confirmButtonText: 'Entendido',
            });
        },
    });
};
</script>

<template>
    <ModalLargeX :show="show"
                 :on-close="close"
                 :loading="form.processing || iaLoading"
                 :loading-text="iaLoading ? 'Corrigiendo con IA, espera un momento...' : 'Guardando, espera un momento...'">
        <template #title>Modificar testimonio</template>
        <template #message>
            Puedes ajustar el contenido, la calificación, la imagen{{ isEditorial ? ', el video' : '' }} y su estado de publicación.
        </template>

        <template #content>
            <div class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                            Nombre del autor
                        </label>
                        <input v-model="form.author_name"
                               type="text"
                               maxlength="255"
                               placeholder="Nombre que se mostrará"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                        <InputError :message="form.errors.author_name" class="mt-2" />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                            Calificación
                        </label>
                        <div class="flex items-center gap-2">
                            <button v-for="n in 5"
                                    :key="n"
                                    type="button"
                                    @click="setRating(n)"
                                    class="text-2xl transition-transform hover:scale-110"
                                    :class="n <= form.rating ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600'">
                                <i :class="n <= form.rating ? 'ri-star-fill' : 'ri-star-line'"></i>
                            </button>
                            <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">{{ form.rating }}/5</span>
                        </div>
                        <InputError :message="form.errors.rating" class="mt-2" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                        Cargo o profesión
                    </label>
                    <input v-model="form.author_role"
                           type="text"
                           maxlength="255"
                           placeholder="Ej. Contador Público"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                    <InputError :message="form.errors.author_role" class="mt-2" />
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                            Comentario
                        </label>
                        <IaCorrectionMenu align="right" :loading="iaLoading" @select="correctWithIa" />
                    </div>
                    <textarea v-model="form.description"
                              rows="5"
                              maxlength="2000"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
                    <div class="flex justify-between text-xs text-gray-400 mt-1">
                        <span v-if="iaMode" class="inline-flex items-center gap-1 text-indigo-500 dark:text-indigo-300">
                            <i class="ri-sparkling-2-line"></i>
                            {{ iaMode === 'spelling' ? 'Ortografía corregida con IA' : 'Redacción y ortografía corregidas con IA' }} — guarda para aplicarlo
                        </span>
                        <span v-else>Corrige con IA y luego guarda para aplicarlo</span>
                        <span>{{ (form.description || '').length }}/2000</span>
                    </div>
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                        Imagen
                    </label>
                    <div class="flex items-start gap-4">
                        <div class="w-28 h-28 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                            <img v-if="currentImage" :src="currentImage" alt="Imagen del testimonio" class="w-full h-full object-cover" />
                            <i v-else class="ri-image-off-line text-2xl text-gray-400"></i>
                        </div>
                        <div class="flex-1">
                            <input type="file"
                                   accept="image/jpeg,image/png,image/webp"
                                   @change="onImageChange"
                                   class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-200" />
                            <p class="text-xs text-gray-400 mt-1">JPG, PNG o WEBP · máx. 2 MB</p>
                            <button v-if="currentImage"
                                    type="button"
                                    @click="markRemoveImage"
                                    class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-red-600 hover:text-red-700 dark:text-red-400">
                                <i class="ri-delete-bin-line"></i> Quitar imagen
                            </button>
                            <p v-else-if="removeImage" class="mt-2 text-xs text-red-600 dark:text-red-400">
                                La imagen se quitará al guardar.
                            </p>
                        </div>
                    </div>
                    <InputError :message="form.errors.image" class="mt-2" />
                </div>

                <div v-if="isEditorial">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                        Video (iframe)
                    </label>
                    <textarea v-model="form.video"
                              rows="3"
                              placeholder="<iframe src=&quot;...&quot;></iframe>"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
                    <button v-if="form.video && !removeVideo"
                            type="button"
                            @click="markRemoveVideo"
                            class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-red-600 hover:text-red-700 dark:text-red-400">
                        <i class="ri-delete-bin-line"></i> Quitar video
                    </button>
                    <p v-else-if="removeVideo" class="mt-2 text-xs text-red-600 dark:text-red-400">
                        El video se quitará al guardar.
                    </p>
                    <div v-if="videoPreview" class="mt-3 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600">
                        <div class="w-full" v-html="videoPreview"></div>
                    </div>
                    <InputError :message="form.errors.video" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                            Estado
                        </label>
                        <select v-model="form.approval_status"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="approved">Publicado (aprobado)</option>
                            <option value="pending">Pendiente (en revisión)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                            Visibilidad
                        </label>
                        <label class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-600 p-3 cursor-pointer">
                            <input type="checkbox"
                                   v-model="form.status"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-200">Visible en /testimonios</span>
                        </label>
                    </div>
                </div>
            </div>
        </template>

        <template #buttons>
            <button type="button"
                    @click="save"
                    :disabled="form.processing || iaLoading"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700 disabled:opacity-60">
                <i class="ri-save-line"></i>
                Guardar cambios
            </button>
        </template>
    </ModalLargeX>
</template>
