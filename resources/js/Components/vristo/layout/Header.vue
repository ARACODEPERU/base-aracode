
<script setup>
    import { ref, onMounted, computed, reactive, watch, toRefs } from 'vue';
    import { Link, router, usePage } from '@inertiajs/vue3';
    import { useI18n } from 'vue-i18n';
    import { useAppStore } from '@/stores/index';

    import IconMenu from '@/Components/vristo/icon/icon-menu.vue';
    import IconCalendar from '@/Components/vristo/icon/icon-calendar.vue';
    import IconEdit from '@/Components/vristo/icon/icon-edit.vue';

    import IconSun from '@/Components/vristo/icon/icon-sun.vue';
    import IconMoon from '@/Components/vristo/icon/icon-moon.vue';
    import IconLaptop from '@/Components/vristo/icon/icon-laptop.vue';

    import IconUser from '@/Components/vristo/icon/icon-user.vue';
    import IconLogout from '@/Components/vristo/icon/icon-logout.vue';

    import IconCaretDown from '@/Components/vristo/icon/icon-caret-down.vue';

    import { faCartPlus, faUserGroup, faChartPie } from  '@fortawesome/free-solid-svg-icons';
    import ChatNotifications from 'Modules/CRM/Resources/assets/js/Components/ChatNotifications.vue';
    import ShoppingCartMenu from 'Modules/Onlineshop/Resources/assets/js/Components/ShoppingCartMenu.vue';
    import menuData from './MenuData.js'


    const userData = usePage().props.auth.user;
    const store = useAppStore();

    const hasAnyRole = (rolesToCheck) => {
        return userData.roles.some(role => rolesToCheck.includes(role.name))
    }

    const logout = () => {
        router.post(route('logout'));
        store.clearSidebar()
    }



    const search = ref(false);

    // multi language
    const i18n = reactive(useI18n());

    const baseUrl = assetUrl;


    onMounted(() => {
        setActiveDropdown();
        if(hasAnyRole(['Alumno'])){
            loadTeachers();
        }
    });

    watch(route, (to, from) => {
        setActiveDropdown();
    });

    const setActiveDropdown = () => {
        const selector = document.querySelector('ul.horizontal-menu a[href="' + window.location.href + '"]');
        if (selector) {
            selector.classList.add('active');
            const all  = document.querySelectorAll('ul.horizontal-menu .nav-link.active');
            for (let i = 0; i < all.length; i++) {
                all[0]?.classList.remove('active');
            }
            const ul = selector.closest('ul.sub-menu');
            if (ul) {
                let ele = ul.closest('li.menu').querySelectorAll('.nav-link');
                if (ele) {
                    ele = ele[0];
                    setTimeout(() => {
                        ele?.classList.add('active');
                    });
                }
            }
        }
    };

    const xasset = assetUrl;

    const getImage = (path) => {
        return baseUrl + 'storage/'+ path;
    }

    // 🔥 Función para cargar los docentes y agregarlos al menú

    const menuChatAI = ref({});

    async function loadTeachers() {
        try {
            const response = await axios.post(route('crm_contacts_docents_chat'),{
                timeout: 0,
            });

            const data = response.data;

            // Mapear los docentes a objetos de menú
            const docentesMenu = data.docents.map(docente => ({
                route: route("crm_application_ai_prompt", { cont: docente.person.id }),
                status: false,
                text: docente.person.full_name,
                permissions: "crm_clientes_preguntas_ia",
                img: docente.person.image
            }));

            menuChatAI.value = {
                status: false,
                text: "Chat",
                icom: faUserGroup,
                route: null,
                permissions: "crm_clientes_preguntas_ia",
                items: docentesMenu,
                avatar: true
            };

        } catch (error) {
            console.error("Error cargando docentes:", error);
        }
    }

