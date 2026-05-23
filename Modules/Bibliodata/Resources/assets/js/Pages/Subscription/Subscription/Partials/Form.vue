<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import OrganizationBeneficiariesModal from './OrganizationBeneficiariesModal.vue';
import { bibSwal } from '../../../../utils/bibSwal.js';

const props = defineProps({
    subscription: { type: Object, default: null },
    plans: { type: Array, default: () => [] },
    organizations: { type: Array, default: () => [] },
    lectorUsers: { type: Array, default: () => [] },
});

const showBeneficiariesModal = ref(false);
const totalMembers = ref(0);

const initialBeneficiaryIds = props.subscription?.subscriber_type === 'organization'
    ? (props.subscription?.beneficiaries?.map((b) => b.id) ?? [])
    : [];

const form = useForm({
    id: props.subscription?.id || null,
    plan_id: props.subscription?.plan_id || '',
    subscriber_type: props.subscription?.subscriber_type || 'individual',
    user_id: props.subscription?.user_id || '',
    organization_id: props.subscription?.organization_id || '',
    beneficiary_user_ids: [...initialBeneficiaryIds],
    starts_at: props.subscription?.starts_at?.substring?.(0, 10) || new Date().toISOString().slice(0, 10),
    ends_at: props.subscription?.ends_at?.substring?.(0, 10) || '',
    status: props.subscription?.status || 'active',
    notes: props.subscription?.notes || '',
    recalculate_ends: false,
});

const selectedPlan = computed(() =>
    props.plans.find((p) => Number(p.id) === Number(form.plan_id))
);

const planDurationHint = computed(() => {
    const plan = selectedPlan.value;
    if (!plan) return '';
    if (plan.duration_type === 'lifetime') return 'El plan es vitalicio (sin fecha de fin automática).';
    const unit = plan.duration_type === 'yearly' ? 'año(s)' : 'mes(es)';
    return `Duración del plan: ${plan.duration_value || 1} ${unit}. Deje fin vacío para calcular automáticamente.`;
});

const isOrganization = computed(() => form.subscriber_type === 'organization');

const canOpenBeneficiaries = computed(
    () => isOrganization.value && form.organization_id && form.plan_id
);

const selectedCount = computed(() => form.beneficiary_user_ids.length);

const beneficiariesLabel = computed(() => {
    if (!totalMembers.value && !selectedCount.value) {
        return '';
    }
    return `Suscritos ${selectedCount.value} de ${totalMembers.value}`;
});

const prefetchMemberTotals = () => {
    if (!form.organization_id || !form.plan_id) {
        totalMembers.value = 0;
        return;
    }
    axios
        .post(route('bib_subscriptions_organization_members'), {
            organization_id: form.organization_id,
            plan_id: form.plan_id,
            subscription_id: form.id || null,
        })
        .then((res) => {
            const list = res.data.members || [];
            totalMembers.value = res.data.total ?? list.length;
            if (!form.id && form.beneficiary_user_ids.length === 0) {
                form.beneficiary_user_ids = list
                    .filter((m) => !m.has_individual_subscription)
                    .map((m) => m.id);
            }
        })
        .catch(() => {
            totalMembers.value = 0;
        });
};

watch(
    () => form.subscriber_type,
    (type) => {
        if (type === 'individual') {
            form.organization_id = '';
            form.beneficiary_user_ids = [];
            totalMembers.value = 0;
        } else {
            form.user_id = '';
        }
    }
);

watch([() => form.organization_id, () => form.plan_id], () => {
    if (!isOrganization.value) {
        return;
    }
    if (!form.organization_id || !form.plan_id) {
        form.beneficiary_user_ids = [];
        totalMembers.value = 0;
        return;
    }
    prefetchMemberTotals();
});

if (isOrganization.value && form.organization_id && form.plan_id) {
    prefetchMemberTotals();
}

const openBeneficiariesModal = () => {
    if (!canOpenBeneficiaries.value) {
        bibSwal({
            icon: 'warning',
            title: 'Datos incompletos',
            text: 'Seleccione el plan y la organización primero.',
        });
        return;
    }
    showBeneficiariesModal.value = true;
};

const onBeneficiariesConfirm = (ids) => {
    form.beneficiary_user_ids = ids;
};

const submit = () => {
    if (isOrganization.value && !form.beneficiary_user_ids.length) {
        bibSwal({
            icon: 'warning',
            title: 'Beneficiarios requeridos',
            text: 'Seleccione al menos un trabajador en Ver beneficiarios.',
        });
        return;
    }
    if (form.id) {
        form.post(route('bib_subscriptions_update'), { preserveScroll: true });
    } else {
        form.post(route('bib_subscriptions_store'), { preserveScroll: true });
    }
};
</script>

