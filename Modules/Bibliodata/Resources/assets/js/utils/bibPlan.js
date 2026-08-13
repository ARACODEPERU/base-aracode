export const SCOPE_ALL_BOOKS = 'all_books';
export const SCOPE_SINGLE_BOOK = 'single_book';
export const SCOPE_LIMITED_BOOKS = 'limited_books';

export const SCOPE_LABELS = {
    [SCOPE_SINGLE_BOOK]: 'Un libro',
    [SCOPE_LIMITED_BOOKS]: 'Varios libros',
    [SCOPE_ALL_BOOKS]: 'Todos los libros',
};

export function planBookLabel(plan) {
    if (plan?.scope_type === SCOPE_ALL_BOOKS) {
        return 'Acceso a todos los libros';
    }
    return plan?.books?.[0]?.title || 'Sin libro';
}
