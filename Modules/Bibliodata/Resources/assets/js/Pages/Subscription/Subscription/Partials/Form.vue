<script setup>
import { computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

const props = defineProps({
    subscription: { type: Object, default: null },
    plans: { type: Array, default: () => [] },
    organizations: { type: Array, default: () => [] },
    lectorUsers: { type: Array, default: () => [] },
});

const form = useForm({
    id: props.subscription?.id || null,
    plan_id: props.subscription?.plan_id || '',
    subscriber_type: props.subscription?.subscriber_type || 'individual',
    user_id: props.subscription?.user_id || '',
    organization_id: props.subscription?.organization_id || '',
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

watch(
    () => form.subscriber_type,
    (type) => {
        if (type === 'individual') {
            form.organization_id = '';
        } else {
            form.user_id = '';
        }
    }
);

const submit = () => {
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
            Asigne un plan a un usuario individual o a una organización. Las fechas de fin se calculan según el plan si se dejan vacías.
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

            <div v-else class="col-span-6 sm:col-span-3">
                <InputLabel value="Organización *" />
                <select v-model="form.organization_id" class="form-select w-full" required>
                    <option value="">Seleccionar organización...</option>
                    <option v-for="org in organizations" :key="org.id" :value="org.id">
                        {{ org.name }}
                    </option>
                </select>
                <InputError :message="form.errors.organization_id" class="mt-1" />
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
</template>
