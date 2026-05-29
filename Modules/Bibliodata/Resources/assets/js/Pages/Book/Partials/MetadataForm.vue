<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import { CONTENT_STRUCTURE_OPTIONS } from '../../../composables/useBookContentLabels';

const props = defineProps({
    authors: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
    book: { type: Object, default: null },
});

const form = useForm({
    id: props.book?.id || null,
    title: props.book?.title || '',
    code_name: props.book?.code_name || '',
    isbn: props.book?.isbn || '',
    author_id: props.book?.author_id || '',
    category_id: props.book?.category_id || '',
    tag_ids: props.book?.tags?.map((t) => t.id) || [],
    pages: props.book?.pages ?? '',
    status: props.book?.status || 'available',
    description: props.book?.description || '',
    cover_image: null,
    file_path: null,
    content_structure: props.book?.content_structure || 'chapter_subchapter',
});

const structureOptions = CONTENT_STRUCTURE_OPTIONS;
const isEditing = !!props.book?.id;

const coverPreview = ref(
    props.book?.cover_image ? '/storage/' + props.book.cover_image : null
);
const fileNameDisplay = ref(
    props.book?.file_path ? props.book.file_path.split('/').pop() : ''
);

const onCoverChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.cover_image = file;
        coverPreview.value = URL.createObjectURL(file);
    }
};

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.file_path = file;
        fileNameDisplay.value = file.name;
    }
};

const toggleTag = (tagId) => {
    const idx = form.tag_ids.indexOf(tagId);
    if (idx >= 0) {
        form.tag_ids.splice(idx, 1);
    } else {
        form.tag_ids.push(tagId);
    }
};

const submit = () => {
    if (form.id) {
        form.post(route('bib_books_update'), { preserveScroll: true });
    } else {
        form.post(route('bib_books_store'), { preserveScroll: true });
    }
};

defineExpose({ form });
</script>

