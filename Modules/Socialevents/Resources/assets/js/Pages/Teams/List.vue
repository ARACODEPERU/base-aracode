<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import Keypad from '@/Components/Keypad.vue';
    import Pagination from '@/Components/Pagination.vue';
    import Swal2 from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';

    import IconPencilPaper from '@/Components/vristo/icon/icon-pencil-paper.vue';
    import iconTrashLines from '@/Components/vristo/icon/icon-trash-lines.vue';

    const props = defineProps({
        teams: {
            type: Object,
            default: () => ({}),
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    });

    const form = useForm({
        search: props.filters.search,
    });

    const xhttp =  assetUrl;

    const destroyTeam = (id) => {
        Swal2.fire({
            title: '¿Estas seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Eliminar!',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            padding: '2em',
            customClass: 'sweet-alerts',
            preConfirm: () => {
                return axios.delete(route('even_equipos_destroy', id)).then((res) => {
                    if (!res.data.success) {
                        Swal2.showValidationMessage(res.data.message)
                    }
                    return res
                });
            },
            allowOutsideClick: () => !Swal2.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se Eliminó correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                router.visit(route('even_equipos_listado'), {
                    replace: true,
                    method: 'get',
                    only: ['teams'],
                });
            }
        });
    }
</script>
<template>
    <AppLayout title="Equipos">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Equipos</span>
            </li>
        </Navigation>
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="grid grid-cols-3 w-full">
                    <div class="col-span-3 sm:col-span-1">
                        <form id="form-search-items" @submit.prevent="form.get(route('even_equipos_listado'))">
                            <label for="table-search" class="sr-only">Search</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                                </div>
                                <input v-model="form.search" type="text" class="block pl-10 form-input w-80" placeholder="Buscar por Descripción">
                            </div>
                        </form>
                    </div>
                    <div class="col-span-3 sm:col-span-2">
                        <Keypad>
                            <template #botones>
                                <Link v-can="'even_equipos_nuevo'" :href="route('even_equipos_create')" class="flex items-center justify-center inline-block px-6 py-2.5 bg-blue-900 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out">
                                    Nuevo
                                </Link>
                            </template>
                        </Keypad>
                    </div>
                </div>
            </div>
            <div class="mt-5">
                <!-- Grid -->
                <div class="grid grid-cols-1
                    sm:grid-cols-2
                    md:grid-cols-3
                    lg:grid-cols-3
                    xl:grid-cols-3
                    2xl:grid-cols-5
                    gap-6"
                >
                    <!-- From Uiverse.io by narmesh_sah -->
                    <div v-for="team in teams.data" class="panel profile-card">
                        <div class="profile-image">
                            <div>
                                <img v-if="team.logo_path" :src="xhttp + 'storage/' + team.logo_path" />
                                <img v-else :src="'/img/team1.png'" />
                            </div>
                        </div>
                        <div class="profile-info">
                            <p class="profile-name">{{ team.name }}</p>
                            <div class="profile-title">
                                <template v-if="team.manager">@{{ team.manager.full_name }}</template>
                                <template v-else>No tiene representante</template>
                            </div>
                            <div class="profile-bio">
                            Creative design and web enthusiast. Building digital experiences that
                            matter.
                            </div>
                        </div>
                        <div class="social-links">
                            <Link :href="route('even_equipos_edit', team.id)" v-tippy="{content:'Editar', placement:'bottom'}" class="btn btn-info w-10 h-10 p-0 rounded-full toolbar-btn">
                                <IconPencilPaper class="w-5 h-5" />
                            </Link>
                            <!-- <button v-tippy="{content:'Jugadores',placement:'bottom'}" class="btn btn-success w-10 h-10 p-0 rounded-full toolbar-btn">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                    <path fill="currentColor" d="M96 192C96 130.1 146.1 80 208 80C269.9 80 320 130.1 320 192C320 253.9 269.9 304 208 304C146.1 304 96 253.9 96 192zM32 528C32 430.8 110.8 352 208 352C305.2 352 384 430.8 384 528L384 534C384 557.2 365.2 576 342 576L74 576C50.8 576 32 557.2 32 534L32 528zM464 128C517 128 560 171 560 224C560 277 517 320 464 320C411 320 368 277 368 224C368 171 411 128 464 128zM464 368C543.5 368 608 432.5 608 512L608 534.4C608 557.4 589.4 576 566.4 576L421.6 576C428.2 563.5 432 549.2 432 534L432 528C432 476.5 414.6 429.1 385.5 391.3C408.1 376.6 435.1 368 464 368z"/>
                                </svg>
                            </button> -->
                            <button @click="destroyTeam(team.id)" v-tippy="{content:'Eliminar', placement:'bottom'}" class="btn btn-danger w-10 h-10 p-0 rounded-full toolbar-btn">
                                <iconTrashLines class="w-5 h-5" />
                            </button>
                        </div>
                        <div class="stats">
                            <div class="stat-item">
                                <div class="stat-value">15k</div>
                                <div class="stat-label">Followers</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">82</div>
                                <div class="stat-label">Posts</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">4.8</div>
                                <div class="stat-label">Rating</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Grid -->
                 <Pagination :data="teams" class="mt-6" />
            </div>
        </div>
    </AppLayout>
</template>
<style lang="css" scoped>
   /* From Uiverse.io by narmesh_sah */
    .profile-card {
        -webkit-backdrop-filter: blur(48px);
        backdrop-filter: blur(48px);
    }

    .profile-image {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background-color: #f0f2f5;
        margin: 0 auto 1rem;
        overflow: hidden;
        border: 3px solid white;
        box-shadow: 0px 0px 6px rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .profile-image::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 96px;
        background-color: #7d988a;
        border-radius: 0.375rem 0.375rem 0 0;
        z-index: -1;
    }

    .profile-info {
        text-align: left;
        margin-bottom: 1.5rem;
    }

    .profile-name {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1a1a1a;
        margin-bottom: 0.25rem;
    }

    .profile-title {
        font-size: 0.9rem;
        color: #666;
        margin-bottom: 0.5rem;
    }

    .profile-bio {
        font-size: 0.85rem;
        color: #777;
        line-height: 1.4;
        margin-bottom: 1.5rem;
    }

    .social-links {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .toolbar-btn {
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .toolbar-btn:hover {
        transform: translateY(-6px);
    }

    /* Specific colors for each social platform */
    .social-btn.twitter:hover svg {
        fill: #1da1f2;
    }
    .social-btn.instagram:hover svg {
        fill: #e4405f;
    }
    .social-btn.linkedin:hover svg {
        fill: #0a66c2;
    }
    .social-btn.github:hover svg {
        fill: #333333;
    }

    .cta-button {
        width: 100%;
        padding: 0.8rem;
        border: none;
        border-radius: 10px;
        background: #7d988a;
        color: white;
        font-weight: 600;
        cursor: pointer;
        transition:
            transform 0.2s,
            background 0.2s;
    }

    .cta-button:hover {
        background: #4d5d54;
        transform: translateY(-2px);
    }

    .stats {
        display: flex;
        justify-content: space-between;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }

    .stat-item {
        text-align: center;
    }

    .stat-value {
        font-weight: 600;
        color: #1a1a1a;
    }

    .stat-label {
        font-size: 0.8rem;
        color: #666;
    }

</style>
