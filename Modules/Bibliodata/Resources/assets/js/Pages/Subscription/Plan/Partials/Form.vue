<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

const props = defineProps({
    books: { type: Array, default: () => [] },
    plan: { type: Object, default: null },
});

const form = useForm({
    id: props.plan?.id || null,
    name: props.plan?.name || '',
    code: props.plan?.code || '',
    description: props.plan?.description || '',
    scope_type: props.plan?.scope_type || 'single_book',
    max_books: props.plan?.max_books || null,
    duration_type: props.plan?.duration_type || 'monthly',
    duration_value: props.plan?.duration_value ?? 1,
    includes_ai_chatbot: props.plan?.includes_ai_chatbot || false,
    is_active: props.plan?.is_active ?? true,
    sort_order: props.plan?.sort_order ?? 0,
    book_ids: props.plan?.books?.map((b) => b.id) || [],
});

const selectedBookId = computed({
    get: () => form.book_ids[0] ?? '',
    set: (val) => {
        form.book_ids = val ? [Number(val)] : [];
    },
});

const showDurationValue = computed(() => form.duration_type !== 'lifetime');

const submit = () => {
    if (form.id) {
        form.post(route('bib_subscription_plans_update'), { preserveScroll: true });
    } else {
        form.post(route('bib_subscription_plans_store'), { preserveScroll: true });
    }
};
</script>

<template>
    <FormSection @submitted="submit">
        <template #title>
            {{ form.id ? 'Editar plan' : 'Nuevo plan de suscripción' }}
        </template>
        <template #description>
            Define el libro incluido, la duración y las opciones del plan. Los campos marcados son obligatorios.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Nombre del plan *" />
                <TextInput v-model="form.name" class="w-full" required />
                <InputError :message="form.errors.name" class="mt-1" />
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Código (único) *" />
                <TextInput v-model="form.code" class="w-full" required />
                <InputError :message="form.errors.code" class="mt-1" />
            </div>
            <div class="col-span-6">
                <InputLabel value="Descripción" />
                <textarea v-model="form.description" class="form-textarea w-full" rows="3" />
                <InputError :message="form.errors.description" class="mt-1" />
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Libro incluido *" />
                <select v-model="selectedBookId" class="form-select w-full" required>
                    <option value="">Seleccionar libro...</option>
                    <option v-for="book in books" :key="book.id" :value="book.id">
                        {{ book.title }}{{ book.code_name ? ` (${book.code_name})` : '' }}
                    </option>
                </select>
                <InputError :message="form.errors.book_ids || form.errors.book_id" class="mt-1" />
                <p class="mt-1 text-xs text-gray-500">Alcance multi-libro y todos los libros: próximamente.</p>
            </div>
            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Duración" />
                <select v-model="form.duration_type" class="form-select w-full">
                    <option value="monthly">Mensual</option>
                    <option value="yearly">Anual</option>
                    <option value="lifetime">Vitalicia</option>
                </select>
            </div>
            <div v-if="showDurationValue" class="col-span-6 sm:col-span-3">
                <InputLabel value="Cantidad (meses o años)" />
                <TextInput v-model="form.duration_value" type="number" min="1" class="w-full" />
                <InputError :message="form.errors.duration_value" class="mt-1" />
            </div>
            <div v-if="showDurationValue" class="col-span-6 sm:col-span-3">
                <InputLabel value="Orden" />
                <TextInput v-model="form.sort_order" type="number" min="0" class="w-full" />
            </div>
            <div class="col-span-6 flex flex-wrap gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input v-model="form.is_active" type="checkbox" class="form-checkbox" />
                    <span class="text-sm">Plan activo</span>
                </label>
                <label
                    v-tippy="{ content: 'Próximamente', placement: 'bottom' }"
                    class="flex items-center gap-2 cursor-pointer opacity-70"
                >
                    <input v-model="form.includes_ai_chatbot" type="checkbox" class="form-checkbox" disabled />
                    <span class="text-sm">Incluye chatbot IA (próximamente)</span>
                </label>
            </div>
        </template>

        <template #actions>
            <Link :href="route('bib_subscription_plans')" class="btn btn-danger mr-2">Cancelar</Link>
            <PrimaryButton :disabled="form.processing">
                <IconLoader v-if="form.processing" class="w-4 h-4 mr-2 animate-spin inline" />
                {{ form.id ? 'Actualizar plan' : 'Guardar plan' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