<template>
    <div class="space-y-6">
        <FormSection @submitted="submit">
            <template #title>Información del libro</template>
            <template #description>
                Datos generales del libro. El contenido se gestiona en una pantalla aparte.
            </template>
            <template #form>
                <div class="col-span-6 lg:col-span-4">
                    <InputLabel value="Título *" />
                    <TextInput v-model="form.title" type="text" class="w-full" placeholder="Título del libro" />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="col-span-6 sm:col-span-3 lg:col-span-2">
                    <InputLabel value="Código interno" />
                    <TextInput v-model="form.code_name" type="text" class="w-full" placeholder="Opcional" />
                    <InputError :message="form.errors.code_name" />
                </div>

                <div class="col-span-6 sm:col-span-3 lg:col-span-2">
                    <InputLabel value="Autor *" />
                    <select v-model="form.author_id" class="form-select w-full">
                        <option value="">Seleccionar autor</option>
                        <option v-for="a in authors" :key="a.id" :value="a.id">
                            {{ a.person?.formatted_name || a.person?.full_name || 'Autor #' + a.id }}
                        </option>
                    </select>
                    <InputError :message="form.errors.author_id" />
                </div>

                <div class="col-span-6 sm:col-span-3 lg:col-span-2">
                    <InputLabel value="Categoría *" />
                    <select v-model="form.category_id" class="form-select w-full">
                        <option value="">Seleccionar categoría</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <InputError :message="form.errors.category_id" />
                </div>

                <div class="col-span-6 sm:col-span-3 lg:col-span-2">
                    <InputLabel value="ISBN" />
                    <TextInput v-model="form.isbn" type="text" class="w-full" placeholder="ISBN" />
                    <InputError :message="form.errors.isbn" />
                </div>

                <div class="col-span-6 sm:col-span-3 lg:col-span-2">
                    <InputLabel value="N° de páginas (referencia)" />
                    <TextInput v-model="form.pages" type="number" class="w-full" min="0" placeholder="0" />
                    <p class="text-xs text-gray-400 mt-1">Útil para generar páginas vacías en masa</p>
                    <InputError :message="form.errors.pages" />
                </div>

                <div class="col-span-6 sm:col-span-3 lg:col-span-2">
                    <InputLabel value="Estado" />
                    <select v-model="form.status" class="form-select w-full">
                        <option value="available">Disponible</option>
                        <option value="restricted">Restringido</option>
                        <option value="archived">Archivado</option>
                    </select>
                    <InputError :message="form.errors.status" />
                </div>

                <div class="col-span-6">
                    <InputLabel value="Formato de contenido *" />
                    <select
                        v-model="form.content_structure"
                        class="form-select w-full"
                        :disabled="isEditing"
                    >
                        <option
                            v-for="opt in structureOptions"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
                        </option>
                    </select>
                    <p v-if="isEditing" class="text-xs text-gray-400 mt-1">
                        Para cambiar el formato, use <strong>Gestionar contenido</strong> (solo si no hay sub-capítulos).
                    </p>
                    <p v-else class="text-xs text-gray-400 mt-1">
                        Define cómo se organizará el árbol de contenido del libro.
                    </p>
                    <InputError :message="form.errors.content_structure" />
                </div>

                <div class="col-span-6">
                    <InputLabel value="Tags / Etiquetas" />
                    <div class="flex flex-wrap gap-2 mt-2">
                        <button
                            v-for="tag in tags"
                            :key="tag.id"
                            type="button"
                            @click="toggleTag(tag.id)"
                            class="px-3 py-1.5 text-xs rounded-full border transition"
                            :class="
                                form.tag_ids.includes(tag.id)
                                    ? 'bg-primary text-white border-primary'
                                    : 'bg-gray-50 dark:bg-zinc-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-zinc-600 hover:border-primary'
                            "
                        >
                            {{ tag.name }}
                        </button>
                    </div>
                    <InputError :message="form.errors.tag_ids" />
                </div>

                <div class="col-span-6">
                    <InputLabel value="Sinopsis / Descripción" />
                    <textarea
                        v-model="form.description"
                        rows="4"
                        class="form-textarea w-full"
                        placeholder="Descripción del libro..."
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="col-span-6 border-t border-gray-200 dark:border-zinc-700 pt-4 mt-2">
                    <p class="text-sm font-semibold text-gray-700 dark:text-neutral-200 mb-3">Portada y archivo</p>
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <InputLabel value="Portada" />
                    <div class="mt-2 flex items-center gap-4">
                        <div
                            class="w-24 h-32 rounded-lg border-2 border-dashed border-gray-300 dark:border-zinc-600 flex items-center justify-center overflow-hidden bg-gray-50 dark:bg-zinc-800"
                        >
                            <img v-if="coverPreview" :src="coverPreview" class="w-full h-full object-cover" alt="" />
                            <span v-else class="text-xs text-gray-400">Sin imagen</span>
                        </div>
                        <label class="cursor-pointer inline-block px-4 py-2 bg-gray-100 dark:bg-zinc-700 text-sm rounded-lg hover:bg-gray-200 dark:hover:bg-zinc-600">
                            Seleccionar imagen
                            <input type="file" accept="image/*" class="hidden" @change="onCoverChange" />
                        </label>
                    </div>
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <InputLabel value="Archivo PDF" />
                    <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 mt-2 bg-gray-100 dark:bg-zinc-700 text-sm rounded-lg hover:bg-gray-200 dark:hover:bg-zinc-600">
                        {{ fileNameDisplay || 'Seleccionar PDF' }}
                        <input type="file" accept=".pdf" class="hidden" @change="onFileChange" />
                    </label>
                    <p class="text-xs text-gray-400 mt-1">Máximo 50MB</p>
                </div>
            </template>
            <template #actions>
                <Link v-if="book?.id" :href="route('bib_books_content', book.id)" class="btn btn-outline-primary mr-2">
                    Gestionar contenido
                </Link>
                <Link :href="route('bib_books')" class="btn btn-danger mr-2">Cancelar</Link>
                <PrimaryButton :disabled="form.processing">
                    <IconLoader v-if="form.processing" class="w-4 h-4 mr-2 animate-spin inline" />
                    {{ book?.id ? 'Guardar cambios' : 'Crear y continuar al contenido' }}
                </PrimaryButton>
            </template>
        </FormSection>
    </div>
</template>
