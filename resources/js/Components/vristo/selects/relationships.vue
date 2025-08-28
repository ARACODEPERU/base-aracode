<script setup>
    import { ref, watch } from 'vue';
    import { Cascader } from 'ant-design-vue';

    const relationships = ref([
        {
            value: 1,
            label: 'Parentescos Directos (Línea de Sangre)',
            children: [
                {value: 'Padre', label: 'Padre'},
                {value: 'Madre', label: 'Madre'},
                {value: 'Hijo', label: 'Hijo'},
                {value: 'Hija', label: 'Hija'},
                {value: 'Hermano', label: 'Hermano'},
                {value: 'Hermana', label: 'Hermana'},
                {value: 'Medio hermano', label: 'Medio hermano'},
                {value: 'Media hermana', label: 'Media hermana'},
                {value: 'Abuelo', label: 'Abuelo'},
                {value: 'Abuela', label: 'Abuela'},
                {value: 'Nieto', label: 'Nieto'},
                {value: 'Nieta', label: 'Nieta'},
                {value: 'Bisabuelo', label: 'Bisabuelo'},
                {value: 'Bisabuela', label: 'Bisabuela'},
                {value: 'Bisnieto', label: 'Bisnieto'},
                {value: 'Bisnieta', label: 'Bisnieta'},
                {value: 'Tío', label: 'Tío'},
                {value: 'Tía', label: 'Tía'},
                {value: 'Sobrino', label: 'Sobrino'},
                {value: 'Sobrina', label: 'Sobrina'},
                {value: 'Primo', label: 'Primo'},
                {value: 'Prima', label: 'Prima'}
            ]
        },
        {
            value: 2,
            label: 'Parentescos por Matrimonio (Afinidad)',
            children: [
                {value: 'Esposo', label: 'Esposo'},
                {value: 'Esposa', label: 'Esposa'},
                {value: 'Suegro', label: 'Suegro'},
                {value: 'Suegra', label: 'Suegra'},
                {value: 'Yerno', label: 'Yerno'},
                {value: 'Nuera', label: 'Nuera'},
                {value: 'Cuñado', label: 'Cuñado'},
                {value: 'Cuñada', label: 'Cuñada'},
                {value: 'Consuegro', label: 'Consuegro'},
                {value: 'Consuegra', label: 'Consuegra'}
            ]
        },
        {
            value: 3,
            label: 'Parentescos Políticos (No Biológicos)',
            children: [
                {value: 'Padrastro', label: 'Padrastro'},
                {value: 'Madrastra', label: 'Madrastra'},
                {value: 'Hijastro', label: 'Hijastro'},
                {value: 'Hijastra', label: 'Hijastra'},
                {value: 'Hermanastro', label: 'Hermanastro'},
                {value: 'Hermanastra', label: 'Hermanastra'}
            ]
        },
        {
            value: 4,
            label: 'Otros Parentescos y Relaciones',
            children: [
                {value: 'Padrino', label: 'Padrino'},
                {value: 'Madrina', label: 'Madrina'},
                {value: 'Ahijado', label: 'Ahijado'},
                {value: 'Ahijada', label: 'Ahijada'},
                {value: 'Compadre', label: 'Compadre (relación entre el padrino y el padre del ahijado)'},
                {value: 'Comadre', label: 'Comadre (relación entre la madrina y la madre del ahijado)'}
            ]
        }
    ]);

    const selectedValues = ref([]);
    const emit = defineEmits(['update:modelValue']);

    // Observa cambios en el valor y emite el valor final
    watch(selectedValues, (newVal) => {
        // Si la selección tiene un valor, emite el último elemento del array (el parentesco)
        if (newVal && newVal.length > 0) {
            emit('update:modelValue', newVal[newVal.length - 1]);
        } else {
            // En caso de que se borre la selección, emite null
            emit('update:modelValue', null);
        }
    });
</script>
<template>
    <Cascader
        v-model:value="selectedValues"
        placeholder="Por favor seleccione"
        :options="relationships"

    />
</template>
