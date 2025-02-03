<script setup>
    import iconDentadura from '@/Components/vristo/icon/icon-dentadura.vue';
    import iconCaretDown from '@/Components/vristo/icon/icon-caret-down.vue';
    import iconHorizontalDots from '@/Components/vristo/icon/icon-horizontal-dots.vue';
    import { Drawer, Row, Col } from 'ant-design-vue';
    import diente16 from './Partials/diente16.vue';
    import diente17 from './Partials/diente17.vue';
    import diente18 from './Partials/diente18.vue';
    import diente26 from './Partials/diente26.vue';
    import diente27 from './Partials/diente27.vue';
    import diente28 from './Partials/diente28.vue';
    import diente15 from './Partials/diente15.vue';
    import diente14 from './Partials/diente14.vue';
    import diente54 from './Partials/diente54.vue';
    import diente64 from './Partials/diente64.vue';
    import diente65 from './Partials/diente65.vue';
    import diente55 from './Partials/diente55.vue';
    import diente13 from './Partials/diente13.vue';
    import diente48 from './Partials/diente48.vue';
    import diente45 from './Partials/diente45.vue';
    import diente41 from './Partials/diente41.vue';
    import dienteX from './Partials/dienteX.vue';
    import { ref } from 'vue';
    import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue';

    const open = ref(false);

    const childrenDrawer = ref(false);

    const showDrawer = (event) => {
        event.preventDefault();
        open.value = true;
    };
    const onClose = () => {
        open.value = false;
    };
    const showChildrenDrawer = () => {
        childrenDrawer.value = true;
    };

    const sizeDiente = ref(14);

    const activeKey = ref(['1']);
    const text = `A dog is a type of domesticated animal.`;
