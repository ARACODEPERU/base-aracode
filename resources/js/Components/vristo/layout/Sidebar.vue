<script setup>
    import { ref, onMounted } from 'vue';

    import { useAppStore } from '@/stores/index';
    import IconCaretsDown from '@/Components/vristo/icon/icon-carets-down.vue';

    import IconMinus from '@/Components/vristo/icon/icon-minus.vue';

    import IconCaretDown from '@/Components/vristo/icon/icon-caret-down.vue';


    import { Link, usePage } from '@inertiajs/vue3';
    import menuData from './MenuData.js'

    const store = useAppStore();
    const activeDropdown = ref('');

    onMounted(() => {
        const selector = document.querySelector('.sidebar ul a[href="' + window.location.pathname + '"]');
        if (selector) {
            selector.classList.add('active');
            const ul = selector.closest('ul.sub-menu-before');
            if (ul) {
                let ele  = ul.closest('li.menu').querySelectorAll('.nav-link') || [];
                if (ele.length) {
                    ele = ele[0];
                    setTimeout(() => {
                        ele.click();
                    });
                }
            }
        }

        getStudentCertificates();

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
                                    <HeightTransition v-show="activeDropdown === item.text">
                                        <template v-if="item.items && item.items.length > 0" >
                                            <ul class="sub-menu-before text-gray-500">
                                                <li v-for="(subItem, subIndex) in item.items" :key="subIndex">
                                                    <Link :href="subItem.route" @click="toggleMobileMenu">{{ subItem.text }}</Link>
                                                </li>
                                            </ul>
                                        </template>
                                    </HeightTransition>
                                </li>
                            </template>
                            <template v-else-if="item.route == 'module'">
                                <h2 v-can="item.permissions" class="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                                    <icon-minus class="w-4 h-5 flex-none hidden" />
                                    <span>{{ item.text }}</span>
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
                                                <HeightTransition v-show="activeDropdown === subItem.text">
                                                    <ul v-if="subItem.items && subItem.items.length > 0"
                                                        class="text-gray-500"
                                                        :class="subItem.avatar ? 'sub-menu-avatar' : 'sub-menu-before'"
                                                    >
                                                        <template v-for="(subSubItem, subSubIndex) in subItem.items" :key="subSubIndex">
                                                            <li v-can="subSubItem.permissions">
                                                                <Link v-bind="{ id: subSubItem.id }"  :href="subSubItem.route" @click="toggleMobileMenu">
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
                                                </HeightTransition>
                                            </li>
                                        </template>
                                        <template v-else>
                                            <li v-can="subItem.permissions" class="menu nav-item">
                                                <Link
                                                    v-bind="{ id: subItem.id }"
                                                    :href="subItem.route" class="nav-link group" @click="toggleMobileMenu">
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
                                    <Link :href="item.route" class="nav-link group" @click="toggleMobileMenu">
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
                                <HeightTransition v-show="activeDropdown === 'logros'">
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
                                </HeightTransition>
                            </li>
                        </template>

                    </ul>

                </perfect-scrollbar>

            </div>

        </nav>

    </div>
</template>


