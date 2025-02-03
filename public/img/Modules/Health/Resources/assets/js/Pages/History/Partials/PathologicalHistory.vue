
<script setup>
import VueCollapsible from 'vue-height-collapsible/vue3';
import { ref, reactive } from 'vue';
import iconHeartCircleExclamation from '@/Components/vristo/icon/icon-heart-circle-exclamation.vue';
import iconCaretDown from '@/Components/vristo/icon/icon-caret-down.vue';
import iconInfoTriangle from '@/Components/vristo/icon/icon-info-triangle.vue';
import iconPlus from '@/Components/vristo/icon/icon-plus.vue';
import iconX from '@/Components/vristo/icon/icon-x.vue';
import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
import iconHelpCircle from '@/Components/vristo/icon/icon-help-circle.vue';
import { Popconfirm, message  } from 'ant-design-vue';

const accordians1 = ref(0);

</script>
<template>
    <div class="border border-[#d3d3d3] rounded dark:border-[#1b2e4b]">
        <button
            type="button"
            class="p-4 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
            :class="{ '!text-primary': accordians1 === 1 }"
            @click="accordians1 === 1 ? (accordians1 = null) : (accordians1 = 1)"
        >
            <icon-heart-circle-exclamation class="w-5 h-5 mr-4" />
            Antecedentes Patologicos
            <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians1 === 1 }">
                <icon-caret-down class="w-6 h-6" />
            </div>
        </button>
        <vue-collapsible :isOpen="accordians1 === 1">
            <div class="space-y-2 p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
             
            </div>
        </vue-collapsible>
    </div>
</template> 