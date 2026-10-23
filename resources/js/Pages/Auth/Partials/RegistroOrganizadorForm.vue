<script setup>
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

/*
 * Formulario único del alta de organización.
 *
 * Todas las variantes de /register montan este componente: el estado, la
 * validación y el envío viven aquí una sola vez. Cada pantalla solo aporta su
 * cabecera, su tarjeta y sus colores.
 */
const form = useForm({
    organization_name: '',
    organization_type: 'empresa',
    ruc: '',
    first_name: '',
    apellido_paterno: '',
    apellido_materno: '',
    telefono: '',
    tipo_identidad: 'DNI',
    numero_identidad: '',
    sexo: '',
    direccion: '',
    email: '',
    password: '',
    password_confirmation: '',
    acepto: false,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <form class="space-y-8" @submit.prevent="submit" novalidate>
        <!-- Organización -->
        <fieldset class="grid gap-4 md:grid-cols-2">
            <legend class="mb-2 text-lg font-bold">Organización</legend>

            <div class="md:col-span-2">
                <label for="organization_name">Nombre de la organización *</label>
                <input id="organization_name" v-model="form.organization_name" type="text" class="form-input" required />
                <InputError :message="form.errors.organization_name" />
            </div>

            <div>
                <label for="organization_type">Tipo *</label>
                <select id="organization_type" v-model="form.organization_type" class="form-select" required>
                    <option value="empresa">Empresa</option>
                    <option value="persona_natural">Persona natural</option>
                </select>
                <InputError :message="form.errors.organization_type" />
            </div>

            <div>
                <label for="ruc">RUC (opcional)</label>
                <input id="ruc" v-model="form.ruc" type="text" inputmode="numeric" maxlength="11" class="form-input" placeholder="Puedes agregarlo después" />
                <InputError :message="form.errors.ruc" />
            </div>
        </fieldset>

        <!-- Usuario responsable -->
        <fieldset class="grid gap-4 md:grid-cols-2">
            <legend class="mb-2 text-lg font-bold">Usuario responsable</legend>

            <div>
                <label for="first_name">Nombre del responsable *</label>
                <input id="first_name" v-model="form.first_name" type="text" class="form-input" required />
                <InputError :message="form.errors.first_name" />
            </div>

            <div>
                <label for="apellido_paterno">Apellido paterno *</label>
                <input id="apellido_paterno" v-model="form.apellido_paterno" type="text" class="form-input" required />
                <InputError :message="form.errors.apellido_paterno" />
            </div>

            <div>
                <label for="apellido_materno">Apellido materno *</label>
                <input id="apellido_materno" v-model="form.apellido_materno" type="text" class="form-input" required />
                <InputError :message="form.errors.apellido_materno" />
            </div>

            <div>
                <label for="telefono">Teléfono *</label>
                <input id="telefono" v-model="form.telefono" type="tel" class="form-input" required />
                <InputError :message="form.errors.telefono" />
            </div>

            <div>
                <label for="tipo_identidad">Tipo de número de identidad *</label>
                <select id="tipo_identidad" v-model="form.tipo_identidad" class="form-select" required>
                    <option value="DNI">DNI</option>
                    <option value="CE">Carné de extranjería</option>
                    <option value="PASAPORTE">Pasaporte</option>
                </select>
                <InputError :message="form.errors.tipo_identidad" />
            </div>

            <div>
                <label for="numero_identidad">Número de identidad *</label>
                <input id="numero_identidad" v-model="form.numero_identidad" type="text" class="form-input" required />
                <InputError :message="form.errors.numero_identidad" />
            </div>

            <div>
                <label for="sexo">Sexo</label>
                <select id="sexo" v-model="form.sexo" class="form-select">
                    <option value="">Seleccionar</option>
                    <option value="M">Masculino</option>
                    <option value="F">Femenino</option>
                </select>
                <InputError :message="form.errors.sexo" />
            </div>

            <div class="md:col-span-2">
                <label for="direccion">Dirección (opcional)</label>
                <input id="direccion" v-model="form.direccion" type="text" class="form-input" />
                <InputError :message="form.errors.direccion" />
            </div>
        </fieldset>

        <!-- Acceso -->
        <fieldset class="grid gap-4 md:grid-cols-2">
            <legend class="mb-2 text-lg font-bold">Acceso</legend>

            <div>
                <label for="email">Correo electrónico *</label>
                <input id="email" v-model="form.email" type="email" class="form-input" required autocomplete="username" />
                <InputError :message="form.errors.email" />
            </div>

            <div></div>

            <div>
                <label for="password">Contraseña *</label>
                <input id="password" v-model="form.password" type="password" class="form-input" required autocomplete="new-password" />
                <InputError :message="form.errors.password" />
            </div>

            <div>
                <label for="password_confirmation">Confirmar contraseña *</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="form-input" required autocomplete="new-password" />
                <InputError :message="form.errors.password_confirmation" />
            </div>
        </fieldset>

        <!-- Aceptación obligatoria: el servidor rechaza el alta sin ella. -->
        <div>
            <label class="flex cursor-pointer items-start gap-2">
                <input v-model="form.acepto" type="checkbox" class="form-checkbox mt-1" />
                <span class="text-sm text-gray-600 dark:text-gray-300">
                    Acepto los
                    <a :href="route('terminos_condiciones')" target="_blank" rel="noopener" class="font-semibold underline">Términos y Condiciones</a>
                    y la
                    <a :href="route('politica_privacidad')" target="_blank" rel="noopener" class="font-semibold underline">Política de Privacidad</a>.
                </span>
            </label>
            <InputError :message="form.errors.acepto" />
        </div>

        <button type="submit" class="btn btn-primary w-full" :disabled="form.processing || ! form.acepto">Crear cuenta y organización</button>
    </form>
</template>
