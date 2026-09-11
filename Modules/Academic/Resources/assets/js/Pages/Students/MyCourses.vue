<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import { useAppStore } from '@/stores/index';

const store = useAppStore();

const props = defineProps({
    courses: {
        type: Object,
        default: () => ({}),
    }
});

// Variables de configuración de colores y tamaños
const config = {
    bgColor: '223333', // Color dominante principal
    textColor: 'ffffff', // Color de texto principal
    accentColor: '4a90e2', // Color de acento
    successColor: '52c41a', // Color de éxito
    warningColor: 'faad14', // Color de advertencia
    errorColor: 'f5222d', // Color de error
    sidebarWidth: '320px', // Ancho del sidebar
    headerHeight: '80px', // Altura del header interno
    borderRadius: '12px', // Radio de bordes
    spacing: '16px', // Espaciado general
    fontSize: {
        small: '14px',
        medium: '16px',
        large: '18px',
        xl: '24px'
    },
    // Colores para tema dark
    dark: {
        bg: '1a1a1a',
        surface: '2d2d2d',
        text: 'e5e5e5',
        border: '404040'
    }
};

const isDarkMode = ref(store.theme === 'dark');

// Escuchar cambios en el tema para actualizar la constante
watch(() => store.theme, (newTheme) => {
    isDarkMode.value = newTheme === 'dark';
});

// Estado del componente
const selectedModule = ref(0);
const selectedClass = ref(0);
const isSidebarCollapsed = ref(false);
const showExamModal = ref(false);
const currentExam = ref(null);

const showComments = ref(false);
const newComment = ref('');
const comments = ref([
    {
        id: 1,
        author: "María García",
        avatar: "👩‍💻",
        content: "Excelente explicación del uso de flexbox, muy clara y concisa.",
        timestamp: "2024-01-15T10:30:00",
        likes: 5,
        classId: 1
    },
    {
        id: 2,
        author: "Juan Pérez",
        avatar: "👨‍🎓",
        content: "¿Alguien podría recomendar recursos adicionales sobre CSS Grid?",
        timestamp: "2024-01-15T14:22:00",
        likes: 2,
        classId: 3
    }
]);

// Datos estáticos de ejemplo del curso
const courseData = ref({
    title: "Desarrollo Web Completo",
    description: "Aprende desarrollo web desde cero hasta nivel avanzado",
    instructor: "Carlos Rodríguez",
    progress: 65,
    duration: "40 horas",
    modules: [
        {
            id: 1,
            title: "Fundamentos de HTML y CSS",
            icon: "📚",
            classes: [
                {
                    id: 1,
                    title: "Introducción al HTML",
                    type: "text",
                    duration: "15 min",
                    completed: true,
                    content: {
                        text: "HTML (HyperText Markup Language) es el lenguaje de marcado estándar para crear páginas web. Describe la estructura de una página web y consiste en una serie de elementos que le dicen al navegador cómo mostrar el contenido. HTML5 es la última versión y trae muchas mejoras como elementos semánticos, soporte multimedia nativo y capacidades offline.",
                        video: null,
                        files: [],
                        externalLinks: []
                    }
                },
                {
                    id: 2,
                    title: "CSS Básico y Selectores",
                    type: "video",
                    duration: "25 min",
                    completed: true,
                    content: {
                        text: "CSS (Cascading Style Sheets) es el lenguaje usado para describir la presentación de documentos escritos en HTML. Aprende sobre selectores, propiedades, y cómo crear diseños atractivos y responsivos.",
                        video: "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
                        files: [
                            { name: "guia-css-completa.pdf", size: "2.5 MB", url: "#" },
                            { name: "ejercicios-selectores.html", size: "15 KB", url: "#" }
                        ],
                        externalLinks: [
                            { title: "MDN CSS Reference", url: "https://developer.mozilla.org/en-US/docs/Web/CSS" }
                        ]
                    }
                },
                {
                    id: 3,
                    title: "Flexbox y CSS Grid",
                    type: "mixed",
                    duration: "30 min",
                    completed: false,
                    content: {
                        text: "Aprende a usar los sistemas de layout modernos de CSS. Flexbox es perfecto para componentes unidimensionales, mientras que CSS Grid excela en layouts bidimensionales. Combinar ambos te dará control total sobre el diseño.",
                        video: null,
                        files: [
                            { name: "ejercicios-flexbox.zip", size: "1.2 MB", url: "#" },
                            { name: "cheat-sheet-grid.pdf", size: "500 KB", url: "#" },
                            { name: "plantillas-grid.html", size: "25 KB", url: "#" }
                        ],
                        externalLinks: [
                            { title: "CSS Tricks Flexbox Guide", url: "https://css-tricks.com/snippets/css/a-guide-to-flexbox/" },
                            { title: "CSS Grid Garden", url: "https://cssgridgarden.com/" }
                        ]
                    }
                }
            ],
            exam: {
                type: "module",
                questions: 10,
                duration: "30 min",
                available: true
            }
        },
        {
            id: 2,
            title: "JavaScript Fundamentals",
            icon: "⚡",
            classes: [
                {
                    id: 4,
                    title: "Variables y Tipos de Datos",
                    type: "text",
                    duration: "20 min",
                    completed: false,
                    content: {
                        text: "JavaScript es un lenguaje de programación versátil que te permite implementar funciones complejas en páginas web. Aprende sobre let, const, var, y los diferentes tipos de datos como strings, numbers, booleans, objects y arrays. Entiende el concepto de hoisting y el scope de variables.",
                        video: null,
                        files: [],
                        externalLinks: [
                            { title: "JavaScript.info - Variables", url: "https://javascript.info/variables" }
                        ]
                    }
                },
                {
                    id: 5,
                    title: "Funciones y Objetos",
                    type: "video",
                    duration: "35 min",
                    completed: false,
                    content: {
                        text: "Las funciones son bloques de código reutilizables que realizan una tarea específica. Aprende sobre funciones declarativas, de flecha, anónimas y cómo trabajar con objetos en JavaScript.",
                        video: "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4",
                        files: [
                            { name: "ejercicios-funciones.js", size: "3 KB", url: "#" }
                        ],
                        externalLinks: []
                    }
                },
                {
                    id: 6,
                    title: "Arrays y Métodos Array",
                    type: "mixed",
                    duration: "25 min",
                    completed: false,
                    content: {
                        text: "Los arrays son estructuras de datos fundamentales en JavaScript. Domina métodos como map, filter, reduce, forEach, y cómo manipular arrays eficientemente.",
                        video: null,
                        files: [
                            { name: "array-methods-cheatsheet.pdf", size: "800 KB", url: "#" }
                        ],
                        externalLinks: []
                    }
                }
            ],
            exam: {
                type: "module",
                questions: 15,
                duration: "45 min",
                available: false
            }
        },
        {
            id: 3,
            title: "React y Componentes",
            icon: "⚛️",
            classes: [
                {
                    id: 7,
                    title: "Introducción a React",
                    type: "text",
                    duration: "30 min",
                    completed: false,
                    content: {
                        text: "React es una biblioteca de JavaScript para construir interfaces de usuario. Aprende sobre JSX, componentes, props, y el ciclo de vida de los componentes.",
                        video: null,
                        files: [],
                        externalLinks: [
                            { title: "Documentación Oficial de React", url: "https://react.dev/" }
                        ]
                    }
                }
            ],
            exam: {
                type: "module",
                questions: 12,
                duration: "40 min",
                available: false
            }
        }
    ],
    finalExam: {
        type: "course",
        questions: 50,
        duration: "120 min",
        available: false,
        title: "Examen Final del Curso"
    }
});

