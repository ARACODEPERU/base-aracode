<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import iconUpload from '@/Components/vristo/icon/icon-upload.vue';
import { ref } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    landingPathPreview: {
        type: String,
        default: '',
    },
    landingUrl: {
        type: String,
        default: '',
    },
    currentHeroImageUrl: {
        type: String,
        default: '',
    },
    showLandingActions: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['copy-landing-url']);

const landingHeroInput = ref(null);

const openLandingHeroExplorer = () => {
    landingHeroInput.value?.click();
};

const onLandingHeroSelected = (event) => {
    const file = event.target.files?.[0];
    if (!file) {
        return;
    }
    props.form.landing_hero_file = file;
};
</script>

<template>
    <div class="col-span-6">
        <div
            class="space-y-5 rounded-lg border border-gray-200 bg-gray-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-900/30 sm:p-5 lg:sticky lg:top-4"
        >
            <h4 class="text-base font-bold text-gray-900 dark:text-gray-100">
                Publicación web y app móvil
            </h4>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="contact_name" value="Nombre de contacto" class="mb-1" />
                    <TextInput id="contact_name" v-model="form.contact_name" class="w-full" />
                    <InputError :message="form.errors.contact_name" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="contact_phone" value="Teléfono" class="mb-1" />
                    <TextInput id="contact_phone" v-model="form.contact_phone" class="w-full" />
                    <InputError :message="form.errors.contact_phone" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="contact_whatsapp" value="WhatsApp (solo números)" class="mb-1" />
                    <TextInput
                        id="contact_whatsapp"
                        v-model="form.contact_whatsapp"
                        placeholder="51999999999"
                        class="w-full"
                    />
                    <InputError :message="form.errors.contact_whatsapp" class="mt-2" />
                </div>
            </div>

            <div class="space-y-1.5 border-t border-gray-200 pt-4 dark:border-zinc-700">
                <InputLabel for="public_slug" value="URL amigable del torneo" class="mb-1" />
                <div class="flex w-full min-w-0 rounded-md shadow-sm">
                    <span
                        class="inline-flex shrink-0 items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-2.5 text-sm text-gray-600 dark:border-zinc-600 dark:bg-zinc-800 dark:text-gray-300"
                    >
                        /torneos/
                    </span>
                    <TextInput
                        id="public_slug"
                        v-model="form.public_slug"
                        class="min-w-0 flex-1 rounded-l-none"
                        placeholder="copa-apertura-2026"
                    />
                </div>
                <p class="text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                    Solo letras minúsculas, números y guiones. Si lo dejas vacío, se genera desde el nombre y el año.
                </p>
                <p v-if="landingPathPreview" class="break-all text-xs text-primary">
                    {{ landingPathPreview }}
                </p>
                <InputError :message="form.errors.public_slug" class="mt-2" />
            </div>

            <div class="space-y-3">
                <div>
                    <InputLabel for="branding_accent_color" value="Color acento (#hex)" class="mb-1" />
                    <TextInput
                        id="branding_accent_color"
                        v-model="form.branding_accent_color"
                        placeholder="#3b82f6"
                        class="w-full max-w-xs"
                    />
                </div>
                <div
                    class="space-y-2.5 rounded-md border border-gray-200 bg-white p-3 dark:border-zinc-600 dark:bg-zinc-800/50"
                >
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <input type="checkbox" v-model="form.landing_published" class="form-checkbox shrink-0" />
                        <span class="text-sm font-medium">Publicar landing web</span>
                    </label>
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <input type="checkbox" v-model="form.mobile_enabled" class="form-checkbox shrink-0" />
                        <span class="text-sm font-medium">Disponible en app móvil</span>
                    </label>
                </div>
            </div>

            <div class="space-y-3 border-t border-gray-200 pt-4 dark:border-zinc-700">
                <InputLabel value="Imagen hero landing (JPG/PNG)" class="mb-1" />
                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                    <button type="button" @click="openLandingHeroExplorer" class="btn btn-outline-primary btn-sm w-fit">
                        <icon-upload class="mr-1 h-4 w-4" />
                        Subir imagen
                    </button>
                    <input
                        ref="landingHeroInput"
                        type="file"
                        class="hidden"
                        accept="image/*"
                        @change="onLandingHeroSelected"
                    />
                    <a
                        v-if="currentHeroImageUrl"
                        :href="currentHeroImageUrl"
                        target="_blank"
                        class="text-sm text-primary underline"
                    >
                        Ver imagen actual
                    </a>
                </div>
                <InputError :message="form.errors.landing_hero_file" class="mt-2" />
                <div v-if="showLandingActions" class="flex flex-wrap gap-2">
                    <a :href="landingUrl" target="_blank" class="btn btn-outline-secondary btn-sm">Ver landing</a>
                    <button type="button" @click="emit('copy-landing-url')" class="btn btn-outline-secondary btn-sm">
                        Copiar enlace
                    </button>
                </div>
            </div>

            <slot name="footer" />
        </div>
    </div>
</template>
