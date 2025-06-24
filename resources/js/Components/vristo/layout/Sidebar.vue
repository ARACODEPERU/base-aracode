<script setup>
    import { ref, onMounted } from 'vue';

    import { useAppStore } from '@/stores/index';
    import IconCaretsDown from '@/Components/vristo/icon/icon-carets-down.vue';
    import VueCollapsible from 'vue-height-collapsible/vue3';
    import IconMinus from '@/Components/vristo/icon/icon-minus.vue';

    import IconCaretDown from '@/Components/vristo/icon/icon-caret-down.vue';
    import { faUserGroup } from  '@fortawesome/free-solid-svg-icons';

    import { Link, usePage } from '@inertiajs/vue3';
    import menuData from './MenuData.js'

    const store = useAppStore();
    const activeDropdown = ref('');
    const subActive = ref('');

    const userData = usePage().props.auth.user;

    const hasAnyRole = (rolesToCheck) => {
        return userData.roles.some(role => rolesToCheck.includes(role.name))
    }

    onMounted(() => {
        //console.log('ruta actual',window.location.href)
        const selector = document.querySelector('.sidebar ul a[href="' + window.location.href + '"]');
        if (selector) {
            selector.classList.add('active');
            const ul = selector.closest('ul.sub-menu-before');
            if (ul) {
                let ele = ul.closest('li.menu').querySelectorAll('.nav-link') || [];
                if (ele.length) {
                    ele = ele[0];
                    setTimeout(() => {
                        ele.click();
                    });
                }
            }
        }

        getStudentCertificates();

        if(hasAnyRole(['Alumno'])){
            loadTeachers();
        }

    });

    const toggleMobileMenu = () => {
        if (window.innerWidth < 1024) {
            store.toggleSidebar();
        }
    };

    const studentSCertificates = ref([]);

    const getStudentCertificates = () =>{
        const roles = usePage().props.auth.roles;

        // Verifica si el usuario tiene el rol "Alumno"
        if (roles.includes('Alumno')) {
            //console.log('aca llega');
            axios({
                method: 'post',
                url: route('aca_certificate_by_student')
            }).then((response) => {
                //console.log('resouesta',response)
                studentSCertificates.value = response.data.certificates
                //console.log('ser ',studentSCertificates.value)
            });
        }

    }

    const getFormatDate = (dateString) => {
        const date = new Date(dateString.replace(' ', 'T')); // Convierte a formato válido
        return new Intl.DateTimeFormat('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        }).format(date);
    };

    const xasset = assetUrl;

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

            if(docentesMenu && docentesMenu.length > 0){
                menuChatAI.value = {
                    status: false,
                    text: "Asesorias",
                    icom: faUserGroup,
                    route: null,
                    permissions: "crm_clientes_preguntas_ia",
                    items: docentesMenu,
                    avatar: true
                };
            }

        } catch (error) {
            console.error("Error cargando docentes:", error);
        }
    }

