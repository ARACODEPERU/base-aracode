<script setup>
    import { ref, onMounted } from 'vue';
    import { useAppStore } from '@/stores/index';
    import { Link, usePage } from '@inertiajs/vue3';
    import IconCaretsDown from '@/Components/vristo/icon/icon-carets-down.vue';
    const xasset = assetUrl;
    const store = useAppStore();
    const page = usePage();

    // Configuración de colores personalizable
    const sidebarConfig = ref({
        primaryColor: '#6366f1', // Indigo por defecto
        secondaryColor: '#8b5cf6', // Violeta por defecto
        accentColor: '#ec4899', // Rosa por defecto
        hoverColor: '#f3f4f6', // Gris claro por defecto
        textColor: '#1f2937', // Gris oscuro por defecto
        textLight: '#6b7280', // Gris medio por defecto
        borderColor: '#e5e7eb', // Gris borde por defecto
        successColor: '#10b981', // Verde éxito
        warningColor: '#f59e0b', // Amarillo advertencia
        errorColor: '#ef4444', // Rojo error
    });

    // Estados para navegación
    const activeSection = ref('courses');
    const expandedSections = ref({});
    const isChatExpanded = ref(false);
    const teachers = ref([]);

    // Menú de navegación del estudiante
    const studentMenu = ref([
        {
            id: 'courses',
            title: 'Mis Cursos',
            icon: 'ri-book-2-line',
            route: route("aca_mycourses"),
            badge: null,
            color: 'primary',
            permissions: "aca_miscursos",
        },
        {
            id: 'attendance',
            title: 'Mis Asistencias',
            icon: 'ri-calendar-check-fill',
            route: route('aca_student_attendances'),
            badge: null,
            color: 'success'
        },
        {
            id: 'exams',
            title: 'Mis Examenes',
            icon: 'ri-file-list-3-line',
            route: route('aca_student_exam_search'),
            badge: null,
            color: 'secondary'
        },
        // {
        //     id: 'grades',
        //     title: 'Notas',
        //     icon: 'ri-bar-chart-line',
        //     route: route('dashboard'),
        //     badge: null,
        //     color: 'accent'
        // },
        {
            id: 'certificates',
            title: 'Mis Certificados',
            icon: 'ri-award-line',
            route: route('aca_student_certificates_all'),
            badge: null,
            color: 'success'
        },
        {
            id: 'chat',
            title: 'Chatbot de Asistencia',
            icon: 'ri-message-3-line',
            expandable: true,
            badge: 'new',
            color: 'warning',
            permissions: 'crm_chatbot'
        }
    ]);

    // Función para obtener clases de color dinámicas
    const getColorClasses = (colorType, type = 'bg') => {
        const colors = {
            primary: {
                bg: 'bg-indigo-500',
                hover: 'hover:bg-indigo-600',
                text: 'text-indigo-600',
                light: 'bg-indigo-50',
                border: 'border-indigo-200',
                dark: {
                    bg: 'dark:bg-indigo-600',
                    hover: 'dark:hover:bg-indigo-700',
                    text: 'dark:text-indigo-400',
                    light: 'dark:bg-indigo-900/20',
                    border: 'dark:border-indigo-700'
                }
            },
            secondary: {
                bg: 'bg-violet-500',
                hover: 'hover:bg-violet-600',
                text: 'text-violet-600',
                light: 'bg-violet-50',
                border: 'border-violet-200',
                dark: {
                    bg: 'dark:bg-violet-600',
                    hover: 'dark:hover:bg-violet-700',
                    text: 'dark:text-violet-400',
                    light: 'dark:bg-violet-900/20',
                    border: 'dark:border-violet-700'
                }
            },
            accent: {
                bg: 'bg-pink-500',
                hover: 'hover:bg-pink-600',
                text: 'text-pink-600',
                light: 'bg-pink-50',
                border: 'border-pink-200',
                dark: {
                    bg: 'dark:bg-pink-600',
                    hover: 'dark:hover:bg-pink-700',
                    text: 'dark:text-pink-400',
                    light: 'dark:bg-pink-900/20',
                    border: 'dark:border-pink-700'
                }
            },
            success: {
                bg: 'bg-emerald-500',
                hover: 'hover:bg-emerald-600',
                text: 'text-emerald-600',
                light: 'bg-emerald-50',
                border: 'border-emerald-200',
                dark: {
                    bg: 'dark:bg-emerald-600',
                    hover: 'dark:hover:bg-emerald-700',
                    text: 'dark:text-emerald-400',
                    light: 'dark:bg-emerald-900/20',
                    border: 'dark:border-emerald-700'
                }
            },
            warning: {
                bg: 'bg-amber-500',
                hover: 'hover:bg-amber-600',
                text: 'text-amber-600',
                light: 'bg-amber-50',
                border: 'border-amber-200',
                dark: {
                    bg: 'dark:bg-amber-600',
                    hover: 'dark:hover:bg-amber-700',
                    text: 'dark:text-amber-400',
                    light: 'dark:bg-amber-900/20',
                    border: 'dark:border-amber-700'
                }
            },
            error: {
                bg: 'bg-red-500',
                hover: 'hover:bg-red-600',
                text: 'text-red-600',
                light: 'bg-red-50',
                border: 'border-red-200',
                dark: {
                    bg: 'dark:bg-red-600',
                    hover: 'dark:hover:bg-red-700',
                    text: 'dark:text-red-400',
                    light: 'dark:bg-red-900/20',
                    border: 'dark:border-red-700'
                }
            }
        };

        const colorSet = colors[colorType] || colors.primary;
        let classes = colorSet[type] || colorSet.bg;

        if (store.theme === 'dark' && colorSet.dark) {
            classes += ' ' + colorSet.dark[type] || colorSet.dark.bg;
        }

        return classes;
    };

    // Función para actualizar colores dinámicamente
    const updateSidebarColors = (newColors) => {
        if (newColors) {
            Object.assign(sidebarConfig.value, newColors);
            // Guardar en localStorage para persistencia
            localStorage.setItem('studentSidebarColors', JSON.stringify(sidebarConfig.value));
        }
    };

    // Función para obtener colores desde props o localStorage
    const initializeColors = () => {
        // Intentar obtener colores desde props
        if (page.props.sidebarColors) {
            updateSidebarColors(page.props.sidebarColors);
        }
        // Intentar obtener desde localStorage
        else {
            const savedColors = localStorage.getItem('studentSidebarColors');
            if (savedColors) {
                try {
                    const parsedColors = JSON.parse(savedColors);
                    updateSidebarColors(parsedColors);
                } catch (e) {
                    console.warn('Error parsing saved sidebar colors:', e);
                }
            }
        }
    };

    // Función para manejar clicks en las opciones del menú
    const handleMenuClick = (menuItem) => {
        activeSection.value = menuItem.id;
        // Guardar en localStorage para persistencia
        localStorage.setItem('studentActiveSection', menuItem.id);
    };

    // Función para alternar secciones expandidas
    const toggleSection = (sectionId) => {
        expandedSections.value[sectionId] = !expandedSections.value[sectionId];
        localStorage.setItem('studentExpandedSections', JSON.stringify(expandedSections.value));
    };

    // Inicializar colores y estado al montar
    onMounted(() => {
        initializeColors();

        const savedSection = localStorage.getItem('studentActiveSection');
        if (savedSection) {
            activeSection.value = savedSection;
        }

        const savedExpanded = localStorage.getItem('studentExpandedSections');
        if (savedExpanded) {
            try {
                expandedSections.value = JSON.parse(savedExpanded);
            } catch (e) {
                expandedSections.value = {};
            }
        }

        const savedChatExpanded = localStorage.getItem('studentChatExpanded');
        if (savedChatExpanded === 'true') {
            isChatExpanded.value = true;
        }

        loadTeachers();
    });

    async function loadTeachers() {
        try {
            const response = await axios.get(route('crm_contacts_docents_chat'), {
                timeout: 0,
            });

            const data = response.data;

            teachers.value = data.docents.map(docente => ({
                id: docente.person.id,
                name: docente.person.full_name,
                image: docente.person.image
            }));

        } catch (error) {
            // console.error("Error cargando docentes:", error);
        }
    }

    const getTeacherImage = (image) => {
        if (!image) return `${xasset}img/default-avatar.png`;
        if (image.startsWith('/img/')) return `${xasset}${image}`;
        return `${xasset}storage/${image}`;
    };

    const toggleChat = () => {
        isChatExpanded.value = !isChatExpanded.value;
        localStorage.setItem('studentChatExpanded', isChatExpanded.value);
    };