</script>
<template>
    <a v-on:click="showDrawer" href="#" class="group flex flex-col justify-center hover:bg-gray-50 focus:outline-none focus:bg-gray-50 rounded-xl p-4 md:p-7 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
        <div class="flex justify-center items-center size-12 bg-blue-600 rounded-xl">
            <icon-dentadura class="shrink-0 size-6 text-white" />
        </div>
        <div class="mt-5">
            <h3 class="group-hover:text-gray-600 text-lg font-semibold text-gray-800 dark:text-white dark:group-hover:text-gray-400">Odontograma</h3>
            <p class="mt-1 text-gray-600 dark:text-neutral-400">Registro de condiciones y tratamientos en cada diente</p>
            <span class="mt-2 inline-flex items-center gap-x-1.5 text-sm text-blue-600 decoration-2 group-hover:underline group-focus:underline font-medium">
                Ingresar
                <icon-caret-down class="shrink-0 size-4 rtl:rotate-90 -rotate-90" />
            </span>
        </div>
    </a>
    <Drawer
        v-model:open="open"
        width="100%"
        :closable="false"
        :footer-style="{ textAlign: 'right' }"
        @close="onClose"
        class="dark:bg-gray-900"
    >
        <template #title>
            <div class="flex items-center gap-4 dark:text-white">
                <icon-dentadura class="shrink-0 size-6 text-white" />
                Odontograma - Inicial
            </div>
        </template>

        <div>
            <Row>
                <Col :span="6">
                    <TabGroup as="div" class="mb-5">
                        <TabList class="flex flex-wrap mt-3 border-b border-white-light dark:border-[#191e3a]">
                            <Tab as="template" v-slot="{ selected }">
                                <a
                                    href="javascript:;"
                                    class=" dark:text-gray-500 p-3.5 py-2 -mb-[1px] block border border-transparent hover:text-primary dark:hover:border-b-black !outline-none transition duration-300"
                                    :class="{ '!border-white-light !border-b-white dark:text-white text-primary dark:!border-[#191e3a] dark:!border-b-black': selected }"
                                    >A-I</a
                                >
                            </Tab>
                            <Tab as="template" v-slot="{ selected }">
                                <a
                                    href="javascript:;"
                                    class="dark:text-gray-500 p-3.5 py-2 -mb-[1px] block border border-transparent hover:text-primary dark:hover:border-b-black !outline-none transition duration-300"
                                    :class="{ '!border-white-light !border-b-white dark:text-white text-primary dark:!border-[#191e3a] dark:!border-b-black': selected }"
                                    >L-P</a
                                >
                            </Tab>
                            <Tab as="template" v-slot="{ selected }">
                                <a
                                    href="javascript:;"
                                    class="dark:text-gray-500 p-3.5 py-2 -mb-[1px] block border border-transparent hover:text-primary dark:hover:border-b-black !outline-none transition duration-300"
                                    :class="{ '!border-white-light !border-b-white dark:text-white text-primary dark:!border-[#191e3a] dark:!border-b-black': selected }"
                                    >R-T</a
                                >
                            </Tab>
                        </TabList>
                        <TabPanels class="pt-5 flex-1 text-sm">
                            <TabPanel> 
                               
                                <div
                                    class="bg-white dark:bg-[#1b2e4b] rounded-xl shadow-[0_0_4px_2px_rgb(31_45_61_/_10%)] p-3 flex items-center justify-between text-gray-500 font-semibold hover:text-primary transition-all duration-300 hover:scale-[1.01]"
                                >
                                    <div class="flex items-center gap-4">
                                        <dienteX class="w-8 h-8 rounded-md object-cover" />
                                    
                                        <div>Fractura</div>
                                    </div>
                                    <div class="cursor-pointer">
                                        <div class="dropdown">
                                            <Popper :placement="'bottom-end'" offsetDistance="0" class="align-middle">
                                                <button type="button" class="dropdown-toggle">
                                                    <icon-caret-down class="w-6 h-6 opacity-70" />
                                                </button>
                                                <template #content="{ close }">
                                                    <ul @click="close()" class="whitespace-nowrap">
                                                        <li><a href="javascript:;">Action</a></li>
                                                        <li><a href="javascript:;">Another action</a></li>
                                                        <li><a href="javascript:;">Something else here</a></li>
                                                        <li><a href="javascript:;">Separated link</a></li>
                                                    </ul>
                                                </template>
                                            </Popper>
                                        </div>
                                    </div>
                                </div>
                             
                            </TabPanel>
                            <TabPanel>Disabled</TabPanel>
                            <TabPanel>Disabled ddd</TabPanel>
                        </TabPanels>
                    </TabGroup>
                </Col>
                <Col :span="18">
                    <div class="p-4">
                        <table class="w-full" style="border: none;">
                            <tbody>
                                <tr style="border: none;">
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                </tr>
                                <tr style="border: none;">
                                    <td class="text-center dark:text-white">18</td>
                                    <td class="text-center dark:text-white">17</td>
                                    <td class="text-center dark:text-white">16</td>
                                    <td class="text-center dark:text-white">15</td>
                                    <td class="text-center dark:text-white">14</td>
                                    <td class="text-center dark:text-white">13</td>
                                    <td class="text-center dark:text-white">12</td>
                                    <td class="text-center border-r border-gray-800 dark:text-white dark:border-gray-50">11</td>
                                    <td class="text-center dark:text-white">21</td>
                                    <td class="text-center dark:text-white">22</td>
                                    <td class="text-center dark:text-white">23</td>
                                    <td class="text-center dark:text-white">24</td>
                                    <td class="text-center dark:text-white">25</td>
                                    <td class="text-center dark:text-white">26</td>
                                    <td class="text-center dark:text-white">27</td>
                                    <td class="text-center dark:text-white">28</td>
                                </tr>
                                <tr style="border: none;">
                                    <td class="p-0">
                                        <diente18 
                                            :rectangulo="true"
                                            class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" 
                                        />
                                    </td>
                                    <td class="p-0">
                                        <diente17 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente16 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente15 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente14 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente13 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente13 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="border-r border-gray-800 p-0 dark:border-gray-50">
                                        <diente13 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 border-none">
                                        <diente13 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente13 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente13 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente15 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente14 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente26 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente27 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente28 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                </tr>
                                <!-- espacio en blanco -->
                                <tr style="border: none;">
                                    <td class="border-r border-gray-800 h-10 dark:border-gray-50" colspan="8"></td>
                                    <td class="h-10" colspan="8"></td>
                                </tr>
                                <!-- segunda fila de dientes -->
                                <tr style="border: none;">
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr style="border: none;">
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="p-0 pt-4">
                                        <diente55 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 pt-4">
                                        <diente54 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 pt-4">
                                        <diente13 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 pt-4">
                                        <diente13 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="border-r border-gray-800 p-0 pt-4 dark:border-gray-50">
                                        <diente13 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 pt-4">
                                        <diente13 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 pt-4">
                                        <diente13 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 pt-4">
                                        <diente13 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 pt-4">
                                        <diente64 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 pt-4">
                                        <diente65 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr style="border: none;">
                                    <td class="border-b border-gray-800 p-0 dark:border-gray-50"></td>
                                    <td class="border-b border-gray-800 p-0 dark:border-gray-50"></td>
                                    <td class="border-b border-gray-800 p-0 dark:border-gray-50"></td>
                                    <td class="border-b border-gray-800 p-0 dark:border-gray-50 text-center dark:text-white">55</td>
                                    <td class="border-b border-gray-800 p-0 dark:border-gray-50 text-center dark:text-white">54</td>
                                    <td class="border-b border-gray-800 p-0 dark:border-gray-50 text-center dark:text-white">53</td>
                                    <td class="border-b border-gray-800 p-0 dark:border-gray-50 text-center dark:text-white">52</td>
                                    <td class="border-b text-center border-r border-gray-800 dark:text-white dark:border-gray-50">51</td>
                                    <td class="border-b border-gray-800 p-0 dark:border-gray-50 text-center dark:text-white">61</td>
                                    <td class="border-b border-gray-800 p-0 dark:border-gray-50 text-center dark:text-white">62</td>
                                    <td class="border-b border-gray-800 p-0 dark:border-gray-50 text-center dark:text-white">63</td>
                                    <td class="border-b border-gray-800 p-0 dark:border-gray-50 text-center dark:text-white">64</td>
                                    <td class="border-b border-gray-800 p-0 dark:border-gray-50 text-center dark:text-white">65</td>
                                    <td class="border-b border-gray-800 p-0 dark:border-gray-50"></td>
                                    <td class="border-b border-gray-800 p-0 dark:border-gray-50"></td>
                                    <td class="border-b border-gray-800 p-0 dark:border-gray-50"></td>
                                </tr>
                                <!-- segundo grupo de dientes -->
                                <tr style="border: none;">
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="dark:border-gray-50 text-center dark:text-white">85</td>
                                    <td class="dark:border-gray-50 text-center dark:text-white">84</td>
                                    <td class="dark:border-gray-50 text-center dark:text-white">83</td>
                                    <td class="dark:border-gray-50 text-center dark:text-white">82</td>
                                    <td class="text-center border-r border-gray-800 dark:text-white dark:border-gray-50">81</td>
                                    <td class="dark:border-gray-50 text-center dark:text-white">71</td>
                                    <td class="dark:border-gray-50 text-center dark:text-white">72</td>
                                    <td class="dark:border-gray-50 text-center dark:text-white">73</td>
                                    <td class="dark:border-gray-50 text-center dark:text-white">74</td>
                                    <td class="dark:border-gray-50 text-center dark:text-white">75</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr style="border: none;">
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="p-0 pb-4">
                                        <diente48 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 pb-4">
                                        <diente48 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 pb-4">
                                        <diente41 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 pb-4">
                                        <diente41 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="border-r border-gray-800 p-0 pb-4 dark:border-gray-50">
                                        <diente41 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 pb-4">
                                        <diente41 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 pb-4">
                                        <diente41 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 pb-4">
                                        <diente41 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 pb-4">
                                        <diente48 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 pb-4">
                                        <diente48 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr style="border: none;">
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <!-- espacio en blanco -->
                                <tr style="border: none;">
                                    <td class="border-r border-gray-800 h-10 dark:border-gray-50" colspan="8"></td>
                                    <td class="h-10" colspan="8"></td>
                                </tr>
                                <tr style="border: none;">
                                    <td class="p-0">
                                        <diente48 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente48 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente48 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente45 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente45 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente41 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente41 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="border-r border-gray-800 p-0 dark:border-gray-50">
                                        <diente41 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0 border-none">
                                        <diente41 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente41 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente41 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente45 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente45 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente48 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente48 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                    <td class="p-0">
                                        <diente48 class="dark:text-white" :class="`w-${sizeDiente} h-${sizeDiente}`" />
                                    </td>
                                </tr>
                                <tr style="border: none;">
                                    <td class="text-center dark:text-white">48</td>
                                    <td class="text-center dark:text-white">47</td>
                                    <td class="text-center dark:text-white">46</td>
                                    <td class="text-center dark:text-white">45</td>
                                    <td class="text-center dark:text-white">44</td>
                                    <td class="text-center dark:text-white">43</td>
                                    <td class="text-center dark:text-white">42</td>
                                    <td class="text-center border-r border-gray-800 dark:text-white dark:border-gray-50">41</td>
                                    <td class="text-center dark:text-white">31</td>
                                    <td class="text-center dark:text-white">32</td>
                                    <td class="text-center dark:text-white">33</td>
                                    <td class="text-center dark:text-white">34</td>
                                    <td class="text-center dark:text-white">35</td>
                                    <td class="text-center dark:text-white">36</td>
                                    <td class="text-center dark:text-white">37</td>
                                    <td class="text-center dark:text-white">38</td>
                                </tr>
                                <tr style="border: none;">
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                    <td class="border border-gray-800 h-6 dark:border-gray-50"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Col>
            </Row>
            
        </div>

        <Drawer v-model:open="childrenDrawer" title="Two-level Drawer" width="320" :closable="false">
            <button type="primary" @click="showChildrenDrawer">This is two-level drawer</button>
        </Drawer>

        <template #footer>
            <div class="flex justify-end">
                <button @click="onClose" type="button" class="btn btn-danger">Cerrar</button>
            </div>
        </template>
    </Drawer>
</template>
<style scoped>
    .ant-space {
        width: 100%;
    }
</style>