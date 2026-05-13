<script setup>
import { useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref } from 'vue';

const props = defineProps({
    authors: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
    book: { type: Object, default: null },
});

const emit = defineEmits(['save']);

const form = useForm({
    id: props.book?.id || null,
    title: props.book?.title || '',
    code_name: props.book?.code_name || '',
    isbn: props.book?.isbn || '',
    author_id: props.book?.author_id || '',
    category_id: props.book?.category_id || '',
    tag_ids: props.book?.tags?.map(t => t.id) || [],
    pages: props.book?.pages || '',
    status: props.book?.status || 'available',
    description: props.book?.description || '',
    cover_image: null,
    file_path: null,
    sections: [],
});

const coverPreview = ref(props.book?.cover_image ? '/storage/' + props.book.cover_image : null);
const fileNameDisplay = ref('');

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

const submit = () => {
    emit('save');
};

const toggleTag = (tagId) => {
    const idx = form.tag_ids.indexOf(tagId);
    if (idx >= 0) {
        form.tag_ids.splice(idx, 1);
    } else {
        form.tag_ids.push(tagId);
    }
};

defineExpose({ form });
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div class="panel">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM216 336h24V272H216c-13.3 0-24-10.7-24-24s10.7-24 24-24h48c13.3 0 24 10.7 24 24v88h8c13.3 0 24 10.7 24 24s-10.7 24-24 24H216c-13.3 0-24-10.7-24-24s10.7-24 24-24zm40-208a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/></svg>
                Información del Libro
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Título -->
                <div class="lg:col-span-2">
                    <InputLabel value="Título *" />
                    <TextInput type="text" v-model="form.title" class="w-full" placeholder="Título del libro" />
                    <InputError :message="form.errors.title" />
                </div>

                <!-- Código interno -->
                <div>
                    <InputLabel value="Código interno" />
                    <TextInput type="text" v-model="form.code_name" class="w-full" placeholder="Código opcional" />
                    <InputError :message="form.errors.code_name" />
                </div>

                <!-- Autor -->
                <div>
                    <InputLabel value="Autor *" />
                    <select v-model="form.author_id" class="form-select w-full">
                        <option value="">Seleccionar autor</option>
                        <option v-for="a in authors" :key="a.id" :value="a.id">
                            {{ a.person?.full_name || 'Autor #' + a.id }}
                        </option>
                    </select>
                    <InputError :message="form.errors.author_id" />
                </div>

                <!-- Categoría -->
                <div>
                    <InputLabel value="Categoría *" />
                    <select v-model="form.category_id" class="form-select w-full">
                        <option value="">Seleccionar categoría</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">
                            {{ c.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.category_id" />
                </div>

                <!-- ISBN -->
                <div>
                    <InputLabel value="ISBN" />
                    <TextInput type="text" v-model="form.isbn" class="w-full" placeholder="ISBN" />
                    <InputError :message="form.errors.isbn" />
                </div>

                <!-- Páginas -->
                <div>
                    <InputLabel value="N° de Páginas" />
                    <TextInput type="number" v-model="form.pages" class="w-full" placeholder="0" min="0" />
                    <InputError :message="form.errors.pages" />
                </div>

                <!-- Estado -->
                <div>
                    <InputLabel value="Estado" />
                    <select v-model="form.status" class="form-select w-full">
                        <option value="available">Disponible</option>
                        <option value="restricted">Restringido</option>
                        <option value="archived">Archivado</option>
                    </select>
                    <InputError :message="form.errors.status" />
                </div>
            </div>

            <!-- Tags -->
            <div class="mt-4">
                <InputLabel value="Tags / Etiquetas" />
                <div class="flex flex-wrap gap-2 mt-2">
                    <button type="button" v-for="tag in tags" :key="tag.id" @click="toggleTag(tag.id)"
                        class="px-3 py-1.5 text-xs rounded-full border transition"
                        :class="form.tag_ids.includes(tag.id)
                            ? 'bg-primary text-white border-primary'
                            : 'bg-gray-50 dark:bg-zinc-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-zinc-600 hover:border-primary'">
                        {{ tag.name }}
                    </button>
                </div>
                <InputError :message="form.errors.tag_ids" />
            </div>
        </div>

        <!-- Portada y Archivo -->
        <div class="panel">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M0 96C0 60.7 28.7 32 64 32H448c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zM323.8 202.5c-4.5-6.6-11.9-10.5-19.8-10.5s-15.4 3.9-19.8 10.5l-87 127.6L170.7 297c-4.6-5.7-11.5-9-18.7-9s-14.2 3.3-18.7 9l-64 80c-5.8 7.2-6.9 17.1-2.9 25.4s12.4 13.6 21.6 13.6h96 32H424c8.9 0 17.1-4.9 21.2-12.8s3.6-17.4-1.4-24.7l-120-176zM112 192a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/></svg>
                Portada y Archivo
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Portada -->
                <div>
                    <InputLabel value="Portada del libro" />
                    <div class="mt-1 flex items-center gap-4">
                        <div class="w-24 h-32 rounded-lg border-2 border-dashed border-gray-300 dark:border-zinc-600 flex items-center justify-center overflow-hidden bg-gray-50 dark:bg-zinc-800">
                            <img v-if="coverPreview" :src="coverPreview" class="w-full h-full object-cover" />
                            <svg v-else class="w-8 h-8 text-gray-300 dark:text-zinc-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M448 80c8.8 0 16 7.2 16 16V415.8l-5-6.5-136-176c-4.5-5.9-11.6-9.3-19-9.3s-14.4 3.4-19 9.3L202 340.7l-30.5-42.7C167 291.7 159.8 288 152 288s-15 3.7-19.5 10.1l-80 112L48 416.3l0-.3V96c0-8.8 7.2-16 16-16H448zM64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zm80 192a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/></svg>
                        </div>
                        <div class="flex-1">
                            <label class="cursor-pointer inline-block px-4 py-2 bg-gray-100 dark:bg-zinc-700 text-sm rounded-lg hover:bg-gray-200 dark:hover:bg-zinc-600 transition">
                                Seleccionar imagen
                                <input type="file" accept="image/*" class="hidden" @change="onCoverChange" />
                            </label>
                            <p class="mt-1 text-xs text-gray-400">JPG, PNG. Recomendado 400x600px</p>
                        </div>
                    </div>
                </div>

                <!-- Archivo PDF -->
                <div>
                    <InputLabel value="Archivo del libro (PDF)" />
                    <div class="mt-1">
                        <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-zinc-700 text-sm rounded-lg hover:bg-gray-200 dark:hover:bg-zinc-600 transition">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M0 64C0 28.7 28.7 0 64 0L224 0l0 128c0 17.7 14.3 32 32 32l128 0 0 288c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64zm384 64H256V0L384 128z"/></svg>
                            {{ fileNameDisplay || 'Seleccionar archivo PDF' }}
                            <input type="file" accept=".pdf" class="hidden" @change="onFileChange" />
                        </label>
                        <p class="mt-1 text-xs text-gray-400">PDF, máximo 50MB</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Descripción -->
        <div class="panel">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM169.8 165.3c7.9-22.3 29.1-37.3 52.8-37.3h58.3c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24V250.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1H222.6c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>
                Sinopsis / Descripción
            </h3>
            <textarea v-model="form.description" rows="5"
                class="form-textarea w-full"
                placeholder="Escribe una descripción o sinopsis del libro..."></textarea>
            <InputError :message="form.errors.description" />
        </div>

        <!-- Submit oculto para que Enter en inputs dispare el save -->
        <button type="submit" class="hidden"></button>
    </form>
</template>
