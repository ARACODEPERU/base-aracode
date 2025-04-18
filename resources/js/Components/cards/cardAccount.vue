<script setup>
    import useClipboard from 'vue-clipboard3';
    import iconHorizontalDots from '@/Components/vristo/icon/icon-horizontal-dots.vue';
    import Swal from "sweetalert2";
    import { router } from '@inertiajs/vue3'

    const props = defineProps({
        bankAccount: {
            type: Object,
            default: () =>({})
        }
    });

    const { toClipboard } = useClipboard();

    const copyText = async (item) => {
        let msg = `Número de cuenta soles ${item.number} N° de cuenta interbancario (cci) ${item.cci}`;
        if (msg) {
            await toClipboard(msg);
            showMessage('Copiado exitosamente.','success');
        }
    };

    const showMessage = (msg = '', type = 'success') => {
        const toast = Swal.mixin({
            toast: true,
            position: 'top',
            showConfirmButton: false,
            timer: 3000,
            customClass: { container: 'toast' },
        });
        toast.fire({
            icon: type,
            title: msg,
            padding: '10px 20px',
        });
    };

    const emit = defineEmits(['openModalEditAccount']);

    const editAccount = (item) => {
        emit('openModalEditAccount', item);
    }

    const destroyAccount = (id) => {
        Swal.fire({
            title: '¿Estas seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Eliminar!',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            padding: '2em',
            customClass: 'sweet-alerts',
            backdrop: true,
            preConfirm: () => {
                return axios.delete(route('bank-account-destroy', id)).then((res) => {
                    if (!res.data.success) {
                        Swal.showValidationMessage(res.data.message)
                    }
                    return res
                });
            },
            allowOutsideClick: () => !Swal2.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Enhorabuena',
                    text: 'Se Eliminó correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                    backdrop: true,
                });
                router.visit(route('company_show'), {
                    method: 'get',
                    replace: false,
                    preserveState: true,
                    preserveScroll: true,
                });
            }
        });
    }
</script>
<template>
    <div class="panel dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-[#e0e6ed] dark:border-[#1b2e4b] -m-5 mb-5 p-5">
            <a href="javascript:;" class="flex font-semibold">
                <img :src="bankAccount.bank.image" class="shrink-0 w-10 h-10 rounded-md flex items-center justify-center text-white ltr:mr-4 rtl:ml-4" />
                <div>
                    <h6>{{ bankAccount.bank.full_name }}</h6>
                    <p class="text-xs text-white-dark mt-1">{{ bankAccount.description }}</p>
                </div>
            </a>
            <div class="dropdown">
                <Popper :placement="'bottom-end'" offsetDistance="0" class="align-middle">
                    <button type="button">
                        <icon-horizontal-dots class="w-5 h-5 text-black/70 dark:text-white/70 hover:!text-primary" />
                    </button>
                    <template #content="{ close }">
                        <ul @click="close()">
                            <li>
                                <a @click="editAccount(bankAccount)" href="javascript:;">Editar</a>
                            </li>
                            <li>
                                <a @click="destroyAccount(bankAccount.id)" href="javascript:;">Eliminar</a>
                            </li>
                        </ul>
                    </template>
                </Popper>
            </div>
        </div>
        <div class="group">
            <div class="mb-5">
                <form>
                    <div class="flex">
                        <span class="shrink-0 grid place-content-center text-base w-9 h-9 rounded-md bg-success-light dark:bg-success text-success dark:text-success-light">#</span>
                        <div class="px-3 flex-1">
                            <p class="font-semibold text-lg dark:text-white-light">Número de cuenta {{ bankAccount.currency_type_id == 'PEN' ? 'soles' : 'dolares' }}</p>
                            <div>{{ bankAccount.number }}</div>
                            <div class="text-sm text-white-dark dark:text-gray-500">N° de cuenta interbancario (cci)</div>
                            <div>{{ bankAccount.cci }}</div>
                        </div>
                    </div>
                    <div class="flex flex-wrap justify-end gap-2 mt-5">
                        <button type="button" class="btn btn-outline-info text-xs btn-sm uppercase" @click="copyText(bankAccount)">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" >
                                <path
                                d="M6 11C6 8.17157 6 6.75736 6.87868 5.87868C7.75736 5 9.17157 5 12 5H15C17.8284 5 19.2426 5 20.1213 5.87868C21 6.75736 21 8.17157 21 11V16C21 18.8284 21 20.2426 20.1213 21.1213C19.2426 22 17.8284 22 15 22H12C9.17157 22 7.75736 22 6.87868 21.1213C6 20.2426 6 18.8284 6 16V11Z"
                                stroke="currentColor"
                                stroke-width="1.5"
                                />
                                <path
                                opacity="0.5"
                                d="M6 19C4.34315 19 3 17.6569 3 16V10C3 6.22876 3 4.34315 4.17157 3.17157C5.34315 2 7.22876 2 11 2H15C16.6569 2 18 3.34315 18 5"
                                stroke="currentColor"
                                stroke-width="1.5"
                                />
                            </svg>
                            Copiar cuentas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

