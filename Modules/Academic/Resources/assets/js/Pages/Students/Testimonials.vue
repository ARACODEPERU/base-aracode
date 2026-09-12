<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { Link, useForm } from '@inertiajs/vue3';
    import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
    import Swal2 from 'sweetalert2';
    import ModalLargeX from '@/Components/ModalLargeX.vue';
    import InputError from '@/Components/InputError.vue';

    const props = defineProps({
        courses: {
            type: Array,
            default: () => [],
        },
        myTestimonies: {
            type: Array,
            default: () => [],
        },
        consentText: {
            type: String,
            default: '',
        },
        editWindowHours: {
            type: Number,
            default: 15,
        },
    });

    const baseUrl = assetUrl;

    const modalOpen = ref(false);
    const editing = ref(null);
    const photoPreview = ref(null);
    const now = ref(Date.now());
    let clock = null;

    onMounted(() => {
        clock = setInterval(() => {
            now.value = Date.now();
        }, 30000);
    });

    onBeforeUnmount(() => {
        if (clock) clearInterval(clock);
    });

    const form = useForm({
        course_id: null,
        rating: 0,
        comment: '',
        author_role: '',
        photo: null,
        consent: false,
    });

    const getImage = (path) => {
        if (!path) return null;
        if (String(path).startsWith('http')) return path;
        return baseUrl + 'storage/' + path;
    };

    const initials = (name) => {
        if (!name) return '?';
        return name
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part.charAt(0).toUpperCase())
            .join('');
    };

    const courseImage = (course) => getImage(course.image);

    const hasTestimonies = computed(() => (props.myTestimonies || []).length > 0);

    const completedCount = computed(() => (props.courses || []).length);

    const statusLabel = (testimony) => {
        if (!testimony) return null;
        return testimony.approval_status === 'approved' ? 'Publicado' : 'En revisión';
    };

    /**
     * El alumno no ve el estado de un testimonio rechazado: se trata como
     * "sin testimonio" para que pueda volver a enviarlo.
     */
    const visibleTestimony = (course) => {
        const testimony = course ? course.testimony : null;
        if (!testimony || testimony.is_rejected) return null;
        return testimony;
    };

    const statusClass = (testimony) => {
        return testimony.approval_status === 'approved'
            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
    };

    /**
     * Tiempo restante (en texto) para editar un testimonio dentro de la ventana de 15 horas.
     */
    const remainingTime = (testimony) => {
        if (!testimony || !testimony.editable_until) return null;
        const diff = new Date(testimony.editable_until).getTime() - now.value;
        if (diff <= 0) return null;

        const totalMinutes = Math.floor(diff / 60000);
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;

        return hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;
    };

    const openCreate = (course) => {
        editing.value = null;
        form.reset();
        form.clearErrors();
        form.course_id = course.id;
        form.rating = 5;
        form.comment = '';
        form.author_role = '';
        form.photo = null;
        photoPreview.value = courseImage(course);
        modalOpen.value = true;
    };

    const openEdit = (course) => {
        const testimony = course.testimony;
        editing.value = testimony;
        form.reset();
        form.clearErrors();
        form.course_id = course.id;
        form.rating = testimony.rating || 5;
        form.comment = testimony.comment || '';
        form.author_role = testimony.author_role || '';
        form.photo = null;
        photoPreview.value = getImage(testimony.photo) || courseImage(course);
        modalOpen.value = true;
    };

    const closeModal = () => {
        if (form.processing) return;
        modalOpen.value = false;
        editing.value = null;
        photoPreview.value = null;
        form.reset();
        form.clearErrors();
    };

    const setRating = (value) => {
        form.rating = value;
    };

    const onPhotoChange = (event) => {
        const file = event.target.files[0] || null;
        form.photo = file;
        photoPreview.value = file ? URL.createObjectURL(file) : null;
    };

    /**
     * El guardado exige aceptar expresamente la publicacion del testimonio y la imagen.
     * Si el alumno elige "Modificar algo" se mantiene el modal abierto con lo escrito.
     */
    const askConsentThenSave = async () => {
        form.clearErrors();

        if (!form.rating || form.rating < 1) {
            Swal2.fire({
                icon: 'warning',
                title: 'Falta tu calificación',
                text: 'Selecciona de 1 a 5 estrellas.',
                confirmButtonText: 'Entendido',
            });
            return;
        }

        if (!form.comment || form.comment.trim().length < 20) {
            Swal2.fire({
                icon: 'warning',
                title: 'Cuéntanos un poco más',
                text: 'El comentario debe tener al menos 20 caracteres.',
                confirmButtonText: 'Entendido',
            });
            return;
        }

        const result = await Swal2.fire({
            title: 'Autorización para publicar',
            html: `<p class="text-left text-sm leading-relaxed">${escapeHtml(
                props.consentText
            ).replace(/\n/g, '<br>')}</p>`,
            icon: 'question',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Aceptar y guardar',
            denyButtonText: 'Modificar algo',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#3085d6',
            denyButtonColor: '#6b7280',
            cancelButtonColor: '#d33',
            reverseButtons: true,
            allowOutsideClick: false,
        });

        if (result.isDenied) {
            // "Modificar algo": se queda en el formulario con todo lo escrito.
            return;
        }

        if (!result.isConfirmed) {
            return;
        }

        save();
    };

    const save = () => {
        const wasEditing = !!editing.value;

        form.consent = true;
        form.transform((data) => ({
            ...data,
            consent: true,
            _method: wasEditing ? 'put' : 'post',
        }));

        const url = editing.value
            ? route('aca_student_testimonials_update', editing.value.id)
            : route('aca_student_testimonials_store');

        form.post(url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                modalOpen.value = false;
                editing.value = null;
                photoPreview.value = null;
                form.reset();
                Swal2.fire({
                    icon: 'success',
                    title: wasEditing ? 'Testimonio actualizado' : '¡Gracias por tu testimonio!',
                    text: 'Tu testimonio fue enviado y será revisado antes de publicarse.',
                    confirmButtonText: 'Listo',
                });
            },
            onError: () => {
                Swal2.fire({
                    icon: 'error',
                    title: 'Revisa el formulario',
                    text: 'Hubo un problema al enviar tu testimonio. Revisa los campos marcados.',
                    confirmButtonText: 'Entendido',
                });
            },
        });
    };

    const deleteTestimony = (course) => {
        const testimony = course.testimony;
        if (!testimony) return;

        Swal2.fire({
            title: '¿Eliminar tu testimonio?',
            text: 'Podrás volver a registrarlo mientras el curso siga culminado.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return axios
                    .delete(route('aca_student_testimonials_destroy', testimony.id))
                    .then((res) => res)
                    .catch((error) => {
                        Swal2.showValidationMessage('No se pudo eliminar el testimonio.');
                    });
            },
            allowOutsideClick: () => !Swal2.isLoading(),
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.reload();
            }
        });
    };

    const escapeHtml = (value) => {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };
