import Swal from 'sweetalert2';

export const BIB_SWAL_DEFAULTS = {
    padding: '2em',
    customClass: 'sweet-alerts',
};

export function bibSwal(options) {
    return Swal.fire({ ...BIB_SWAL_DEFAULTS, ...options });
}
