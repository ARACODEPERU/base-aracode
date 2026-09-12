<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { Link, useForm } from '@inertiajs/vue3';
    import { computed, ref } from 'vue';
    import InputError from '@/Components/InputError.vue';
    import IaCorrectionMenu from '../../Components/IaCorrectionMenu.vue';
    import Swal2 from 'sweetalert2';

    const props = defineProps({
        courses: {
            type: Array,
            default: () => [],
        },
    });

    const form = useForm({
        course_id: null,
        author_name: '',
        author_role: '',
        rating: 5,
        comment: '',
        photo: null,
        publish: true,
    });

    const search = ref('');
    const showList = ref(false);
    const photoPreview = ref(null);
    const iaLoading = ref(false);

    const filteredCourses = computed(() => {
        const term = (search.value || '').toLowerCase().trim();
        const list = term
            ? props.courses.filter((course) => {
                  const haystack = `${course.description} ${course.category || ''}`.toLowerCase();
                  return haystack.includes(term);
              })
            : props.courses;

        return list.slice(0, 50);
    });

    const selectedCourse = computed(() =>
        props.courses.find((course) => course.id === form.course_id) || null
    );

    const selectCourse = (course) => {
        form.course_id = course.id;
        search.value = course.description;
        showList.value = false;
    };

    const onPhotoChange = (event) => {
        const file = event.target.files[0] || null;
        form.photo = file;
        photoPreview.value = file ? URL.createObjectURL(file) : null;
    };

    const setRating = (value) => {
        form.rating = value;
    };

    /**
     * Corrige el comentario con la IA del sistema (redacción y ortografía o
     * solo ortografía) antes de guardar.
     */
    const correctWithIa = (mode) => {
        if (!form.comment || form.comment.trim().length === 0) {
            Swal2.fire({
                icon: 'warning',
                title: 'Escribe primero el comentario',
                text: 'Necesitamos un texto para poder corregirlo.',
                confirmButtonText: 'Entendido',
            });
            return;
        }

        iaLoading.value = true;

        axios
            .post(route('cms_testimonies_ia_correct'), { text: form.comment, mode: mode })
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

                form.comment = response.data.corrected;
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

    const submit = () => {
        if (!form.course_id) {
            Swal2.fire({
                icon: 'warning',
                title: 'Falta el curso',
                text: 'Selecciona el curso al que corresponde el testimonio.',
                confirmButtonText: 'Entendido',
            });
            return;
        }

        if (!form.author_name || form.author_name.trim().length < 3) {
            Swal2.fire({
                icon: 'warning',
                title: 'Falta el autor',
                text: 'Escribe el nombre que se mostrará como autor del testimonio.',
                confirmButtonText: 'Entendido',
            });
            return;
        }

        if (!form.comment || form.comment.trim().length < 20) {
            Swal2.fire({
                icon: 'warning',
                title: 'Comentario muy corto',
                text: 'El comentario debe tener al menos 20 caracteres.',
                confirmButtonText: 'Entendido',
            });
            return;
        }

        form.post(route('cms_testimonies_students_store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                Swal2.fire({
                    icon: 'success',
                    title: 'Testimonio registrado',
                    text: 'El testimonio quedó guardado correctamente.',
                    timer: 1800,
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
    <AppLayout title="Nuevo testimonio de alumno">
        <div class="max-w-screen-2xl mx-auto p-4 md:p-6 2xl:p-10">
            <nav class="flex px-4 py-3 border border-stroke text-gray-700 mb-4 bg-gray-50 dark:bg-gray-800 dark:border-gray-700" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <Link :href="route('dashboard')" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            Inicio
                        </Link>
                    </li>
                    <li><span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">CMS</span></li>
                    <li>
                        <Link :href="route('cms_testimonies_list', { source: 'student' })"
                              class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                            Testimonios de alumnos
                        </Link>
                    </li>
                    <li aria-current="page">
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Nuevo</span>
                    </li>
                </ol>
            </nav>

            <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark p-5 md:p-7">
                <div class="mb-6">
                    <h1 class="text-xl font-bold text-gray-800 dark:text-white">Nuevo testimonio de alumno</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Este testimonio se muestra como si lo hubiera dejado un alumno, pero
                        <strong>no queda vinculado a ninguna persona real</strong> de la base de datos. Puedes elegir
                        cualquier curso del sistema y escribir el nombre del autor.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Curso -->
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                            Curso <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input v-model="search"
                                   @focus="showList = true"
                                   @input="form.course_id = null; showList = true"
                                   type="text"
                                   placeholder="Busca por nombre de curso o categoría..."
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                            <div v-if="showList"
                                 class="absolute z-20 mt-1 w-full max-h-72 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg">
                                <button v-for="course in filteredCourses"
                                        :key="course.id"
                                        type="button"
                                        @click="selectCourse(course)"
                                        class="w-full text-left px-4 py-2.5 text-sm hover:bg-indigo-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                    <span class="block font-medium text-gray-800 dark:text-gray-100">{{ course.description }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                                        {{ course.category || 'Sin categoría' }}<template v-if="course.type_description"> · {{ course.type_description }}</template>
                                    </span>
                                </button>
                                <div v-if="filteredCourses.length === 0" class="px-4 py-3 text-sm text-gray-500">
                                    No se encontraron cursos con ese criterio.
                                </div>
                            </div>
                        </div>
                        <p v-if="selectedCourse" class="text-xs text-emerald-600 dark:text-emerald-400 mt-2">
                            <i class="ri-checkbox-circle-line"></i> Curso seleccionado: {{ selectedCourse.description }}
                        </p>
                        <InputError :message="form.errors.course_id" class="mt-2" />
                    </div>

                    <!-- Autor -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                            Nombre del autor <span class="text-red-500">*</span>
                        </label>
                        <input v-model="form.author_name"
                               type="text"
                               maxlength="255"
                               placeholder="Ej. María Fernández Ríos"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                        <p class="text-xs text-gray-400 mt-1">Es solo texto: no se busca ni crea ninguna persona en el sistema.</p>
                        <InputError :message="form.errors.author_name" class="mt-2" />
                    </div>

                    <!-- Cargo o profesion -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                            Cargo o profesión <span class="font-normal text-gray-400">(opcional)</span>
                        </label>
                        <input v-model="form.author_role"
                               type="text"
                               maxlength="255"
                               placeholder="Ej. Contador Público"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                        <p class="text-xs text-gray-400 mt-1">Se muestra junto al nombre en la página pública.</p>
                        <InputError :message="form.errors.author_role" class="mt-2" />
                    </div>

                    <!-- Estrellas -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                            Calificación <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <button v-for="n in 5"
                                    :key="n"
                                    type="button"
                                    @click="setRating(n)"
                                    class="text-3xl transition-transform hover:scale-110"
                                    :class="n <= form.rating ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600'">
                                <i :class="n <= form.rating ? 'ri-star-fill' : 'ri-star-line'"></i>
                            </button>
                            <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">{{ form.rating }}/5</span>
                        </div>
                        <InputError :message="form.errors.rating" class="mt-2" />
                    </div>

                    <!-- Comentario -->
                    <div class="lg:col-span-2">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Comentario <span class="text-red-500">*</span>
                            </label>
                            <IaCorrectionMenu :loading="iaLoading" @select="correctWithIa" />
                        </div>
                        <textarea v-model="form.comment"
                                  rows="5"
                                  maxlength="2000"
                                  placeholder="Texto del testimonio tal como se mostrará en la página pública..."
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <span>Mínimo 20 caracteres</span>
                            <span>{{ (form.comment || '').length }}/2000</span>
                        </div>
                        <InputError :message="form.errors.comment" class="mt-2" />
                    </div>

                    <!-- Foto -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                            Foto <span class="font-normal text-gray-400">(opcional)</span>
                        </label>
                        <input type="file"
                               accept="image/jpeg,image/png,image/webp"
                               @change="onPhotoChange"
                               class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-200" />
                        <p class="text-xs text-gray-400 mt-1">JPG, PNG o WEBP · máx. 2 MB</p>
                        <InputError :message="form.errors.photo" class="mt-2" />
                        <div v-if="photoPreview" class="mt-3">
                            <img :src="photoPreview" alt="Vista previa" class="w-28 h-28 object-cover rounded-lg border border-gray-200 dark:border-gray-600" />
                        </div>
                    </div>

                    <!-- Publicar -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                            Visibilidad
                        </label>
                        <label class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-600 p-3 cursor-pointer">
                            <input type="checkbox"
                                   v-model="form.publish"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-200">
                                Publicar inmediatamente en <strong>/testimonios</strong>
                            </span>
                        </label>
                        <p class="text-xs text-gray-400 mt-1">
                            Si lo desmarcas, queda como <strong>pendiente</strong> para aprobarlo luego desde la lista.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 mt-8 pt-5 border-t border-gray-200 dark:border-gray-700">
                    <Link :href="route('cms_testimonies_list', { source: 'student' })"
                          class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-600 text-sm font-semibold hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                        Cancelar
                    </Link>
                    <button type="button"
                            @click="submit"
                            :disabled="form.processing || iaLoading"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-60">
                        <i class="ri-save-line"></i>
                        {{ form.processing ? 'Guardando...' : 'Guardar testimonio' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