</script>
<template>
    <div :class="{ 'dark text-white-dark': store.semidark }">
        <nav class="sidebar fixed min-h-screen h-full top-0 bottom-0 w-[260px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] z-50 transition-all duration-300">
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
                        wheelPropagation: false,
                    }"
                    class="h-[calc(100vh-80px)] relative"
                >
                    <ul class="relative font-semibold space-y-0.5 p-4 py-0">

                        <template v-for="(item, index) in menuData" :key="index">
                            <template v-if="item.route == null">
                                <li v-can="item.permissions" class="menu nav-item">
                                    <button
                                        type="button"
                                        class="nav-link group w-full"
                                        :class="{ active: activeDropdown === item.text }"
                                        @click="activeDropdown === item.text ? (activeDropdown = null) : (activeDropdown = item.text)"
                                    >
                                        <div class="flex items-center">
                                            <font-awesome-icon :icon="item.icom" class="group-hover:!text-primary shrink-0" />
                                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">
                                                {{ item.text }}
                                            </span>
                                        </div>
                                        <div :class="{ 'rtl:rotate-90 -rotate-90': activeDropdown !== item.text }">
                                            <icon-caret-down />
                                        </div>
                                    </button>
                                    <vue-collapsible :isOpen="activeDropdown === item.text">
                                        <template v-if="item.items && item.items.length > 0" >
                                            <ul class="sub-menu-before text-gray-500">
                                                <li v-for="(subItem, subIndex) in item.items" :key="subIndex">
                                                    <Link :href="subItem.route" @click="toggleMobileMenu">{{ subItem.text }}</Link>
                                                </li>
                                            </ul>
                                        </template>
                                    </vue-collapsible>
                                </li>
                            </template>
                            <template v-else-if="item.route == 'module'">
                                <h2 v-can="item.permissions" class="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                                    <icon-minus class="w-4 h-5 flex-none hidden" />
                                    <span class="">{{ item.text }}</span>
                                </h2>
                                <template v-if="item.items && item.items.length > 0" >
                                    <template v-for="(subItem, subIndex) in item.items" :key="subIndex">
                                        <template v-if="subItem.route == null">
                                            <li v-can="subItem.permissions" class="menu nav-item">
                                                <button
                                                    type="button"
                                                    class="nav-link group w-full"
                                                    :class="{ active: activeDropdown === subItem.text }"
                                                    @click="activeDropdown === subItem.text ? (activeDropdown = null) : (activeDropdown = subItem.text)"
                                                >
                                                    <div class="flex items-center">
                                                        <font-awesome-icon :icon="subItem.icom" class="group-hover:!text-primary shrink-0" />
                                                        <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">
                                                            {{ subItem.text }}
                                                        </span>
                                                    </div>
                                                    <div :class="{ 'rtl:rotate-90 -rotate-90': activeDropdown !== subItem.text }">
                                                        <icon-caret-down />
                                                    </div>
                                                </button>
                                                <vue-collapsible :isOpen="activeDropdown === subItem.text">
                                                    <ul v-if="subItem.items && subItem.items.length > 0"
                                                        class="text-gray-500"
                                                        :class="subItem.avatar ? 'sub-menu-avatar' : 'sub-menu-before'"
                                                    >
                                                        <template v-for="(subSubItem, subSubIndex) in subItem.items" :key="subSubIndex">
                                                            <li v-can="subSubItem.permissions">
                                                                <Link
                                                                    v-bind="{ id: subSubItem.id }"
                                                                    :href="subSubItem.route"
                                                                    @click="toggleMobileMenu"
                                                                    preserve-state
                                                                    preserve-scroll
                                                                >
                                                                    <template v-if="subSubItem.img">
                                                                        <div class="ltr:mr-2 rtl:ml-2">
                                                                            <img :src="`${xasset}storage/${subSubItem.img}`" alt="" class="w-8 h-8 rounded" />
                                                                        </div>
                                                                        <div class="flex-1 text-xs">
                                                                            {{ subSubItem.text }}
                                                                        </div>
                                                                    </template>
                                                                    <template v-else>
                                                                        {{ subSubItem.text }}
                                                                    </template>
                                                                </Link>
                                                            </li>
                                                        </template>
                                                    </ul>
                                                </vue-collapsible>
                                            </li>
                                        </template>
                                        <template v-else>
                                            <li v-can="subItem.permissions" class="menu nav-item">
                                                <Link
                                                    v-bind="{ id: subItem.id }"
                                                    :href="subItem.route"
                                                    class="nav-link group"
                                                    @click="toggleMobileMenu"
                                                    preserve-state
                                                    preserve-scroll
                                                >
                                                    <div class="flex items-center">
                                                        <font-awesome-icon :icon="subItem.icom" class="group-hover:!text-primary shrink-0" />

                                                        <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">
                                                            {{ subItem.text }}
                                                        </span>
                                                    </div>
                                                </Link>
                                            </li>
                                        </template>
                                    </template>
                                </template>
                            </template>
                            <template v-else>
                                <li v-can="item.permissions" class="menu nav-item">
                                    <Link
                                        :href="item.route"
                                        class="nav-link group"
                                        @click="toggleMobileMenu"
                                        preserve-state
                                        preserve-scroll
                                    >
                                        <div class="flex items-center">
                                            <!-- <icon-menu-font-icons class="group-hover:!text-primary shrink-0" /> -->
                                            <font-awesome-icon :icon="item.icom" class="group-hover:!text-primary shrink-0" />
                                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">
                                                {{ item.text }}
                                            </span>
                                        </div>
                                    </Link>
                                </li>
                            </template>

                        </template>
                        <template v-if="studentSCertificates && studentSCertificates.length > 0">
                            <li class="menu nav-item">
                                <button
                                    type="button"
                                    class="nav-link group w-full"
                                    :class="{ active: activeDropdown === 'logros' }"
                                    @click="activeDropdown === 'logros' ? (activeDropdown = null) : (activeDropdown = 'logros')"
                                >
                                    <div class="flex items-center">
                                        <svg class="group-hover:!text-primary shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                                            <path fill="currentColor" d="M372.2 52c0 20.9-12.4 39-30.2 47.2L448 192l104.4-20.9c-5.3-7.7-8.4-17.1-8.4-27.1c0-26.5 21.5-48 48-48s48 21.5 48 48c0 26-20.6 47.1-46.4 48L481 442.3c-10.3 23-33.2 37.7-58.4 37.7l-205.2 0c-25.2 0-48-14.8-58.4-37.7L46.4 192C20.6 191.1 0 170 0 144c0-26.5 21.5-48 48-48s48 21.5 48 48c0 10.1-3.1 19.4-8.4 27.1L192 192 298.1 99.1c-17.7-8.3-30-26.3-30-47.1c0-28.7 23.3-52 52-52s52 23.3 52 52z"/>
                                        </svg>
                                        <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">
                                            Logros
                                        </span>
                                    </div>
                                    <div :class="{ 'rtl:rotate-90 -rotate-90': activeDropdown !== 'logros' }">
                                        <icon-caret-down />
                                    </div>
                                </button>
                                <vue-collapsible :isOpen="activeDropdown === 'logros'">
                                    <ul class="sub-menu-before text-gray-500">
                                        <li v-for="(scd, sck) in studentSCertificates" :key="sck">
                                            <a :href="route('aca_image_download', scd.id)" target="_blank">
                                                <div>
                                                    <p class="text-primary">
                                                        {{ scd.course.description }}
                                                    </p>
                                                    <span class="text-xs">Otorgado {{ getFormatDate(scd.created_at) }}</span>
                                                </div>
                                            </a>
                                        </li>
                                    </ul>
                                </vue-collapsible>
                            </li>
                        </template>
                        <template v-if="menuChatAI">
                            <template v-if="hasAnyRole(['Alumno'])">
                                <li v-if="menuChatAI.permissions" class="menu nav-item">
                                    <button
                                        type="button"
                                        class="nav-link group w-full"
                                        :class="{ active: activeDropdown === 'logros' }"
                                        @click="activeDropdown === menuChatAI.text ? (activeDropdown = null) : (activeDropdown = menuChatAI.text)"
                                    >
                                        <div class="flex items-center">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="group-hover:!text-primary shrink-0">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M10.4036 22.4797L10.6787 22.015C11.1195 21.2703 11.3399 20.8979 11.691 20.6902C12.0422 20.4825 12.5001 20.4678 13.4161 20.4385C14.275 20.4111 14.8523 20.3361 15.3458 20.1317C16.385 19.7012 17.2106 18.8756 17.641 17.8365C17.9639 17.0571 17.9639 16.0691 17.9639 14.093V13.2448C17.9639 10.4683 17.9639 9.08006 17.3389 8.06023C16.9892 7.48958 16.5094 7.0098 15.9388 6.66011C14.919 6.03516 13.5307 6.03516 10.7542 6.03516H8.20964C5.43314 6.03516 4.04489 6.03516 3.02507 6.66011C2.45442 7.0098 1.97464 7.48958 1.62495 8.06023C1 9.08006 1 10.4683 1 13.2448V14.093C1 16.0691 1 17.0571 1.32282 17.8365C1.75326 18.8756 2.57886 19.7012 3.61802 20.1317C4.11158 20.3361 4.68882 20.4111 5.5477 20.4385C6.46368 20.4678 6.92167 20.4825 7.27278 20.6902C7.6239 20.8979 7.84431 21.2703 8.28514 22.015L8.5602 22.4797C8.97002 23.1721 9.9938 23.1721 10.4036 22.4797ZM13.1928 14.5171C13.7783 14.5171 14.253 14.0424 14.253 13.4568C14.253 12.8713 13.7783 12.3966 13.1928 12.3966C12.6072 12.3966 12.1325 12.8713 12.1325 13.4568C12.1325 14.0424 12.6072 14.5171 13.1928 14.5171ZM10.5422 13.4568C10.5422 14.0424 10.0675 14.5171 9.48193 14.5171C8.89637 14.5171 8.42169 14.0424 8.42169 13.4568C8.42169 12.8713 8.89637 12.3966 9.48193 12.3966C10.0675 12.3966 10.5422 12.8713 10.5422 13.4568ZM5.77108 14.5171C6.35664 14.5171 6.83133 14.0424 6.83133 13.4568C6.83133 12.8713 6.35664 12.3966 5.77108 12.3966C5.18553 12.3966 4.71084 12.8713 4.71084 13.4568C4.71084 14.0424 5.18553 14.5171 5.77108 14.5171Z" fill="currentColor"></path>
                                                <path opacity="0.5" d="M15.486 1C16.7529 0.999992 17.7603 0.999986 18.5683 1.07681C19.3967 1.15558 20.0972 1.32069 20.7212 1.70307C21.3632 2.09648 21.9029 2.63623 22.2963 3.27821C22.6787 3.90219 22.8438 4.60265 22.9226 5.43112C22.9994 6.23907 22.9994 7.24658 22.9994 8.51343V9.37869C22.9994 10.2803 22.9994 10.9975 22.9597 11.579C22.9191 12.174 22.8344 12.6848 22.6362 13.1632C22.152 14.3323 21.2232 15.2611 20.0541 15.7453C20.0249 15.7574 19.9955 15.7691 19.966 15.7804C19.8249 15.8343 19.7039 15.8806 19.5978 15.915H17.9477C17.9639 15.416 17.9639 14.8217 17.9639 14.093V13.2448C17.9639 10.4683 17.9639 9.08006 17.3389 8.06023C16.9892 7.48958 16.5094 7.0098 15.9388 6.66011C14.919 6.03516 13.5307 6.03516 10.7542 6.03516H8.20964C7.22423 6.03516 6.41369 6.03516 5.73242 6.06309V4.4127C5.76513 4.29934 5.80995 4.16941 5.86255 4.0169C5.95202 3.75751 6.06509 3.51219 6.20848 3.27821C6.60188 2.63623 7.14163 2.09648 7.78361 1.70307C8.40759 1.32069 9.10805 1.15558 9.93651 1.07681C10.7445 0.999986 11.7519 0.999992 13.0188 1H15.486Z" fill="currentColor"></path>
                                            </svg>
                                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">
                                                {{ menuChatAI.text }}
                                            </span>
                                        </div>
                                        <div :class="{ 'rtl:rotate-90 -rotate-90': activeDropdown !== menuChatAI.text }">
                                            <icon-caret-down />
                                        </div>
                                    </button>
                                    <vue-collapsible :isOpen="activeDropdown === menuChatAI.text">
                                        <ul
                                            class="text-gray-500"
                                            :class="menuChatAI.avatar ? 'sub-menu-avatar' : 'sub-menu-before'"
                                        >
                                            <li v-for="(scd, sck) in menuChatAI.items" :key="sck">
                                                <Link
                                                    v-bind="{ id: scd.id }"
                                                    :href="scd.route"
                                                    @click="toggleMobileMenu"
                                                    preserve-state
                                                    preserve-scroll
                                                >
                                                    <template v-if="scd.img">
                                                        <div class="ltr:mr-2 rtl:ml-2">
                                                            <img :src="`${xasset}storage/${scd.img}`" alt="" class="w-8 h-8 rounded" />
                                                        </div>
                                                        <div class="flex-1 text-xs">
                                                            {{ scd.text }}
                                                        </div>
                                                    </template>
                                                    <template v-else>
                                                        {{ scd.text }}
                                                    </template>
                                                </Link>
                                            </li>
                                        </ul>
                                    </vue-collapsible>
                                </li>
                            </template>
                        </template>

                        <li v-can="'crm_chatbot'" class="menu nav-item">
                            <Link
                                :href="route('crm_dasboard_chatbot')"
                                class="nav-link group"
                                @click="toggleMobileMenu"
                                preserve-state
                                preserve-scroll
                            >
                                <div class="flex items-center">
                                    <!-- <icon-menu-font-icons class="group-hover:!text-primary shrink-0" /> -->
                                    <svg class="group-hover:!text-primary shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                                        <path fill="currentColor" d="M320 0c17.7 0 32 14.3 32 32l0 64 120 0c39.8 0 72 32.2 72 72l0 272c0 39.8-32.2 72-72 72l-304 0c-39.8 0-72-32.2-72-72l0-272c0-39.8 32.2-72 72-72l120 0 0-64c0-17.7 14.3-32 32-32zM208 384c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zM264 256a40 40 0 1 0 -80 0 40 40 0 1 0 80 0zm152 40a40 40 0 1 0 0-80 40 40 0 1 0 0 80zM48 224l16 0 0 192-16 0c-26.5 0-48-21.5-48-48l0-96c0-26.5 21.5-48 48-48zm544 0c26.5 0 48 21.5 48 48l0 96c0 26.5-21.5 48-48 48l-16 0 0-192 16 0z"/>
                                    </svg>
                                    <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">
                                        Chatbot
                                    </span>
                                </div>
                            </Link>
                        </li>
                    </ul>

                </perfect-scrollbar>

            </div>

        </nav>

    </div>
</template>

<style>
ul.sub-menu-before li a.active {
  --tw-text-opacity: 1;
  color: rgb(67 97 238 / var(--tw-text-opacity));
}
</style>