<template>
    <FormSection @submitted="submit">
        <template #title>
            {{ form.id ? 'Editar suscripción' : 'Nueva suscripción' }}
        </template>
        <template #description>
            Asigne un plan a un usuario individual o a una organización. Para empresas, elija qué trabajadores serán beneficiarios.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Plan *" />
                <select v-model="form.plan_id" class="form-select w-full" required>
                    <option value="">Seleccionar plan...</option>
                    <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                        {{ plan.name }} — {{ plan.books?.[0]?.title || 'Sin libro' }}
                    </option>
                </select>
                <InputError :message="form.errors.plan_id" class="mt-1" />
                <p v-if="planDurationHint" class="mt-1 text-xs text-gray-500">{{ planDurationHint }}</p>
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Tipo de suscriptor" />
                <select v-model="form.subscriber_type" class="form-select w-full">
                    <option value="individual">Individual</option>
                    <option value="organization">Organización (empresa)</option>
                </select>
            </div>

            <div v-if="form.subscriber_type === 'individual'" class="col-span-6 sm:col-span-3">
                <InputLabel value="Usuario (Lector) *" />
                <select v-model="form.user_id" class="form-select w-full" required>
                    <option value="">Seleccionar usuario...</option>
                    <option v-for="user in lectorUsers" :key="user.id" :value="user.id">
                        {{ user.name }} ({{ user.email }})
                    </option>
                </select>
                <InputError :message="form.errors.user_id" class="mt-1" />
            </div>

            <div v-else class="col-span-6">
                <InputLabel value="Organización *" />
                <div class="flex flex-wrap items-end gap-3 mt-1">
                    <div class="flex-1 min-w-[200px]">
                        <select v-model="form.organization_id" class="form-select w-full" required>
                            <option value="">Seleccionar organización...</option>
                            <option v-for="org in organizations" :key="org.id" :value="org.id">
                                {{ org.name }}
                            </option>
                        </select>
                    </div>
                    <button
                        type="button"
                        class="btn btn-info whitespace-nowrap"
                        :disabled="!canOpenBeneficiaries"
                        @click="openBeneficiariesModal"
                    >
                        Ver beneficiarios
                    </button>
                </div>
                <span
                    v-if="beneficiariesLabel"
                    class="mt-4 text-sm font-medium text-primary dark:text-primary"
                >
                    {{ beneficiariesLabel }}
                </span>
                <InputError :message="form.errors.organization_id" class="mt-1" />
                <InputError :message="form.errors.beneficiary_user_ids" class="mt-1" />
            </div>

            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="Inicio *" />
                <TextInput v-model="form.starts_at" type="date" class="w-full" required />
                <InputError :message="form.errors.starts_at" class="mt-1" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="Fin (opcional)" />
                <TextInput v-model="form.ends_at" type="date" class="w-full" />
                <InputError :message="form.errors.ends_at" class="mt-1" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="Estado" />
                <select v-model="form.status" class="form-select w-full">
                    <option value="pending">Pendiente</option>
                    <option value="active">Activa</option>
                    <option value="expired">Expirada</option>
                    <option value="suspended">Suspendida</option>
                    <option value="cancelled">Cancelada</option>
                </select>
            </div>

            <div v-if="form.id" class="col-span-6 flex items-center gap-2">
                <input id="recalc" v-model="form.recalculate_ends" type="checkbox" class="form-checkbox" />
                <label for="recalc" class="text-sm cursor-pointer">Recalcular fecha de fin según el plan</label>
            </div>

            <div class="col-span-6">
                <InputLabel value="Notas" />
                <textarea v-model="form.notes" class="form-textarea w-full" rows="3" />
            </div>
        </template>

        <template #actions>
            <Link :href="route('bib_subscriptions')" class="btn btn-danger mr-2">Cancelar</Link>
            <PrimaryButton :disabled="form.processing">
                <IconLoader v-if="form.processing" class="w-4 h-4 mr-2 animate-spin inline" />
                {{ form.id ? 'Actualizar suscripción' : 'Crear suscripción' }}
            </PrimaryButton>
        </template>
    </FormSection>

    <OrganizationBeneficiariesModal
        :show="showBeneficiariesModal"
        :organization-id="form.organization_id"
        :plan-id="form.plan_id"
        :subscription-id="form.id"
        :initial-selected-ids="form.beneficiary_user_ids"
        @close="showBeneficiariesModal = false"
        @confirm="onBeneficiariesConfirm"
    />
</template>
