<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/Vristo/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';

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
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout>
        <Head title="Crea tu cuenta" />

        <div class="mx-auto w-full max-w-3xl p-6">
            <div class="mb-6 text-center">
                <h1 class="text-3xl font-extrabold text-primary">Empieza a organizar tu torneo</h1>
                <p class="text-gray-500">Registra tu organización y tu usuario responsable. Tendrás prueba gratuita para crear tus torneos.</p>
            </div>

            <form class="space-y-8 rounded-xl bg-white p-6 shadow dark:bg-black" @submit.prevent="submit" novalidate>
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

                <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">Crear cuenta y organización</button>
            </form>

            <p class="mt-6 text-center">
                ¿Ya tienes una cuenta?
                <Link :href="route('login')" class="font-semibold text-primary underline">Inicia sesión</Link>
            </p>
        </div>
    </AuthLayout>
</template>
