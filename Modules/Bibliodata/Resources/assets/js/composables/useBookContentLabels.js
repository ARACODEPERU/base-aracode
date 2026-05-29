import { computed, unref } from 'vue';

export const CONTENT_STRUCTURE_CHAPTER = 'chapter_subchapter';
export const CONTENT_STRUCTURE_LEVEL = 'level_content';

export const CONTENT_STRUCTURE_OPTIONS = [
    {
        value: CONTENT_STRUCTURE_CHAPTER,
        label: 'Capítulo → Sub-capítulo → Página',
    },
    {
        value: CONTENT_STRUCTURE_LEVEL,
        label: 'Nivel → Contenido',
    },
];

export function useBookContentLabels(contentStructureRef) {
    const structure = computed(() => unref(contentStructureRef) || CONTENT_STRUCTURE_CHAPTER);
    const isLevelContent = computed(() => structure.value === CONTENT_STRUCTURE_LEVEL);

    const rootSectionLabel = computed(() => (isLevelContent.value ? 'Nivel' : 'Capítulo'));
    const childSectionLabel = computed(() => (isLevelContent.value ? null : 'Sub-capítulo'));
    const pageLabelSingular = computed(() => (isLevelContent.value ? 'Contenido' : 'Página'));
    const pageLabelPlural = computed(() => (isLevelContent.value ? 'Contenidos' : 'Páginas'));

    const addRootButton = computed(() => (isLevelContent.value ? '+ Nivel' : '+ Capítulo'));
    const emptyTreeMessage = computed(() =>
        isLevelContent.value ? 'Aún no hay niveles' : 'Aún no hay capítulos'
    );
    const createFirstRoot = computed(() =>
        isLevelContent.value ? 'Crear primer nivel' : 'Crear primer capítulo'
    );

    const formatPageNumber = (n, preview = null) => {
        const base = `${pageLabelSingular.value} ${n}`;
        if (preview && preview !== '(vacío)') {
            return `${base} — ${preview}`;
        }
        return base;
    };

    const formatPageHeader = (sectionTitle, pageNumber) => {
        const title = sectionTitle || '';
        if (title) {
            return `${title} — ${pageLabelSingular.value} ${pageNumber}`;
        }
        return `${pageLabelSingular.value} ${pageNumber}`;
    };

    const rootCreatedMessage = computed(() =>
        isLevelContent.value ? 'Nivel creado correctamente' : 'Capítulo creado correctamente'
    );

    const subsectionCreatedMessage = 'Sub-sección creada correctamente';

    const structureFormatLabel = computed(() => {
        const opt = CONTENT_STRUCTURE_OPTIONS.find((o) => o.value === structure.value);
        return opt?.label ?? structure.value;
    });

    return {
        structure,
        isLevelContent,
        rootSectionLabel,
        childSectionLabel,
        pageLabelSingular,
        pageLabelPlural,
        addRootButton,
        emptyTreeMessage,
        createFirstRoot,
        formatPageNumber,
        formatPageHeader,
        rootCreatedMessage,
        subsectionCreatedMessage,
        structureFormatLabel,
    };
}
