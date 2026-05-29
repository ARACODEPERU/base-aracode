import axios from 'axios';
import { computed, reactive, ref } from 'vue';

export function useReaderPageLoader(props, { onMobileIndexClose } = {}) {
    const expandedIds = reactive({});
    const pagesCache = reactive({});
    const selectedPageId = ref(null);
    const currentPage = ref(null);
    const pageLoading = ref(false);
    const pageError = ref(null);
    const pageZoom = ref(100);
    const showAccessBlocked = ref(false);
    const previewPageId = ref(props.access?.previewPageId ?? null);

    const csrfHeaders = () => ({
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        Accept: 'application/json',
    });

    const canAccessPage = (pageId) => {
        if (props.access?.hasActiveSubscription) {
            return true;
        }
        if (!previewPageId.value) {
            return true;
        }
        return previewPageId.value === pageId;
    };

    const loadSectionPages = async (sectionId) => {
        if (pagesCache[sectionId]?.items?.length) {
            return;
        }

        pagesCache[sectionId] = { loading: true, items: [] };

        try {
            const { data } = await axios.get(route('bib_reader_section_pages', sectionId), {
                params: { per_page: 200 },
                headers: csrfHeaders(),
            });
            pagesCache[sectionId] = {
                loading: false,
                items: data.pages?.data ?? [],
            };
        } catch {
            pagesCache[sectionId] = { loading: false, items: [], error: true };
        }
    };

    const onToggleExpand = async (section) => {
        const id = section.id;
        if (expandedIds[id]) {
            delete expandedIds[id];
            return;
        }
        expandedIds[id] = true;
        if (section.pages_count > 0) {
            await loadSectionPages(id);
        }
    };

    const applyPageAccessFromResponse = (access) => {
        if (access?.previewPageId) {
            previewPageId.value = access.previewPageId;
        }
        if (access?.hasActiveSubscription) {
            previewPageId.value = null;
        }
    };

    const onSelectPage = async (page) => {
        if (!canAccessPage(page.id)) {
            showAccessBlocked.value = true;
            selectedPageId.value = page.id;
            onMobileIndexClose?.();
            return;
        }

        selectedPageId.value = page.id;
        onMobileIndexClose?.();
        pageLoading.value = true;
        pageError.value = null;
        showAccessBlocked.value = false;

        try {
            const { data } = await axios.get(route('bib_reader_page_show', page.id), {
                headers: csrfHeaders(),
            });
            if (data.success) {
                currentPage.value = data.page;
                applyPageAccessFromResponse(data.access);
            }
        } catch (err) {
            const payload = err.response?.data;
            if (err.response?.status === 403 && payload?.code === 'subscription_required') {
                showAccessBlocked.value = true;
                if (payload.preview_page_id) {
                    previewPageId.value = payload.preview_page_id;
                }
            } else {
                pageError.value = payload?.message || 'No se pudo cargar el contenido.';
                currentPage.value = null;
            }
        } finally {
            pageLoading.value = false;
        }
    };

    const fetchPracticalCase = async (pageId, caseId) => {
        if (!canAccessPage(pageId)) {
            showAccessBlocked.value = true;
            selectedPageId.value = pageId;
            onMobileIndexClose?.();
            return null;
        }

        const cached = currentPage.value?.practical_cases?.find((c) => c.id === caseId);
        if (cached && currentPage.value?.id === pageId) {
            return cached;
        }

        try {
            const { data } = await axios.get(
                route('bib_reader_practical_case_show', { pageId, caseId }),
                { headers: csrfHeaders() }
            );
            if (data.success) {
                applyPageAccessFromResponse(data.access);
                return data.case;
            }
        } catch (err) {
            const payload = err.response?.data;
            if (err.response?.status === 403 && payload?.code === 'subscription_required') {
                showAccessBlocked.value = true;
                if (payload.preview_page_id) {
                    previewPageId.value = payload.preview_page_id;
                }
            }
        }

        return null;
    };

    const hasBook = computed(() => !!props.book);

    return {
        expandedIds,
        pagesCache,
        selectedPageId,
        currentPage,
        pageLoading,
        pageError,
        pageZoom,
        showAccessBlocked,
        previewPageId,
        hasBook,
        loadSectionPages,
        onToggleExpand,
        onSelectPage,
        fetchPracticalCase,
        canAccessPage,
        csrfHeaders,
    };
}