</script>
<template>
    <div :class="{ 'dark text-white-dark': store.semidark }">
        <nav class="sidebar fixed min-h-screen h-full top-0 bottom-0 w-[300px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] z-50 transition-all duration-300">
            <div class="bg-white dark:bg-[#0e1726] h-full">
                <div class="flex justify-between items-center px-4 py-3">
                    <Link :href="route('dashboard')" class="main-logo flex items-center shrink-0">
                        <template v-if="store.theme === 'light' || store.theme === 'system'">
                            <img v-if="$page.props.company.isotipo == '/img/isotipo.png'" class="w-8 ml-[5px] flex-none" :src="xasset+$page.props.company.isotipo" alt="" />
                            <img v-else class="w-8 ml-[5px] flex-none" :src="xasset+'storage/'+$page.props.company.isotipo" alt="" />
                        </template>
                        <template v-if="store.theme === 'dark'">
                            <img v-if="$page.props.company.isotipo_negative == '/img/isotipo_negativo.png'" :src="`${xasset}/img/isotipo_negativo.png`" alt="Logo" class="w-8 ml-[5px] flex-none" />
                            <img v-else :src="`${xasset}storage/${$page.props.company.isotipo_negative}`" alt="Logo" class="w-8 ml-[5px] flex-none" />
                        </template>
                        <span class="text-2xl ltr:ml-1.5 rtl:mr-1.5 font-semibold align-middle lg:inline dark:text-white-light">{{ $page.props.company.name }}</span>
                    </Link>
                    <a
                        href="javascript:;"
                        class="collapse-icon w-8 h-8 rounded-full flex items-center hover:bg-gray-500/10 dark:hover:bg-dark-light/10 dark:text-white-light transition duration-300 rtl:rotate-180 hover:text-primary"
                        @click="store.toggleSidebar()"
                    >
                        <icon-carets-down class="m-auto rotate-90" />
                    </a>
                </div>
                <perfect-scrollbar
                    :options="{
                        swipeEasing: true,
                        wheelPropagation: false
                    }"
                    class="h-[calc(100vh-80px-60px)] relative"
                >
                    <ul class="relative font-semibold space-y-0.5 p-4 py-0">
                        <div v-show="store.menu != 'collapsible-vertical'" class="mb-6 p-4 mt-6 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700">
                            <div class="flex items-center space-x-3 justify-between">
                                <div class="w-12 h-12 rounded-full bg-indigo-500 dark:bg-indigo-600 flex items-center justify-center">
                                    <i class="ri-user-3-line text-white text-xl"></i>
                                </div>
                                <div class="ltr:ml-1.5 rtl:mr-1.5 align-middle lg:inline">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">¡Hola, {{ page.props.auth.user.name }}!</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Bienvenido a tu plataforma</p>
                                </div>
                            </div>
                        </div>
                        <template v-for="menuItem in studentMenu" :key="menuItem.id">
                            <li v-if="!menuItem.expandable" class="menu nav-item">
                                <Link
                                    :href="menuItem.route"
                                    @click="handleMenuClick(menuItem)"
                                    class="nav-link group px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200"
                                    :class="[
                                        activeSection === menuItem.id
                                            ? getColorClasses(menuItem.color, 'bg') + ' text-white shadow-lg'
                                            : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700'
                                    ]"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <i
                                            :class="[
                                                menuItem.icon,
                                                'text-xl',
                                                activeSection === menuItem.id
                                                    ? 'text-white'
                                                    : getColorClasses(menuItem.color, 'text')
                                            ]"
                                        ></i>
                                        <span class="ltr:pl-3 rtl:pr-3 font-medium">{{ menuItem.title }}</span>
                                        <div v-if="menuItem.badge"
                                             class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                             :class="[
                                                 menuItem.badge === 'new'
                                                     ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                                     : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
                                             ]">
                                            {{ menuItem.badge }}
                                        </div>
                                    </div>
                                </Link>
                            </li>

                            <li v-else class="menu nav-item">
                                <button
                                    @click="toggleChat"
                                    class="nav-link group w-full px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
                                    :class="{ 'bg-amber-50 dark:bg-amber-900/20': isChatExpanded }"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-3">
                                            <i :class="[menuItem.icon, 'text-xl text-amber-600 dark:text-amber-400']"></i>
                                            <span class="font-medium">{{ menuItem.title }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span v-if="menuItem.badge" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                {{ menuItem.badge }}
                                            </span>
                                            <svg
                                                class="w-4 h-4 transition-transform duration-200"
                                                :class="{ 'rotate-180': isChatExpanded }"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </div>
                                    </div>
                                </button>

                                <transition
                                    enter-active-class="transition-all duration-200 ease-out"
                                    enter-from-class="opacity-0 max-h-0"
                                    enter-to-class="opacity-100 max-h-96"
                                    leave-active-class="transition-all duration-200 ease-in"
                                    leave-from-class="opacity-100 max-h-96"
                                    leave-to-class="opacity-0 max-h-0"
                                >
                                    <div v-if="isChatExpanded && teachers.length > 0" class="overflow-hidden">
                                        <div class="mt-2 ml-4 space-y-1">
                                            <Link
                                                v-for="teacher in teachers"
                                                :key="teacher.id"
                                                :href="route('crm_application_ai_prompt', { cont: teacher.id })"
                                                class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-200 bg-gradient-to-r from-gray-50 to-transparent dark:from-gray-800 dark:to-transparent hover:from-indigo-50 hover:to-purple-50 dark:hover:from-indigo-900/30 dark:hover:to-purple-900/30 text-gray-700 dark:text-gray-300 hover:text-indigo-700 dark:hover:text-indigo-300"
                                            >
                                                <div class="relative">
                                                    <img
                                                        :src="getTeacherImage(teacher.image)"
                                                        :alt="teacher.name"
                                                        class="w-8 h-8 rounded-full object-cover ring-2 ring-white dark:ring-gray-700"
                                                    />
                                                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-white dark:border-gray-800 rounded-full"></span>
                                                </div>
                                                <span class="font-medium truncate">{{ teacher.name }}</span>
                                            </Link>
                                        </div>
                                    </div>
                                </transition>
                            </li>
                        </template>
                    </ul>

                </perfect-scrollbar>

                <!-- Footer fijo del sidebar - fuera del perfect-scrollbar -->
                <div class="absolute bottom-0 left-0 right-0 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-[#0e1726]">
                    <div class="px-4 py-3">
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>Plataforma Educativa</span>
                            <span>v3.0</span>
                        </div>
                    </div>
                </div>
            </div>

        </nav>

    </div>
</template>
<style lang="css">
.nav-item-student {
    margin-bottom: 0.25rem;
    display: flex;
    width: 100%;
    align-items: center;
    justify-content: space-between;
    overflow: hidden;
    white-space: nowrap;
    border-radius: 0.375rem;
    padding: 0.625rem;
}
</style>