</script>

<template>
    <AppLayout title="Testimonios">
        <div class="max-w-screen-2xl mx-auto p-4 md:p-6 2xl:p-10">
            <!-- Encabezado -->
            <div class="rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white p-6 md:p-8 mb-6 shadow-lg">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold mb-2">Testimonios</h1>
                        <p class="text-white/80 max-w-3xl">
                            Comparte tu experiencia con los cursos que ya culminaste. Tu testimonio nos ayuda a mejorar
                            y motiva a otros profesionales a dar el siguiente paso.
                        </p>
                    </div>
                    <div class="flex-shrink-0 bg-white/15 rounded-xl px-5 py-4 text-center">
                        <p class="text-3xl font-bold">{{ completedCount }}</p>
                        <p class="text-xs uppercase tracking-wide text-white/80">Cursos culminados</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-6 text-sm">
                    <div class="flex items-start gap-2 bg-white/10 rounded-lg p-3">
                        <i class="ri-checkbox-circle-line text-lg mt-0.5"></i>
                        <span>Debes haber revisado el <strong>100%</strong> del curso.</span>
                    </div>
                    <div class="flex items-start gap-2 bg-white/10 rounded-lg p-3">
                        <i class="ri-award-line text-lg mt-0.5"></i>
                        <span>En Programas de Especialización se requiere el <strong>certificado emitido</strong>.</span>
                    </div>
                    <div class="flex items-start gap-2 bg-white/10 rounded-lg p-3">
                        <i class="ri-time-line text-lg mt-0.5"></i>
                        <span>Podrás editar tu testimonio durante <strong>{{ editWindowHours }} horas</strong>.</span>
                    </div>
                </div>
            </div>

            <!-- Cursos culminados -->
            <div class="mb-10">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-1">Cursos que ya culminaste</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Desde aquí puedes dejar tu testimonio o revisar el que ya enviaste.
                </p>

                <div v-if="completedCount === 0"
                     class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-10 text-center">
                    <i class="ri-chat-quote-line text-4xl text-gray-400"></i>
                    <p class="mt-3 font-semibold text-gray-700 dark:text-gray-200">
                        Aún no tienes cursos culminados al 100%.
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Termina todas las actividades de un curso y podrás dejar tu testimonio.
                    </p>
                    <Link :href="route('aca_mycourses')"
                          class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                        <i class="ri-book-2-line"></i> Ir a Mis Cursos
                    </Link>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    <div v-for="course in courses"
                         :key="course.id"
                         class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex flex-col">
                        <div class="h-40 bg-gray-100 dark:bg-gray-700 relative">
                            <img v-if="courseImage(course)"
                                 :src="courseImage(course)"
                                 :alt="course.description"
                                 class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <i class="ri-book-2-line text-4xl text-gray-400"></i>
                            </div>
                            <span v-if="course.is_specialization"
                                  class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-xs font-semibold bg-violet-600 text-white">
                                Especialización
                            </span>
                        </div>

                        <div class="p-4 flex-1 flex flex-col">
                            <p class="text-xs uppercase tracking-wide text-indigo-600 dark:text-indigo-400 font-semibold mb-1">
                                {{ course.category || 'Curso' }}
                            </p>
                            <h3 class="font-bold text-gray-800 dark:text-white leading-snug mb-2">
                                {{ course.description }}
                            </h3>

                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-3">
                                <i class="ri-checkbox-circle-fill text-emerald-500"></i>
                                <span>{{ course.progress }}% completado</span>
                                <template v-if="course.is_specialization && course.certificate_exists">
                                    <span class="mx-1">·</span>
                                    <i class="ri-award-fill text-amber-500"></i>
                                    <span>Certificado</span>
                                </template>
                            </div>

                            <!-- Con testimonio -->
                            <div v-if="visibleTestimony(course)" class="mt-auto">
                                <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-3 mb-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="text-amber-400 text-sm">
                                            <i v-for="n in 5"
                                               :key="n"
                                               :class="n <= course.testimony.rating ? 'ri-star-fill' : 'ri-star-line'"></i>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                                              :class="statusClass(course.testimony)">
                                            {{ statusLabel(course.testimony) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-3">
                                        {{ course.testimony.comment }}
                                    </p>
                                </div>

                                <div v-if="course.testimony.can_edit" class="flex items-center gap-2">
                                    <button type="button"
                                            @click="openEdit(course)"
                                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                                        <i class="ri-edit-line"></i> Editar
                                    </button>
                                    <button type="button"
                                            @click="deleteTestimony(course)"
                                            class="inline-flex items-center justify-center px-3 py-2 rounded-lg border border-red-200 text-red-600 text-sm font-semibold hover:bg-red-50 dark:border-red-800 dark:text-red-400">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                                <p v-else-if="course.testimony.approval_status === 'approved'"
                                   class="text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                    <i class="ri-lock-2-line"></i>
                                    Ya fue publicado: no puede modificarse
                                </p>
                                <p v-else-if="remainingTime(course.testimony)"
                                   class="text-xs text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                    <i class="ri-timer-line"></i>
                                    Te quedan {{ remainingTime(course.testimony) }} para editarlo
                                </p>
                                <p v-else
                                   class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                    <i class="ri-lock-2-line"></i>
                                    Plazo de edición finalizado
                                </p>
                            </div>

                            <!-- Sin testimonio -->
                            <button v-else
                                    type="button"
                                    @click="openCreate(course)"
                                    class="mt-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                                <i class="ri-chat-quote-line"></i> Dejar mi testimonio
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mis testimonios -->
            <div v-if="hasTestimonies">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Mis testimonios</h2>
                <div class="space-y-4">
                    <div v-for="testimony in myTestimonies"
                         :key="testimony.id"
                         class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full overflow-hidden bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0">
                                    <img v-if="getImage(testimony.photo)"
                                         :src="getImage(testimony.photo)"
                                         alt="Foto del testimonio"
                                         class="w-full h-full object-cover" />
                                    <span v-else class="text-sm font-bold text-indigo-600 dark:text-indigo-300">
                                        {{ initials(testimony.author_name) }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800 dark:text-white">
                                        {{ testimony.author_name || 'Tú' }}
                                    </p>
                                    <div class="text-amber-400 text-sm">
                                        <i v-for="n in 5"
                                           :key="n"
                                           :class="n <= testimony.rating ? 'ri-star-fill' : 'ri-star-line'"></i>
                                    </div>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap"
                                  :class="statusClass(testimony)">
                                {{ statusLabel(testimony) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-3">
                            {{ testimony.comment }}
                        </p>
                        <p v-if="testimony.approval_status === 'approved'"
                           class="text-xs text-emerald-600 dark:text-emerald-400 mt-3 flex items-center gap-1">
                            <i class="ri-checkbox-circle-line"></i>
                            Publicado: ya no puede modificarse
                        </p>
                        <p v-else-if="testimony.can_edit && remainingTime(testimony)"
                           class="text-xs text-amber-600 dark:text-amber-400 mt-3 flex items-center gap-1">
                            <i class="ri-timer-line"></i>
                            Puedes editarlo durante {{ remainingTime(testimony) }} más
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para dejar / editar testimonio -->
        <ModalLargeX :show="modalOpen"
                     :on-close="closeModal"
                     :loading="form.processing"
                     loading-text="Guardando tu testimonio, espera un momento...">
            <template #title>
                {{ editing ? 'Editar mi testimonio' : 'Dejar mi testimonio' }}
            </template>
            <template #message>
                Cuéntanos tu experiencia con el curso. Al guardar se te pedirá autorizar su publicación.
            </template>

            <template #content>
                <div class="space-y-5">
                    <div class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-3 flex items-center gap-3">
                        <div class="w-14 h-14 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0">
                            <img v-if="photoPreview"
                                 :src="photoPreview"
                                 alt="Vista previa"
                                 class="w-full h-full object-cover" />
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Curso</p>
                            <p class="font-semibold text-gray-800 dark:text-white">
                                {{
                                    (courses.find((c) => c.id === form.course_id) || {}).description || ''
                                }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                            Tu calificación
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

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                            Tu cargo o profesión <span class="font-normal text-gray-400">(opcional)</span>
                        </label>
                        <input v-model="form.author_role"
                               type="text"
                               maxlength="255"
                               placeholder="Ej. Contador Público, Analista Financiero..."
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                        <p class="text-xs text-gray-400 mt-1">Se mostrará junto a tu nombre en la página pública.</p>
                        <InputError :message="form.errors.author_role" class="mt-2" />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                            Tu comentario
                        </label>
                        <textarea v-model="form.comment"
                                  rows="5"
                                  maxlength="2000"
                                  placeholder="¿Qué te pareció el curso? ¿Qué te aportó profesionalmente?"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <span>Mínimo 20 caracteres</span>
                            <span>{{ (form.comment || '').length }}/2000</span>
                        </div>
                        <InputError :message="form.errors.comment" class="mt-2" />
                    </div>

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
                    </div>

                    <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3 text-xs text-amber-800 dark:text-amber-200">
                        <i class="ri-information-line"></i>
                        Al guardar se te pedirá autorizar la publicación de tu testimonio en nuestra página web y redes
                        sociales, incluida la imagen que compartas.
                    </div>
                </div>
            </template>

            <template #buttons>
                <button type="button"
                        @click="askConsentThenSave"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700 disabled:opacity-60">
                    <i class="ri-save-line"></i>
                    {{ editing ? 'Guardar cambios' : 'Guardar testimonio' }}
                </button>
            </template>
        </ModalLargeX>
    </AppLayout>
</template>