</script>
<template>

    <header class="z-40" :class="{ dark: store.semidark && store.menu === 'horizontal' }">
        <div class="shadow-sm">
            <div class="relative bg-white flex w-full items-center px-5 py-2.5 dark:bg-[#0e1726]">
                <div class="horizontal-logo flex lg:hidden justify-between items-center ltr:mr-2 rtl:ml-2">
                    <Link :href="route('dashboard')" class="main-logo flex items-center shrink-0 bg-white dark:bg-white rounded-lg p-2">
                        <template v-if="store.theme === 'light'">
                            <img v-if="$page.props.company.isotipo == '/img/isotipo.png'" class="w-8 ltr:-ml-1 rtl:-mr-1 inline" :src="xasset+$page.props.company.isotipo" alt="" />
                            <img v-else class="w-8 ltr:-ml-1 rtl:-mr-1 inline" :src="xasset+'storage/'+$page.props.company.isotipo" alt="" />
                        </template>
                        <template v-if="store.theme === 'dark'">
                            <img v-if="$page.props.company.isotipo_negative == '/img/isotipo_negativo.png'" :src="`${baseUrl}/img/isotipo_negativo.png`" alt="Logo" class="w-8 ltr:-ml-1 rtl:-mr-1 inline" />
                            <img v-else :src="`${baseUrl}storage/${$page.props.company.isotipo_negative}`" alt="Logo" class="w-8 ltr:-ml-1 rtl:-mr-1 inline" />
                        </template>

                        <span
                            class="text-2xl ltr:ml-1.5 rtl:mr-1.5 font-semibold align-middle hidden md:inline dark:text-white-light transition-all duration-300"
                            >{{ $page.props.company.name }}</span
                        >
                    </Link>

                    <a
                        href="javascript:;"
                        class="collapse-icon flex-none dark:text-[#d0d2d6] hover:text-primary dark:hover:text-primary flex lg:hidden ltr:ml-2 rtl:mr-2 p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:bg-white-light/90 dark:hover:bg-dark/60"
                        @click="store.toggleSidebar()"
                    >
                        <icon-menu class="w-5 h-5" />
                    </a>
                </div>
                <div class="ltr:mr-2 rtl:ml-2 hidden sm:block">
                    <ul class="flex items-center space-x-2 rtl:space-x-reverse">
                        <li>
                            <Link :href="route('calendar')"
                                class="flex items-center px-4 py-1.5 text-sm font-medium text-slate-600 bg-slate-100 border border-slate-200 rounded-full hover:bg-white hover:text-blue-600 hover:shadow-sm dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition-all">
                                <icon-calendar class="w-4 h-4 mr-2" />
                                <span>Calendario</span>
                            </Link>
                        </li>
                        <!-- <li v-can="'empresa'">
                            <Link :href="route('company_show')"
                                class="flex items-center px-4 py-1.5 text-sm font-medium text-slate-600 bg-slate-100 border border-slate-200 rounded-full hover:bg-white hover:text-blue-600 hover:shadow-sm dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition-all">
                                <icon-edit class="w-4 h-4 mr-2" />
                                <span>Empresa</span>
                            </Link>
                        </li>
                        <li v-can="'punto_ventas'">
                            <Link :href="route('sales.create')"
                                class="flex items-center px-4 py-1.5 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-100 rounded-full hover:bg-blue-600 hover:text-white dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800 dark:hover:bg-blue-600 transition-all">
                                <font-awesome-icon :icon="faCartPlus" class="w-4 h-4 mr-2" />
                                <span>Vender</span>
                            </Link>
                        </li> -->
                    </ul>
                </div>
                <div class="sm:flex-1 ltr:sm:ml-0 ltr:ml-auto sm:rtl:mr-0 rtl:mr-auto flex items-center space-x-1.5 lg:space-x-2 rtl:space-x-reverse dark:text-[#d0d2d6]">
                    <div class="sm:ltr:mr-auto sm:rtl:ml-auto">
                    </div>
                    <div>
                        <a
                            href="javascript:;"
                            v-show="store.theme === 'light'"
                            class="flex items-center px-4 py-1.5 text-sm font-medium text-slate-600 bg-slate-100 border border-slate-200 rounded-full hover:bg-white hover:text-blue-600 hover:shadow-sm dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition-all"
                            @click="store.toggleTheme('dark')"
                        >
                            <icon-sun class="w-4 h-4 mr-2" />
                            <span>Claro</span>
                        </a>
                        <a
                            href="javascript:;"
                            v-show="store.theme === 'dark'"
                            class="flex items-center px-4 py-1.5 text-sm font-medium text-slate-600 bg-slate-100 border border-slate-200 rounded-full hover:bg-white hover:text-blue-600 hover:shadow-sm dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition-all"
                            @click="store.toggleTheme('light')"
                        >
                            <icon-moon class="w-4 h-4 mr-2" />
                            <span>Oscuro</span>
                        </a>
                        <!-- <a
                            href="javascript:;"
                            v-show="store.theme === 'system'"
                            class="flex items-center p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60"
                            @click="store.toggleTheme('dark')"
                        >
                            <icon-laptop class="w-4 h-4 mr-2" />
                            <span>Sistema</span>
                        </a> -->
                    </div>

                    <div v-can="'crm_chat_notifications'" class="dropdown shrink-0">
                        <ChatNotifications />
                    </div>
                    <ShoppingCartMenu />
                    <div class="dropdown shrink-0">
                        <Popper :placement="store.rtlClass === 'rtl' ? 'bottom-end' : 'bottom-start'" offsetDistance="8" class="!block">
                            <button id="btnHeaderPerfilUser" type="button" class="flex items-center p-1 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-white transition-all group">
                                <img v-if="$page.props.auth.user.avatar"
                                    class="w-7 h-7 rounded-full object-cover"
                                    :src="getImage($page.props.auth.user.avatar)"
                                    :alt="$page.props.auth.user.name"
                                />
                                <img v-else :src="`https://ui-avatars.com/api/?name=${$page.props.auth.user.name}&size=100`" class="w-7 h-7 rounded-full" />
                                <icon-caret-down class="w-4 h-4 mx-1 text-slate-400 group-hover:text-slate-600" />
                            </button>
                            <template #content="{ close }">
                                <ul class="text-dark dark:text-white-dark !py-0 w-[230px] font-semibold dark:text-white-light/90">
                                    <li>
                                        <div class="flex items-center px-4 py-4">
                                            <div class="flex-none">
                                                <img v-if="$page.props.auth.user.avatar" class="rounded-md w-10 h-10 object-cover" :src="getImage($page.props.auth.user.avatar)" alt="" />
                                                <img v-else :src="`https://ui-avatars.com/api/?name=${$page.props.auth.user.name}&size=150&rounded=true`" class="rounded-md w-10 h-10 object-cover" :alt="$page.props.auth.user.name"/>
                                            </div>
                                            <div class="ltr:pl-4 rtl:pr-4 truncate">
                                                <h4 class="text-base">
                                                    {{ $page.props.auth.user.name }}
                                                    <span class="text-xs bg-success-light rounded text-success px-1 ltr:ml-2 rtl:ml-2">Pro</span>
                                                </h4>
                                                <a class="text-black/60 hover:text-primary dark:text-dark-light/60 dark:hover:text-white" href="javascript:;">
                                                    {{ $page.props.auth.user.email }}
                                                </a>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <Link :href="route('profile.edit')" class="dark:hover:text-white" @click="close()">
                                            <icon-user class="w-4.5 h-4.5 ltr:mr-2 rtl:ml-2 shrink-0" />
                                            Perfil
                                        </Link>
                                    </li>

                                    <li class="border-t border-white-light dark:border-white-light/10">
                                        <Link href="#" @click="logout" class="text-danger !py-3" >
                                            <icon-logout class="w-4.5 h-4.5 ltr:mr-2 rtl:ml-2 rotate-90 shrink-0" />
                                            Cerrar sesión
                                        </Link>
                                    </li>
                                </ul>
                            </template>
                        </Popper>
                    </div>
                </div>
            </div>

                            <!-- horizontal menu -->
            <ul class="horizontal-menu hidden flex flex-wrap gap-x-4 gap-y-0 py-1.5 font-semibold px-6 bg-white border-t border-[#ebedf2] dark:border-[#191e3a] dark:bg-[#0e1726] text-black dark:text-white-dark">
                <template v-for="(item, index) in menuData" :key="index">
                    <template v-if="item.route == null">
                        <li v-can="item.permissions" class="menu nav-item relative">
                            <a href="javascript:;" class="nav-link">
                                <div class="flex items-center">
                                    <font-awesome-icon :icon="item.icom" class="shrink-0" />
                                    <span class="px-2">{{ item.text }}</span>
                                </div>
                                <div class="right_arrow">
                                    <icon-caret-down />
                                </div>
                            </a>
                            <template v-if="item.items && item.items.length > 0" >
                                <ul class="sub-menu">
                                    <li v-for="(subItem, subIndex) in item.items" :key="subIndex">
                                        <Link :href="subItem.route">{{ subItem.text }}</Link>
                                    </li>
                                </ul>
                            </template>
                        </li>
                    </template>
                    <template v-else-if="item.route == 'module'">
                        <li v-can="item.permissions" class="menu nav-item relative">
                            <a href="javascript:;" class="nav-link">
                                <div class="flex items-center">
                                    <font-awesome-icon :icon="item.icom" class="shrink-0" />
                                    <span class="px-2">{{ item.text }}</span>
                                </div>
                                <div class="right_arrow">
                                    <icon-caret-down />
                                </div>
                            </a>
                            <ul class="sub-menu">
                                <template v-if="item.items && item.items.length > 0" >
                                    <template v-for="(subItem, subIndex) in item.items" :key="subIndex">
                                        <template v-if="subItem.route == null">
                                            <li
                                                v-can="subItem.permissions"
                                                class="relative"
                                            >
                                                <a href="javascript:;" >
                                                    {{ subItem.text }}
                                                    <div class="ltr:ml-auto rtl:mr-auto rtl:rotate-90 -rotate-90">
                                                        <icon-caret-down />
                                                    </div>
                                                </a>
                                                <ul
                                                    v-if="subItem.items && subItem.items.length > 0"
                                                    class="rounded absolute top-0 left-[95%] min-w-[180px] bg-white z-[10] text-dark dark:text-white-dark dark:bg-[#1b2e4b] shadow p-0 py-2 hidden">
                                                    <template v-for="(subSubItem, subSubIndex) in subItem.items" :key="subSubIndex">
                                                        <li v-can="subSubItem.permissions">
                                                            <Link :href="subSubItem.route">{{ subSubItem.text }}</Link>
                                                        </li>
                                                    </template>
                                                </ul>
                                            </li>
                                        </template>
                                        <template v-else>
                                            <li v-can="subItem.permissions">
                                                <Link :href="subItem.route">{{ subItem.text }}</Link>
                                            </li>
                                        </template>
                                    </template>
                                </template>
                            </ul>
                        </li>
                    </template>
                    <template v-else>
                        <li v-can="item.permissions" class="menu nav-item relative">
                            <Link :href="item.route" class="nav-link">
                                <div class="flex items-center">
                                    <font-awesome-icon :icon="item.icom" class="shrink-0" />
                                    <span class="px-2">{{ item.text }}</span>
                                </div>
                            </Link>
                        </li>
                    </template>
                </template>
                <template v-if="hasAnyRole(['Alumno'])">
                    <li v-can="menuChatAI.permissions" class="menu nav-item relative">
                        <a href="javascript:;" class="nav-link">
                            <div class="flex items-center">
                                <!-- <font-awesome-icon :icon="menuChatAI.icom" class="shrink-0" /> -->
                                <span class="px-2">{{ menuChatAI.text }}</span>
                            </div>
                            <div class="right_arrow">
                                <icon-caret-down />
                            </div>
                        </a>
                        <ul class="sub-menu">
                            <template v-if="menuChatAI.items && menuChatAI.items.length > 0" >
                                <template v-for="(subItem, subIndex) in menuChatAI.items" :key="subIndex">
                                    <li v-can="subItem.permissions">
                                        <Link :href="subItem.route">{{ subItem.text }}</Link>
                                    </li>
                                </template>
                            </template>
                        </ul>
                    </li>
                </template>
            </ul>
        </div>
    </header>
</template>

