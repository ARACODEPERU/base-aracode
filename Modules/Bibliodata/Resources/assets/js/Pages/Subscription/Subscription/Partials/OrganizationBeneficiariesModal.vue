<script setup>
import { ref, watch, computed } from 'vue';
import ModalSmall from '@/Components/ModalSmall.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import { bibSwal } from '../../../../utils/bibSwal.js';

const props = defineProps({
    show: { type: Boolean, default: false },
    organizationId: { type: [String, Number], default: '' },
    planId: { type: [String, Number], default: '' },
    subscriptionId: { type: [String, Number], default: null },
    initialSelectedIds: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'confirm']);

const loading = ref(false);
const members = ref([]);
const selectedIds = ref([]);

const eligibleMembers = computed(() =>
    members.value.filter((m) => !m.has_individual_subscription)
);

const allEligibleSelected = computed(() => {
    const eligible = eligibleMembers.value;
    if (!eligible.length) {
        return false;
    }
    return eligible.every((m) => selectedIds.value.includes(m.id));
});

const selectAllEligible = () => {
    selectedIds.value = eligibleMembers.value.map((m) => m.id);
};

const clearEligible = () => {
    selectedIds.value = [];
};

const onToggleAll = (event) => {
    if (event.target.checked) {
        selectAllEligible();
    } else {
        clearEligible();
    }
};

const toggleMember = (member) => {
    if (member.has_individual_subscription) {
        return;
    }
    const idx = selectedIds.value.indexOf(member.id);
    if (idx >= 0) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(member.id);
    }
};

const isSelected = (id) => selectedIds.value.includes(id);

const loadMembers = () => {
    if (!props.organizationId || !props.planId) {
        return;
    }
    loading.value = true;
    axios
        .post(route('bib_subscriptions_organization_members'), {
            organization_id: props.organizationId,
            plan_id: props.planId,
            subscription_id: props.subscriptionId || null,
        })
        .then((res) => {
            members.value = res.data.members || [];
            if (props.initialSelectedIds?.length) {
                selectedIds.value = props.initialSelectedIds.filter((id) =>
                    members.value.some(
                        (m) => m.id === id && !m.has_individual_subscription
                    )
                );
            } else {
                selectAllEligible();
            }
            if (!members.value.length) {
                bibSwal({
                    icon: 'info',
                    title: 'Sin trabajadores',
                    text: 'La organización no tiene miembros registrados.',
                });
            }
        })
        .catch(() => {
            bibSwal({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo cargar la lista de trabajadores.',
            });
        })
        .finally(() => {
            loading.value = false;
        });
};

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            loadMembers();
        }
    }
);

const confirm = () => {
    if (!selectedIds.value.length) {
        bibSwal({
            icon: 'warning',
            title: 'Sin beneficiarios',
            text: 'Seleccione al menos un trabajador elegible.',
        });
        return;
    }
    emit('confirm', [...selectedIds.value]);
    emit('close');
};
</script>

<template>
    <ModalSmall :show="show" :on-close="() => emit('close')" :icon="'/img/perfil.png'" >
        <template #title>Beneficiarios</template>
        <template #message>
            Trabajadores de la organización. Desmarque a quienes no deben incluirse en esta suscripción.
        </template>
        <template #content>
            <div v-if="loading" class="flex justify-center py-8">
                <IconLoader class="w-8 h-8 animate-spin text-primary" />
            </div>
            <template v-else>
                <label
                    class="inline-flex pl-4 items-center gap-2 mb-3 cursor-pointer text-sm font-medium select-none"
                    :class="{ 'opacity-50 pointer-events-none': !eligibleMembers.length }"
                >
                    <input
                        type="checkbox"
                        class="form-checkbox"
                        :checked="allEligibleSelected"
                        :disabled="!eligibleMembers.length"
                        @change="onToggleAll"
                    />
                    Todos
                </label>
                <div class="max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg">
                    <table class="table-hover min-w-full text-sm">
                        <thead class="sticky top-0 bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="w-10 text-center"></th>
                                <th class="text-left">Nombre</th>
                                <th class="text-left">DNI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="member in members"
                                :key="member.id"
                                :class="member.has_individual_subscription ? 'opacity-60' : ''"
                            >
                                <td class="text-center">
                                    <input
                                        type="checkbox"
                                        class="form-checkbox"
                                        :checked="isSelected(member.id)"
                                        :disabled="member.has_individual_subscription"
                                        @change="toggleMember(member)"
                                    />
                                </td>
                                <td>
                                    <span class="font-medium">{{ member.name }}</span>
                                    <p
                                        v-if="member.has_individual_subscription"
                                        class="text-xs text-warning mt-0.5"
                                    >
                                        {{ member.individual_subscription_label }}
                                    </p>
                                </td>
                                <td class="text-gray-500">{{ member.document_number || '—' }}</td>
                            </tr>
                            <tr v-if="!members.length">
                                <td colspan="3" class="text-center py-6 text-gray-500">
                                    No hay miembros en esta organización.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </template>
        <template #buttons>
            <PrimaryButton type="button" :disabled="loading" @click="confirm">
                Aceptar ({{ selectedIds.length }})
            </PrimaryButton>
        </template>
    </ModalSmall>
</template>
