<script setup>
import VueCollapsible from 'vue-height-collapsible/vue3';
import { ref, reactive } from 'vue';
import iconChecks from '@/Components/vristo/icon/icon-checks.vue';
import iconCaretDown from '@/Components/vristo/icon/icon-caret-down.vue';
import iconInfoTriangle from '@/Components/vristo/icon/icon-info-triangle.vue';
import iconPlus from '@/Components/vristo/icon/icon-plus.vue';
import iconX from '@/Components/vristo/icon/icon-x.vue';
import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
import iconHelpCircle from '@/Components/vristo/icon/icon-help-circle.vue';
import { Popconfirm, message  } from 'ant-design-vue';

// Props
const props = defineProps({
    allergies: {
        type: Object,
        default: () => ({})
    },
    patient: {
        type: Object,
        default: () => ({})
    }
});

const accordians1 = ref(1);
// Controlar los items abiertos
const treeview1 = ref([]);

// Estado para mostrar inputs de nuevas alergias
const newAllergiesInput = reactive({});

// Función para abrir/cerrar el árbol de alergias
const toggleTreeview1 = (name) => {
    if (treeview1.value.includes(name)) {
        treeview1.value = treeview1.value.filter((d) => d !== name);
    } else {
        treeview1.value.push(name);
    }
};

// Función para mostrar input de nueva alergia
const showNewAllergyInput = (allergyId) => {
    newAllergiesInput[allergyId] = {
        active: true,
        text: null,
        loading: false
    };
};

const hideNewAllergyInput = (allergyId) => {
    newAllergiesInput[allergyId] = {
        active: false,
        text: null,
        loading: false
    };
};

// Función para agregar una nueva alergia a allergy_patient
const addNewAllergy = (allergy, description, allergyId) => {
    if (description.text.trim() !== '') {
        newAllergiesInput[allergyId].loading = true;

        axios({
            method: 'post',
            url: route('heal_allergies_store'),
            data: {
                patient: props.patient.id,
                allergy: allergyId,
                description: description.text.trim()
            }
        }).then((response) => {
            allergy.allergy_patient.push({
                id: response.data.allergyId,
                description: description.text.trim()
            });
        }).finally(function () {
            // always executed
            newAllergiesInput[allergyId].loading = false;
        }); 

        
    }
    newAllergiesInput[allergy.id] = {
        active: false,
        text: null,
        loading: false
    }
};

// Reactive state para las alergias
const arrayAllergies = reactive({
    data: props.allergies
});

const loading = ref(false);

const confirmRemoveAllergy = (id,key,index) => {
    loading.value = true; // Activa el estado de loading
    return axios({
        method: 'delete',
        url: route('heal_allergies_destroy', id),
    }).then((response) => {
       // console.log(response);
        message.success('Se eliminó correctamente');
    }).catch((error) => {
        message.error('Hubo un error al eliminar');
    }).finally(() => {
        loading.value = false; // Desactiva el estado de loading
        //console.log(arrayAllergies.data[index].allergy_patient[key])
        arrayAllergies.data[index].allergy_patient.splice(key,1);
    }); 
    
}
</script>

<template>
    <div class="border border-[#d3d3d3] rounded dark:border-[#1b2e4b]">
        <button
            type="button"
            class="p-4 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
            :class="{ '!text-primary': accordians1 === 1 }"
            @click="accordians1 === 1 ? (accordians1 = null) : (accordians1 = 1)"
        >
            <icon-info-triangle class="w-5 h-5 mr-4" />
            Alergias
            <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians1 === 1 }">
                <icon-caret-down class="w-6 h-6" />
            </div>
        </button>
        <vue-collapsible :isOpen="accordians1 === 1">
            <div class="space-y-2 p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                <ul class="font-semibold text-danger">
                    <li v-for="(allergy, index) in arrayAllergies.data" class="py-[5px]" :key="allergy.id">
                        <button class="flex gap-2" type="button" @click="toggleTreeview1(allergy.id)">
                            <span v-html="allergy.icon"></span>
                            {{ allergy.title }}
                        </button>
                        <vue-collapsible :isOpen="treeview1.includes(allergy.id)">
                            <ul class="ltr:pl-10 rtl:pr-10">
                                <template v-if="allergy.allergy_patient.length > 0">
                                    <li v-for="(pat, key) in allergy.allergy_patient" :key="key" class="flex gap-4 items-center py-[5px]">
                                        <Popconfirm 
                                            title="Estas seguro de eliminar" 
                                            ok-text="Si, eliminar" 
                                            cancel-text="Cancelar"
                                            :okButtonProps="{ loading: loading }"
                                            @confirm="confirmRemoveAllergy(pat.id, key, index)"
                                        >
                                            <template #icon>
                                                <icon-help-circle class="w-6 h-6 text-danger" />
                                            </template>
                                            <button type="button">
                                                <icon-x class="w-5 h-5" />
                                            </button>
                                        </Popconfirm>
                                        {{ pat.description }}
                                    </li>
                                </template>
                                <li class="py-[5px]">
                                    <div class="flex items-center gap-4">
                                        <button v-if="newAllergiesInput[allergy.id] && newAllergiesInput[allergy.id].active" type="button"  @click="hideNewAllergyInput(allergy.id)">
                                            <icon-loader v-if="newAllergiesInput[allergy.id].loading" class="animate-[spin_2s_linear_infinite] inline-block align-middle w-5 h-5 shrink-0"  />
                                            <icon-x v-else class="w-5 h-5"  />
                                        </button>
                                        <button v-else type="button" class="flex text-warning gap-4" @click="showNewAllergyInput(allergy.id)">
                                            <icon-plus class="w-5 h-5" /> Agregar
                                        </button>
                                        <!-- Mostrar el input si el botón de plus fue clicado -->
                                        <input v-if="newAllergiesInput[allergy.id] && newAllergiesInput[allergy.id].active" 
                                            type="text" 
                                            v-model="newAllergiesInput[allergy.id].text" 
                                            @keydown.enter="addNewAllergy(allergy, newAllergiesInput[allergy.id], allergy.id)"
                                            placeholder="Nueva alergia"
                                            class="border p-1 rounded"
                                            />
                                    </div>
                                </li>
                            </ul>
                        </vue-collapsible>
                    </li>
                </ul>
            </div>
        </vue-collapsible>
    </div>
</template>