const currentContent = ref(null);
const sidebarScroll = ref(null);

// Referencia para el ancho de ventana (solución para SSR)
const windowWidth = ref(0);

let perfectScrollbarInstance = null;

onMounted(() => {
    selectClass(0, 0);

    // Establecer el ancho de ventana (solo en cliente)
    windowWidth.value = window.innerWidth;

    // Forzar inicialización con múltiples intentos
    const initScrollbar = () => {
        if (sidebarScroll.value && typeof window.PerfectScrollbar !== 'undefined') {
            try {
                perfectScrollbarInstance = new PerfectScrollbar(sidebarScroll.value, {
                    wheelSpeed: 1,
                    wheelPropagation: false,
                    swipeEasing: true,
                    minScrollbarLength: 30,
                    maxScrollbarLength: 100,
                    scrollingThreshold: 1000,
                    suppressScrollX: true,
                    handlers: ['click-rail', 'drag-thumb', 'keyboard', 'wheel', 'touch']
                });
                
                // Forzar actualización
                setTimeout(() => {
                    if (perfectScrollbarInstance) {
                        perfectScrollbarInstance.update();
                    }
                }, 100);
            } catch (error) {
                console.log('Error inicializando perfect-scrollbar:', error);
            }
        }
    };

    // Múltiples intentos para asegurar que el DOM esté listo
    setTimeout(initScrollbar, 100);
    setTimeout(initScrollbar, 300);
    setTimeout(initScrollbar, 500);

    // Cleanup del listener
    onUnmounted(() => {
        if (perfectScrollbarInstance) {
            perfectScrollbarInstance.destroy();
        }
    });
});

function selectClass(moduleIndex, classIndex) {
    selectedModule.value = moduleIndex;
    selectedClass.value = classIndex;
    currentContent.value = courseData.value.modules[moduleIndex].classes[classIndex];
}

// La función toggleSidebar ya no se necesita pero se mantiene por compatibilidad
function toggleSidebar() {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
}

function openExam(exam) {
    currentExam.value = exam;
    showExamModal.value = true;
}

function getCompletionIcon(completed) {
    return completed ? '✅' : '⭕';
}

function getContentIcon(type) {
    const icons = {
        text: '📄',
        video: '🎥',
        mixed: '📎',
        quiz: '📝'
    };
    return icons[type] || '📄';
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Funciones eliminadas (toggleTheme y toggleSidebar ya no se usan)

function toggleComments() {
    showComments.value = !showComments.value;
}

function addComment() {
    if (!newComment.value.trim()) return;

    const comment = {
        id: Date.now(),
        author: "Tú",
        avatar: "🎓",
        content: newComment.value,
        timestamp: new Date().toISOString(),
        likes: 0,
        classId: currentContent.value?.id || 0
    };

    comments.value.unshift(comment);
    newComment.value = '';
}

function getClassComments() {
    return comments.value.filter(comment => comment.classId === currentContent.value?.id);
}

function formatCommentTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return "ahora";
    if (diffMins < 60) return `hace ${diffMins} min`;
    if (diffHours < 24) return `hace ${diffHours} h`;
    if (diffDays < 7) return `hace ${diffDays} días`;
    return formatDate(timestamp);
}
</script>

