<script setup>
    import { ref, onMounted, computed, watch } from 'vue';
    import apexchart from 'vue3-apexcharts';
    import { useAppStore } from '@/stores/index';
    const store = useAppStore();

    const  displayChartBar = ref(true);
    const dataBarLoaded = ref(false);
    const dataCourses = ref([]);

    const getDataStudentsCourses = () => {
        displayChartBar.value = true;
        axios.get(route('aca_student_registration_courses')).then((response) => {
            return response.data;
        }).then((result) => {
            let men = [];
            let women = [];
            let courseName = [];
            let total = 0;

            result.courses.forEach(item => {
                men.push(item.men);
                women.push(parseFloat(item.women));
                total = total + (parseFloat(item.men) + parseFloat(item.women));
                courseName.push(item.curso)
            });

            dataCourses.value.men = men;
            dataCourses.value.women = women;
            dataCourses.value.total = total;
            dataCourses.value.courseName = courseName;
            displayChartBar.value = false;

            return total;
        }).then(() => {
            dataBarLoaded.value = true;
        }).catch((error) => {
            console.error('Error al obtener los datos:', error);
            displayChartBar.value = false;
        });
    }

    const series = computed(() => [
        {
            name: 'Hombre',
            data: dataCourses.value.men
        },
        {
            name: 'Mujeres',
            data: dataCourses.value.women
        }
    ]);

    const chartOptions = computed(() => {
        const isDark = store.theme === 'dark' || store.isDarkMode ? true : false;
        const isRtl = store.rtlClass === 'rtl' ? true : false;
        return {
            chart: {
                type: 'bar',
                height: 350,
                stacked: true,
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                },
            },
            xaxis: {
                categories: dataCourses.value.courseName,
                axisBorder: {
                    color: isDark ? '#191e3a' : '#e0e6ed',
                },
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val
                    }
                },
                theme: isDark ? 'dark' : 'light',
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left',
                offsetX: 40
            },
            colors: ['#d4526e','#f48024'],
            grid: {
                borderColor: isDark ? '#191e3a' : '#e0e6ed',
            },
        }
    });

    onMounted(() => {
        getDataStudentsCourses();
    });
</script>
<template>
    <div class="panel">
        <template v-if="displayChartBar">
            <div class="flex items-center justify-between">
                <div>
                    <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-600 w-24 mb-2.5"></div>
                    <div class="w-32 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
                </div>
                <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-700 w-12"></div>
            </div>
        </template>
        <template v-else>
            <div class="flex items-center justify-between mb-5">
                <h5 class="font-semibold text-lg dark:text-white-light uppercase">Matriculados Activos por curso</h5>
                <span class="font-semibold hover:text-gray-400 dark:text-gray-400 dark:hover:text-gray-600" > TOTAL: {{ dataCourses.total }}</span>
            </div>
            <div>
                <apexchart v-if="dataBarLoaded" type="bar" height="380" :options="chartOptions" :series="series"></apexchart>
            </div>
        </template>
    </div>
</template>