<template>
    <AppLayout title="Mi Curso">
        <!-- Contenedor principal que respeta el espacio asignado -->
        <div class="panel p-0">
            <!-- Header Mejorado y Fijo - Siempre debajo del header principal -->
            <div class="border-b bg-white rounded-t-2xl border-gray-200 dark:bg-gray-800 dark:border-gray-700"
                 style="height: var(--header-height)">

                <div class="flex items-center justify-between h-full p-4 sm:p-6">
                    <!-- Sección Izquierda - Información Principal -->
                    <div class="flex items-center space-x-3 sm:space-x-6">
                        <!-- Tarjeta de Información del Curso Mejorada -->
                        <div class="relative group flex-1">
                            <!-- Efecto de hover premium -->
                            <div class="absolute -inset-1 rounded-xl blur transition-all duration-300 opacity-0 group-hover:opacity-40"
                                 style="background: linear-gradient(135deg, var(--accent-color)20, transparent)"></div>

                            <div class="relative flex items-center space-x-2 sm:space-x-4">
                               <!-- Contenido de Información -->
                                <div class="flex flex-col">
                                    <h1 class="text-lg sm:text-xl font-bold tracking-tight text-gray-900 dark:text-white transition-colors duration-300">
                                        {{ courseData.title }}
                                    </h1>
                                    <div class="hidden sm:flex items-center space-x-2 sm:space-x-4 text-xs sm:text-sm font-medium mt-2 text-gray-600 dark:text-gray-300">
                                        <span class="flex items-center space-x-1 sm:space-x-2 bg-gray-100 px-2 sm:px-3 py-1 rounded-full dark:bg-gray-700 dark:text-gray-300">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                            </svg>
                                            <span class="hidden sm:inline">{{ courseData.instructor }}</span>
                                            <span class="sm:hidden">{{ courseData.instructor.split(' ')[0] }}</span>
                                        </span>
                                        <span class="flex items-center space-x-1 sm:space-x-2 bg-gray-100 px-2 sm:px-3 py-1 rounded-full dark:bg-gray-700 dark:text-gray-300">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                            </svg>
                                            <span>{{ courseData.duration }}</span>
                                        </span>
                                        <span class="flex items-center space-x-1 sm:space-x-2 bg-gray-100 px-2 sm:px-3 py-1 rounded-full dark:bg-gray-700 dark:text-gray-300">
                                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span>{{ courseData.modules.length }} módulos</span>
                                        </span>
                                    </div>
                                    <!-- Mobile version of info - single line -->
                                    <div class="flex sm:hidden items-center space-x-2 text-xs text-gray-600 dark:text-gray-300 mt-1">
                                        <span>{{ courseData.instructor.split(' ')[0] }}</span>
                                        <span>•</span>
                                        <span>{{ courseData.duration }}</span>
                                        <span>•</span>
                                        <span>{{ courseData.modules.length }} módulos</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección Derecha - Barra de Progreso Premium -->
                    <div class="flex items-center space-x-3 sm:space-x-6">
                        <!-- Barra de Progreso Ultra Moderna -->
                        <div class="relative group">
                            <div class="flex items-center space-x-2 sm:space-x-4">
                                <!-- Estadísticas de Progreso -->
                                <div class="text-right hidden sm:block">
                                    <div class="text-xs font-bold uppercase tracking-wider opacity-70"
                                          :style="{ color: `var(--accent-color)` }">
                                        Progreso del Curso
                                    </div>
                                    <div class="text-2xl sm:text-3xl font-bold mt-1 transition-all duration-300 group-hover:scale-110"
                                          :style="{ color: `var(--accent-color)` }">
                                        {{ courseData.progress }}%
                                    </div>
                                    <div class="text-xs mt-1 hidden lg:block"
                                         :class="isDarkMode ? 'text-gray-400' : 'text-gray-500'">
                                        {{ courseData.modules.reduce((acc, m) => acc + m.classes.filter(c => c.completed).length, 0) }} / {{ courseData.modules.reduce((acc, m) => acc + m.classes.length, 0) }} lecciones
                                    </div>
                                </div>

                                <!-- Mobile Progress Indicator -->
                                <div class="text-right sm:hidden">
                                    <div class="text-lg font-bold transition-all duration-300"
                                          :style="{ color: `var(--accent-color)` }">
                                        {{ courseData.progress }}%
                                    </div>
                                </div>

                                <!-- Barra Visual Mejorada -->
                                <div class="relative">
                                     <div class="relative w-32 sm:w-48 lg:w-56 h-3 sm:h-4 bg-gray-200 rounded-full overflow-hidden shadow-inner"
                                          :class="isDarkMode ? 'bg-gray-700' : 'bg-gray-200'">
                                        <div class="h-full rounded-full transition-all duration-1000 ease-out relative overflow-hidden"
                                             :style="{
                                                 width: `${courseData.progress}%`,
                                                 background: `linear-gradient(90deg, var(--accent-color) 0%, ${courseData.progress > 75 ? '#10b981' : 'var(--accent-color)'} 100%)`,
                                                 boxShadow: `inset 0 2px 4px rgba(0,0,0,0.1)`
                                             }">
                                            <!-- Efecto de Brillo -->
                                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/50 to-transparent animate-pulse"></div>
                                        </div>

                                        <!-- Marcador de Posición Premium -->
                                        <div class="absolute top-1/2 transform -translate-y-1/2 transition-all duration-1000"
                                             :style="{
                                                 left: `${courseData.progress}%`,
                                                 transform: 'translate(-50%, -50%) scale(1.5)'
                                             }">
                                            <div class="w-4 h-4 sm:w-5 sm:h-5 rounded-full shadow-xl transition-all duration-300 relative"
                                                 :style="[
                                                     courseData.progress > 75
                                                         ? { backgroundColor: '#10b981', boxShadow: '0 0 25px rgba(16, 185, 129, 0.8)' }
                                                         : { backgroundColor: `var(--accent-color)`, boxShadow: `0 0 25px ${config.accentColor}80` }
                                                 ]">
                                                <!-- Centro brillante -->
                                                <div class="absolute inset-1 rounded-full bg-white opacity-30"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido principal con flex -->
            <div class="flex h-[calc(100%-var(--header-height))]">
                <!-- Sidebar mejorado con estructura de árbol -->
                <aside :class="[
                    'transition-all duration-300 overflow-hidden flex flex-col border-r',
                    isDarkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200',
                    'fixed sm:relative sm:translate-x-0 z-40',
                    isSidebarCollapsed ? 'w-16' : 'w-72 sm:w-80',
                    isSidebarCollapsed ? '-translate-x-full sm:translate-x-0' : 'translate-x-0'
                ]">

                    <div ref="sidebarScroll" class="flex-1 p-3 sm:p-4 overflow-y-auto sm:overflow-y-visible" style="height: 0; min-height: 0;">
                        <!-- Título del sidebar con botón toggle -->
                        <div v-if="!isSidebarCollapsed" class="flex items-center justify-between mb-4 sm:mb-6">
                            <h2 class="text-base font-semibold"
                                :class="isDarkMode ? 'text-white' : 'text-gray-900'">
                                📚 Contenido del Curso
                            </h2>
                            <div class="flex items-center space-x-2">
                                <!-- Botón toggle sidebar para desktop (ocultar/mostrar sidebar completo) -->
                                <button @click="toggleSidebar"
                                        class="hidden sm:block p-2 rounded-lg transition-all duration-200 hover:scale-105"
                                        :class="isDarkMode
                                            ? 'hover:bg-gray-700 text-gray-300 hover:text-gray-200'
                                            : 'hover:bg-gray-100 text-gray-600 hover:text-gray-800'"
                                        title="Ocultar sidebar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <!-- Botón toggle sidebar para mobile -->
                                <button @click="toggleSidebar"
                                        class="sm:hidden p-2 rounded-lg transition-all duration-200"
                                        :class="isDarkMode ? 'hover:bg-gray-700 text-gray-300' : 'hover:bg-gray-100 text-gray-600'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <!-- Sidebar colapsado - Solo mostrar botón para expandir -->
                        <div v-if="isSidebarCollapsed" class="flex justify-center mb-4 sm:mb-6">
                            <button @click="toggleSidebar"
                                    class="hidden sm:block p-2 rounded-lg transition-all duration-200 hover:scale-105"
                                    :class="isDarkMode
                                        ? 'hover:bg-gray-700 text-gray-300 hover:text-gray-200'
                                        : 'hover:bg-gray-100 text-gray-600 hover:text-gray-800'"
                                    title="Mostrar sidebar completo">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                        </div>

                        <div v-for="(module, moduleIndex) in courseData.modules" :key="module.id" class="mb-4 sm:mb-6 relative">
                            <!-- Línea vertical decorativa del módulo -->
                            <div v-if="!isSidebarCollapsed" class="absolute left-3 sm:left-4 top-8 bottom-0 w-px">
                                <div class="h-full w-px animate-pulse opacity-30"
                                     :class="isDarkMode ? 'bg-gray-600' : 'bg-gray-300'"></div>
                            </div>

                            <!-- Cabecera del módulo con círculos animados -->
                            <div v-if="!isSidebarCollapsed" class="mb-3 sm:mb-4 relative">
                                <div class="relative group/item">
                                    <!-- Efecto de hover sin blur -->
                                    <div class="absolute -inset-1 rounded-xl opacity-0 transition-all duration-300 group-hover/item:opacity-20"
                                         :style="{
                                             background: `linear-gradient(135deg, ${config.accentColor}15, transparent)`
                                         }"></div>

                                    <!-- Tarjeta del módulo -->
                                    <div class="relative flex items-center space-x-2 sm:space-x-3 p-2 sm:p-3 rounded-xl border transition-all duration-300 hover:scale-[1.02]"
                                         :class="isDarkMode
                                             ? 'bg-gray-800/50 border-gray-700'
                                             : 'bg-white/70 border-gray-200'">

                                        <!-- Número de orden del módulo con círculo palpitante -->
                                        <div class="relative flex-shrink-0 group/icon-module">
                                            <!-- Número del módulo -->
                                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-sm sm:text-lg font-bold transition-all duration-300 group-hover/item:scale-110 group-hover/icon-module:scale-110"
                                                 :class="isDarkMode ? 'bg-gray-700 text-gray-200' : 'bg-gray-100 text-gray-700'">
                                                {{ moduleIndex + 1 }}
                                            </div>

                                            <!-- Círculo palpitante detrás del número -->
                                            <div class="absolute -inset-1 rounded-xl opacity-0 transition-all duration-300 group-hover/icon-module:opacity-100">
                                                <div class="absolute inset-0 rounded-xl border-2 animate-pulse"
                                                     :style="{
                                                         borderColor: `var(--accent-color)`,
                                                         animationDuration: '2s'
                                                     }"></div>
                                                <div class="absolute inset-0 rounded-xl border-2 animate-ping opacity-30"
                                                     :style="{
                                                         borderColor: `var(--accent-color)`,
                                                         animationDuration: '2s'
                                                     }"></div>
                                            </div>

                                            <!-- Indicador de progreso del módulo -->
                                            <div v-if="module.classes.filter(c => c.completed).length > 0"
                                                 class="absolute -bottom-1 -right-1 w-2 h-2 sm:w-3 sm:h-3 rounded-full border-2 animate-pulse"
                                                 :class="[
                                                     'z-20',
                                                     isDarkMode ? 'bg-green-500 border-gray-800' : 'bg-green-500 border-white'
                                                 ]">
                                            </div>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-bold text-xs sm:text-sm tracking-tight truncate"
                                                :class="isDarkMode ? 'text-white' : 'text-gray-900'">
                                                {{ module.title }}
                                            </h3>
                                            <div class="flex items-center space-x-2 text-xs mt-1"
                                                 :class="isDarkMode ? 'text-gray-500' : 'text-gray-600'">
                                                <span>{{ module.classes.length }} lecc</span>
                                                <span class="hidden sm:inline">lecciones</span>
                                                <span>•</span>
                                                <span class="font-medium"
                                                      :class="module.classes.filter(c => c.completed).length === module.classes.length ? 'text-green-500' : ''">
                                                    {{ module.classes.filter(c => c.completed).length }}/{{ module.classes.length }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Estado del módulo -->
                                        <div class="flex-shrink-0 text-xs font-medium px-1 sm:px-2 py-1 rounded-lg whitespace-nowrap"
                                             :class="[
                                                 module.classes.filter(c => c.completed).length === module.classes.length
                                                     ? 'bg-green-100 text-green-700 border border-green-200'
                                                     : 'bg-yellow-100 text-yellow-700 border border-yellow-200'
                                             ]">
                                            <span class="hidden sm:inline">{{ module.classes.filter(c => c.completed).length === module.classes.length ? '✓ Completo' : '⏳ En curso' }}</span>
                                            <span class="sm:hidden">{{ module.classes.filter(c => c.completed).length === module.classes.length ? '✓' : '⏳' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Lista de clases con puntos de conexión -->
                            <div v-if="!isSidebarCollapsed" class="ml-6 sm:ml-8 space-y-1 sm:space-y-2">
                                <div v-for="(classItem, classIndex) in module.classes"
                                     :key="classItem.id"
                                     @click="selectClass(moduleIndex, classIndex)"
                                     :class="[
                                         'group/class cursor-pointer transition-all duration-300 relative',
                                         selectedModule === moduleIndex && selectedClass === classIndex ? 'transform translate-x-1' : ''
                                     ]">

                                    <!-- Línea de conexión horizontal al punto -->
                                    <div class="absolute -left-3 sm:-left-4 top-1/2 w-3 sm:w-4 h-px transform -translate-y-1/2 opacity-30 transition-all duration-300 group-hover/class:opacity-60"
                                         :class="isDarkMode ? 'bg-gray-600' : 'bg-gray-300'"></div>

                                    <!-- Punto de conexión de la clase -->
                                    <div class="absolute -left-4 sm:-left-6 top-1/2 transform -translate-y-1/2 transition-all duration-300 group-hover/class:scale-150">
                                        <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full transition-all duration-300"
                                             :class="[
                                                 classItem.completed
                                                     ? 'bg-green-500 shadow-green-500/50 animate-pulse'
                                                     : isDarkMode ? 'bg-gray-600' : 'bg-gray-300'
                                             ]"
                                             :style="classItem.completed ? { boxShadow: '0 0 12px rgba(34, 197, 94, 0.4)' } : {}"></div>
                                    </div>

                                    <!-- Tarjeta de la clase -->
                                    <div class="relative">
                                        <!-- Efecto de hover sin blur -->
                                        <div class="absolute -inset-1 rounded-lg opacity-0 transition-all duration-300"
                                             :class="[
                                                 selectedModule === moduleIndex && selectedClass === classIndex
                                                     ? 'opacity-30'
                                                     : 'group-hover/class:opacity-20'
                                             ]"
                                             :style="{
                                                 background: selectedModule === moduleIndex && selectedClass === classIndex
                                                     ? `linear-gradient(90deg, ${config.accentColor}40, ${config.accentColor}10)`
                                                     : isDarkMode
                                                         ? 'linear-gradient(90deg, rgba(74, 144, 226, 0.1), transparent)'
                                                         : 'linear-gradient(90deg, rgba(74, 144, 226, 0.05), transparent)'
                                             }"></div>

                                        <div class="relative flex items-center space-x-2 sm:space-x-3 p-2 sm:p-3 rounded-lg border transition-all duration-300"
                                             :class="[
                                                 selectedModule === moduleIndex && selectedClass === classIndex
                                                     ? isDarkMode ? 'bg-blue-900/40 border-blue-600' : 'bg-blue-50/80 border-blue-200'
                                                     : isDarkMode ? 'bg-gray-800/30 border-gray-700 hover:bg-gray-700/40' : 'bg-white/60 border-gray-200 hover:bg-gray-50/80',
                                                 'hover:scale-[1.02]'
                                             ]">

                                            <!-- Icono de contenido -->
                                            <div class="relative group/icon flex-shrink-0">
                                                <div class="w-6 h-6 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center text-xs sm:text-sm font-bold transition-all duration-300 group-hover/icon:scale-110"
                                                     :class="[
                                                         classItem.completed
                                                             ? 'bg-gradient-to-br from-green-500 to-green-600 text-white shadow-lg'
                                                             : isDarkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-100 text-gray-600'
                                                     ]"
                                                     :style="classItem.completed ? { boxShadow: '0 4px 12px rgba(34, 197, 94, 0.25)' } : {}">
                                                    {{ getContentIcon(classItem.type) }}
                                                </div>

                                                <!-- Efecto de estado completado -->
                                                <div v-if="classItem.completed"
                                                     class="absolute -top-1 -right-1 w-3 h-3 sm:w-4 sm:h-4 rounded-full bg-green-500 text-white flex items-center justify-center text-xs font-bold border border-current animate-bounce">
                                                    ✓
                                                </div>
                                            </div>

                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-xs truncate transition-colors duration-300"
                                                   :class="[
                                                       selectedModule === moduleIndex && selectedClass === classIndex
                                                           ? 'text-blue-500 font-semibold'
                                                           : isDarkMode ? 'text-gray-200' : 'text-gray-900',
                                                       classItem.completed ? 'opacity-80' : ''
                                                   ]">
                                                     {{ classItem.title }}
                                                </p>
                                                <div class="flex items-center space-x-2 text-xs mt-1"
                                                     :class="isDarkMode ? 'text-gray-500' : 'text-gray-500'">
                                                    <span>⏱️ {{ classItem.duration }}</span>
                                                    <span v-if="classItem.completed" class="text-green-500 font-medium hidden sm:flex items-center">
                                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                        </svg>
                                                        Completado
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Indicador de tipo de contenido -->
                                            <div class="flex-shrink-0 text-xs px-1 sm:px-2 py-1 rounded-full font-medium whitespace-nowrap"
                                                 :class="[
                                                     classItem.type === 'video' ? 'bg-red-100 text-red-700' :
                                                     classItem.type === 'text' ? 'bg-blue-100 text-blue-700' :
                                                     classItem.type === 'mixed' ? 'bg-purple-100 text-purple-700' :
                                                     'bg-gray-100 text-gray-700'
                                                 ]">
                                                <span class="hidden sm:inline">{{ classItem.type.charAt(0).toUpperCase() + classItem.type.slice(1) }}</span>
                                                <span class="sm:hidden">{{ classItem.type.charAt(0).toUpperCase() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Examen del módulo con su punto de conexión -->
                            <div v-if="module.exam && !isSidebarCollapsed" class="ml-6 sm:ml-8 mt-3 sm:mt-4 relative">
                                <!-- Punto de conexión del examen -->
                                <div class="absolute -left-4 sm:-left-6 top-1/2 transform -translate-y-1/2">
                                    <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-purple-500 animate-pulse"
                                         :style="{ boxShadow: '0 0 12px rgba(147, 51, 234, 0.4)' }"></div>
                                </div>

                                <!-- Línea de conexión horizontal -->
                                <div class="absolute -left-3 sm:-left-4 top-1/2 w-3 sm:w-4 h-px transform -translate-y-1/2 opacity-30"
                                     :class="isDarkMode ? 'bg-gray-600' : 'bg-gray-300'"></div>

                                <div @click="openExam(module.exam)"
                                     class="group/exam cursor-pointer transition-all duration-300 hover:scale-[1.01]">
                                    <!-- Efecto de hover sin blur -->
                                    <div class="absolute -inset-1 rounded-lg opacity-0 transition-all duration-300 group-hover/exam:opacity-30"
                                         :style="{
                                             background: 'linear-gradient(90deg, rgba(147, 51, 234, 0.1), transparent)'
                                         }"></div>

                                    <div class="relative flex items-center space-x-2 sm:space-x-3 p-2 sm:p-3 rounded-lg border transition-all duration-300"
                                         :class="isDarkMode
                                             ? 'bg-purple-900/20 border-purple-700/50 hover:bg-purple-800/30'
                                             : 'bg-purple-50/70 border-purple-200/50 hover:bg-purple-100/80'">

                                        <div class="flex-shrink-0 w-6 h-6 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center text-xs sm:text-sm font-bold bg-gradient-to-br from-purple-500 to-purple-600 text-white shadow-lg">
                                            📝
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-xs font-semibold truncate"
                                               :class="isDarkMode ? 'text-purple-300' : 'text-purple-900'">
                                                <span class="hidden sm:inline">Examen del Módulo</span>
                                                <span class="sm:hidden">Examen</span>
                                            </p>
                                            <div class="flex items-center space-x-2 text-xs mt-1"
                                                 :class="isDarkMode ? 'text-purple-400' : 'text-purple-600'">
                                                <span>{{ module.exam.questions }}</span>
                                                <span>•</span>
                                                <span>{{ module.exam.duration }}</span>
                                            </div>
                                        </div>

                                        <div class="flex-shrink-0 flex items-center space-x-1">
                                            <div v-if="module.exam.available"
                                                 class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-green-500 animate-pulse"></div>
                                            <div v-else class="text-xs bg-purple-500/20 text-purple-600 px-1 sm:px-2 py-1 rounded-full border border-purple-500/30 whitespace-nowrap">
                                                <span class="hidden sm:inline">🔒 Bloqueado</span>
                                                <span class="sm:hidden">🔒</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Examen Final - Diseño compacto y profesional para sidebar -->
                        <div v-if="courseData.finalExam && !isSidebarCollapsed"
                             class="mt-4 sm:mt-6 relative">
                            <div @click="openExam(courseData.finalExam)"
                                 class="relative cursor-pointer transition-all duration-300 hover:bg-opacity-80 group">

                                <!-- Contenedor principal - colores sólidos y compactos -->
                                <div class="rounded-xl border-2 transition-all duration-300 overflow-hidden"
                                     :class="[
                                         courseData.finalExam.available
                                             ? 'bg-gradient-to-r from-blue-600 to-blue-700 border-blue-500 hover:border-blue-400'
                                             : 'bg-gradient-to-r from-gray-600 to-gray-700 border-gray-500 hover:border-gray-400',
                                         'shadow-md hover:shadow-lg'
                                     ]">

                                    <!-- Contenido compacto y bien organizado -->
                                    <div class="p-2 sm:p-3">
                                        <!-- Header del examen -->
                                        <div class="flex items-center justify-between mb-2 sm:mb-3">
                                            <div class="flex items-center space-x-1 sm:space-x-2">
                                                <!-- Trofeo compacto -->
                                                <div class="w-6 h-6 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center text-xs sm:text-sm font-bold bg-yellow-500 text-white">
                                                    🏆
                                                </div>

                                                <!-- Título -->
                                                <h3 class="text-xs sm:text-sm font-bold text-white truncate pr-2">
                                                    <span class="hidden sm:inline">Examen Final</span>
                                                    <span class="sm:hidden">Final</span>
                                                </h3>
                                            </div>

                                            <!-- Badge de estado -->
                                            <div class="px-1 sm:px-2 py-1 rounded-full text-xs font-bold"
                                                 :class="[
                                                     courseData.finalExam.available
                                                         ? 'bg-green-500 text-white'
                                                         : 'bg-red-500 text-white'
                                                 ]">
                                                <span class="hidden sm:inline">{{ courseData.finalExam.available ? 'DISPONIBLE' : 'BLOQUEADO' }}</span>
                                                <span class="sm:hidden">{{ courseData.finalExam.available ? '✓' : '🔒' }}</span>
                                            </div>
                                        </div>

                                        <!-- Información en grid compacto -->
                                        <div class="grid grid-cols-3 gap-1 sm:gap-2 mb-2 sm:mb-3">
                                            <!-- Preguntas -->
                                            <div class="text-center p-1 sm:p-2 rounded bg-white bg-opacity-20">
                                                <div class="text-xs font-semibold text-white opacity-80 hidden sm:block">Preguntas</div>
                                                <div class="text-xs sm:text-sm font-bold text-white">{{ courseData.finalExam.questions }}</div>
                                            </div>

                                            <!-- Duración -->
                                            <div class="text-center p-1 sm:p-2 rounded bg-white bg-opacity-20">
                                                <div class="text-xs font-semibold text-white opacity-80 hidden sm:block">Duración</div>
                                                <div class="text-xs sm:text-sm font-bold text-white">{{ courseData.finalExam.duration }}</div>
                                            </div>

                                            <!-- Tipo -->
                                            <div class="text-center p-1 sm:p-2 rounded bg-white bg-opacity-20">
                                                <div class="text-xs font-semibold text-white opacity-80 hidden sm:block">Tipo</div>
                                                <div class="text-xs sm:text-sm font-bold text-white">CURSO</div>
                                            </div>
                                        </div>

                                        <!-- Mensaje informativo -->
                                        <div v-if="!courseData.finalExam.available"
                                             class="p-1 sm:p-2 rounded bg-red-500 bg-opacity-20 border border-red-400 border-opacity-30 mb-2 sm:mb-3">
                                            <p class="text-xs text-red-100 text-center">
                                                <span class="hidden sm:inline">Completa todo el curso para desbloquear</span>
                                                <span class="sm:hidden">Completa el curso</span>
                                            </p>
                                        </div>

                                        <div v-else
                                             class="p-1 sm:p-2 rounded bg-green-500 bg-opacity-20 border border-green-400 border-opacity-30 mb-2 sm:mb-3">
                                            <p class="text-xs text-green-100 text-center">
                                                <span class="hidden sm:inline">¡Listo para el examen final!</span>
                                                <span class="sm:hidden">¡Listo!</span>
                                            </p>
                                        </div>

                                        <!-- Botón de acción -->
                                        <button class="w-full py-1.5 sm:py-2 rounded-lg font-bold text-xs sm:text-sm transition-all duration-200"
                                                :class="[
                                                    courseData.finalExam.available
                                                        ? 'bg-white text-blue-600 hover:bg-gray-100'
                                                        : 'bg-gray-500 text-gray-300 cursor-not-allowed opacity-60'
                                                ]"
                                                :disabled="!courseData.finalExam.available">
                                            <span class="hidden sm:inline">{{ courseData.finalExam.available ? 'COMENZAR EXAMEN' : 'NO DISPONIBLE' }}</span>
                                            <span class="sm:hidden">{{ courseData.finalExam.available ? 'COMENZAR' : 'BLOQUEADO' }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Área principal de contenido -->
                <main :class="[
                    'flex-1 overflow-y-auto',
                    isDarkMode ? 'bg-gray-900' : 'bg-gray-50'
                ]">
                    <!-- Botón toggle sidebar para mobile (arriba del contenido) -->
                    <div class="sm:hidden sticky top-0 z-30 p-3"
                         :class="isDarkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'">
                        <button @click="toggleSidebar"
                                class="w-full px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                                :class="isDarkMode
                                    ? 'bg-gray-700 text-gray-300 hover:bg-gray-600'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                            Mostrar Contenido
                        </button>
                    </div>

                    <div v-if="currentContent" class="p-3 sm:p-6 space-y-3 sm:space-y-4">
                        <!-- Header del contenido mejorado -->
                        <div :class="[
                            'rounded-xl shadow-sm p-3 sm:p-4 transition-all duration-300',
                            isDarkMode ? 'bg-gray-800' : 'bg-white'
                        ]">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 sm:space-x-3 mb-2">
                                        <div class="w-8 h-8 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center text-lg sm:text-2xl"
                                             :class="isDarkMode ? 'bg-gray-700' : 'bg-blue-100'">
                                            {{ getContentIcon(currentContent.type) }}
                                        </div>
                                        <div>
                                            <h2 class="text-base sm:text-lg font-bold" :class="isDarkMode ? 'text-white' : 'text-gray-900'">
                                                {{ currentContent.title }}
                                            </h2>
                                            <div class="flex flex-wrap items-center gap-2 text-xs mt-1" :class="isDarkMode ? 'text-gray-400' : 'text-gray-600'">
                                                <span class="flex items-center space-x-1">
                                                    <span>{{ currentContent.type.charAt(0).toUpperCase() + currentContent.type.slice(1) }}</span>
                                                </span>
                                                <span>•</span>
                                                <span>⏱️ {{ currentContent.duration }}</span>
                                                <span>•</span>
                                                <span :class="currentContent.completed ? 'text-green-500 font-medium' : 'text-yellow-500'">
                                                    {{ currentContent.completed ? '✅ Completado' : '⏳ Pendiente' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <!-- Botón de comentarios -->
                                    <button @click="toggleComments"
                                            class="p-2 rounded-lg transition-all duration-200 hover:scale-105"
                                            :class="isDarkMode ? 'hover:bg-gray-700 text-gray-300' : 'hover:bg-gray-100 text-gray-600'">
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                    </button>

                                    <!-- Botón de estado -->
                                    <button class="px-2 sm:px-3 py-1.5 rounded-lg font-medium transition-all duration-200 text-xs sm:text-sm hover:scale-105"
                                            :style="{
                                                backgroundColor: `var(--accent-color)`,
                                                color: 'white'
                                            }">
                                        {{ currentContent.completed ? '🔄 Repasar' : '✓ Completar' }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Contenido Texto -->
                        <div v-if="currentContent.content.text" :class="[
                            'rounded-xl shadow-sm p-4 transition-all duration-300',
                            isDarkMode ? 'bg-gray-800' : 'bg-white'
                        ]">
                            <h3 class="text-base font-semibold mb-3 flex items-center"
                                :class="isDarkMode ? 'text-white' : 'text-gray-900'">
                                <span class="mr-2">📄</span> Contenido
                            </h3>
                            <div class="prose prose-sm max-w-none">
                                <p class="leading-relaxed" :class="isDarkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ currentContent.content.text }}
                                </p>
                            </div>
                        </div>

                        <!-- Contenido Video -->
                        <div v-if="currentContent.content.video" :class="[
                            'rounded-xl shadow-sm p-4 transition-all duration-300',
                            isDarkMode ? 'bg-gray-800' : 'bg-white'
                        ]">
                            <h3 class="text-base font-semibold mb-3 flex items-center"
                                :class="isDarkMode ? 'text-white' : 'text-gray-900'">
                                <span class="mr-2">🎥</span> Video
                            </h3>
                            <div class="relative rounded-lg overflow-hidden bg-black aspect-video shadow-lg">
                                <video
                                    controls
                                    class="w-full h-full"
                                    :poster="'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'800\' height=\'450\' viewBox=\'0 0 800 450\'%3E%3Crect fill=\'%23374151\' width=\'800\' height=\'450\'/%3E%3Ctext fill=\'%23ffffff\' font-size=\'20\' font-family=\'system-ui\' text-anchor=\'middle\' x=\'400\' y=\'225\'%3EVideo Player%3C/text%3E%3C/svg%3E'">
                                    <source :src="currentContent.content.video" type="video/mp4">
                                    Tu navegador no soporta la reproducción de video.
                                </video>
                            </div>
                        </div>

                        <!-- Archivos para descargar -->
                        <div v-if="currentContent.content.files && currentContent.content.files.length > 0" :class="[
                            'rounded-xl shadow-sm p-4 transition-all duration-300',
                            isDarkMode ? 'bg-gray-800' : 'bg-white'
                        ]">
                            <h3 class="text-base font-semibold mb-3 flex items-center"
                                :class="isDarkMode ? 'text-white' : 'text-gray-900'">
                                <span class="mr-2">📎</span> Archivos de Descarga
                            </h3>
                            <div class="space-y-2">
                                <div v-for="(file, index) in currentContent.content.files" :key="index"
                                     class="flex items-center justify-between p-3 rounded-lg transition-all duration-200 hover:scale-[1.02]"
                                     :class="isDarkMode ? 'bg-gray-700 hover:bg-gray-600 border-gray-600' : 'bg-gray-50 hover:bg-gray-100 border-gray-200'">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                                             :class="isDarkMode ? 'bg-blue-900' : 'bg-blue-100'">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-medium text-sm" :class="isDarkMode ? 'text-gray-200' : 'text-gray-900'">
                                                {{ file.name }}
                                            </p>
                                            <p class="text-xs" :class="isDarkMode ? 'text-gray-500' : 'text-gray-500'">
                                                {{ file.size }}
                                            </p>
                                        </div>
                                    </div>
                                    <a :href="file.url" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 text-xs font-medium hover:scale-105">
                                        Descargar
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Enlaces Externos -->
                        <div v-if="currentContent.content.externalLinks && currentContent.content.externalLinks.length > 0" :class="[
                            'rounded-xl shadow-sm p-4 transition-all duration-300',
                            isDarkMode ? 'bg-gray-800' : 'bg-white'
                        ]">
                            <h3 class="text-base font-semibold mb-3 flex items-center"
                                :class="isDarkMode ? 'text-white' : 'text-gray-900'">
                                <span class="mr-2">🔗</span> Recursos Externos
                            </h3>
                            <div class="space-y-2">
                                <a v-for="(link, index) in currentContent.content.externalLinks"
                                   :key="index"
                                   :href="link.url"
                                   target="_blank"
                                   class="flex items-center justify-between p-3 rounded-lg transition-all duration-200 hover:scale-[1.02] group"
                                   :class="isDarkMode ? 'bg-gray-700 hover:bg-gray-600 border-gray-600' : 'bg-gray-50 hover:bg-gray-100 border-gray-200'">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                                             :class="isDarkMode ? 'bg-green-900' : 'bg-green-100'">
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </div>
                                        <p class="font-medium text-sm transition-colors"
                                           :class="isDarkMode ? 'text-gray-200 group-hover:text-blue-400' : 'text-gray-900 group-hover:text-blue-600'">
                                            {{ link.title }}
                                        </p>
                                    </div>
                                    <svg class="w-4 h-4 transition-colors"
                                       :class="isDarkMode ? 'text-gray-400 group-hover:text-blue-400' : 'text-gray-400 group-hover:text-blue-600'"
                                       fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <!-- Sección de Comentarios -->
                        <div v-if="showComments" :class="[
                            'rounded-xl shadow-sm p-4 transition-all duration-300',
                            isDarkMode ? 'bg-gray-800' : 'bg-white'
                        ]">
                            <h3 class="text-base font-semibold mb-4 flex items-center justify-between"
                                :class="isDarkMode ? 'text-white' : 'text-gray-900'">
                                <span class="flex items-center">
                                    <span class="mr-2">💬</span> Comentarios ({{ getClassComments().length }})
                                </span>
                                <button @click="toggleComments" class="text-sm hover:scale-105 transition-transform"
                                        :class="isDarkMode ? 'text-gray-400 hover:text-gray-200' : 'text-gray-500 hover:text-gray-700'">
                                    ✕
                                </button>
                            </h3>

                            <!-- Formulario para agregar comentario -->
                            <div class="mb-4">
                                <div class="flex space-x-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-xl bg-gradient-to-r from-blue-500 to-purple-600 text-white">
                                        🎓
                                    </div>
                                    <div class="flex-1">
                                        <textarea
                                            v-model="newComment"
                                            placeholder="Escribe un comentario..."
                                            class="w-full p-3 rounded-lg border resize-none transition-colors"
                                            :class="[
                                                'text-sm',
                                                isDarkMode
                                                    ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400 focus:border-blue-500'
                                                    : 'bg-gray-50 border-gray-200 text-gray-900 placeholder-gray-500 focus:border-blue-500'
                                            ]"
                                            rows="3"
                                            @keydown.enter.prevent="addComment"
                                        ></textarea>
                                        <button
                                            @click="addComment"
                                            :disabled="!newComment.trim()"
                                            class="mt-2 px-4 py-2 rounded-lg font-medium transition-all duration-200 text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:scale-105"
                                            :style="{
                                                backgroundColor: newComment.trim() ? `var(--accent-color)` : '#ccc',
                                                color: 'white'
                                            }">
                                            Publicar Comentario
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Lista de comentarios -->
                            <div class="space-y-3 max-h-96 overflow-y-auto">
                                <div v-for="comment in getClassComments()" :key="comment.id"
                                     class="flex space-x-3 p-3 rounded-lg transition-all duration-200"
                                     :class="isDarkMode ? 'bg-gray-700' : 'bg-gray-50'">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-xl">
                                        {{ comment.avatar }}
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-1">
                                            <div>
                                                <span class="font-medium text-sm" :class="isDarkMode ? 'text-gray-200' : 'text-gray-900'">
                                                    {{ comment.author }}
                                                </span>
                                                <span class="text-xs ml-2" :class="isDarkMode ? 'text-gray-500' : 'text-gray-500'">
                                                    {{ formatCommentTime(comment.timestamp) }}
                                                </span>
                                            </div>
                                            <div class="flex items-center space-x-1">
                                                <button class="flex items-center space-x-1 text-xs transition-colors hover:scale-105"
                                                        :class="isDarkMode ? 'text-gray-400 hover:text-red-400' : 'text-gray-500 hover:text-red-500'">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"></path>
                                                    </svg>
                                                    <span>{{ comment.likes }}</span>
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-sm leading-relaxed" :class="isDarkMode ? 'text-gray-300' : 'text-gray-700'">
                                            {{ comment.content }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <!-- Modal de Exámenes mejorado -->
        <div v-if="showExamModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div :class="[
                'rounded-xl p-6 max-w-md w-full transition-all duration-300 transform scale-100',
                isDarkMode ? 'bg-gray-800' : 'bg-white'
            ]">
                <h3 class="text-lg font-bold mb-4 flex items-center"
                    :class="isDarkMode ? 'text-white' : 'text-gray-900'">
                    <span class="mr-2">📝</span>
                    {{ currentExam?.type === 'course' ? currentExam?.title : 'Examen del Módulo' }}
                </h3>
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between p-3 rounded-lg"
                         :class="isDarkMode ? 'bg-gray-700' : 'bg-gray-50'">
                        <span class="text-sm" :class="isDarkMode ? 'text-gray-300' : 'text-gray-600'">
                            📋 Preguntas:
                        </span>
                        <span class="font-semibold text-sm" :class="isDarkMode ? 'text-white' : 'text-gray-900'">
                            {{ currentExam?.questions }}
                        </span>
                    </div>
                    <div class="flex justify-between p-3 rounded-lg"
                         :class="isDarkMode ? 'bg-gray-700' : 'bg-gray-50'">
                        <span class="text-sm" :class="isDarkMode ? 'text-gray-300' : 'text-gray-600'">
                            ⏱️ Duración:
                        </span>
                        <span class="font-semibold text-sm" :class="isDarkMode ? 'text-white' : 'text-gray-900'">
                            {{ currentExam?.duration }}
                        </span>
                    </div>
                    <div class="flex justify-between p-3 rounded-lg"
                         :class="isDarkMode ? 'bg-gray-700' : 'bg-gray-50'">
                        <span class="text-sm" :class="isDarkMode ? 'text-gray-300' : 'text-gray-600'">
                            🔒 Estado:
                        </span>
                        <span :class="currentExam?.available
                            ? 'text-green-500 font-semibold'
                            : 'text-red-500 font-semibold'">
                            {{ currentExam?.available ? '✅ Disponible' : '🔒 Bloqueado' }}
                        </span>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <button
                        v-if="currentExam?.available"
                        class="flex-1 px-4 py-3 rounded-lg font-medium text-white transition-all duration-200 text-sm hover:scale-105"
                        :style="{ backgroundColor: `var(--success-color)` }">
                        🚀 Comenzar Examen
                    </button>
                    <button
                        @click="showExamModal = false"
                        class="flex-1 px-4 py-3 rounded-lg font-medium transition-all duration-200 text-sm hover:scale-105"
                        :class="isDarkMode
                            ? 'bg-gray-700 text-gray-300 hover:bg-gray-600'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

        <!-- Responsive mobile sidebar backdrop -->
        <div v-if="!isSidebarCollapsed && windowWidth < 640"
             class="fixed inset-0 bg-black bg-opacity-50 z-30 sm:hidden"
             @click="toggleSidebar"></div>

    </AppLayout>
</template>
<style>
    :root {
        --bg-color: #{{ config.bgColor }};
        --text-color: #{{ config.textColor }};
        --accent-color: #{{ config.accentColor }};
        --success-color: #{{ config.successColor }};
        --warning-color: #{{ config.warningColor }};
        --error-color: #{{ config.errorColor }};
        --sidebar-width: {{ config.sidebarWidth }};
        --header-height: {{ config.headerHeight }};
        --border-radius: {{ config.borderRadius }};
        --spacing: {{ config.spacing }};
        --font-size-small: {{ config.fontSize.small }};
        --font-size-medium: {{ config.fontSize.medium }};
        --font-size-large: {{ config.fontSize.large }};
        --font-size-xl: {{ config.fontSize.xl }};
    }
    @media (max-width: 639px) {
        .ps__rail-y {
            opacity: 1 !important;
            background: rgba(0, 0, 0, 0.1) !important;
        }
        .ps__thumb-y {
            background-color: rgba(0, 0, 0, 0.3) !important;
            width: 6px !important;
            border-radius: 3px !important;
        }
        .dark .ps__thumb-y {
            background-color: rgba(255, 255, 255, 0.3) !important;
        }
    }
</style>
