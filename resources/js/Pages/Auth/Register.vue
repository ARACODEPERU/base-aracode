<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/Vristo/AuthLayout.vue';
import RegistroOrganizadorForm from './Partials/RegistroOrganizadorForm.vue';

/*
 * Variante corporativa (PW00001 = 1). Hoy el registro está deshabilitado en
 * esta portada (config/registro.php), pero la pantalla queda lista para cuando
 * se habilite: comparte el formulario y el alta con las demás variantes.
 */
</script>

<template>
    <AuthLayout>
        <Head title="Crea tu cuenta" />

        <div class="min-h-screen bg-[#F3F6FA] px-4 py-10 sm:px-6">
            <div class="mx-auto grid w-full max-w-6xl overflow-hidden rounded-2xl bg-white shadow-xl lg:grid-cols-[0.85fr_1.15fr]">
                <section class="hidden bg-primary p-10 text-white lg:block">
                    <p class="text-2xl font-extrabold tracking-tight">ARACODE</p>

                    <h1 class="mt-12 text-3xl font-bold leading-tight">Crea tu cuenta</h1>
                    <p class="mt-4 max-w-sm text-white/80">
                        Registra tu organización y el usuario responsable. Desde ahí podrás administrar tus
                        procesos, tus equipos y tu información en un solo lugar.
                    </p>

                    <ul class="mt-10 space-y-3 text-sm text-white/85">
                        <li class="flex items-start gap-2">
                            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-white"></span>
                            Una cuenta para toda la organización.
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-white"></span>
                            El usuario responsable administra el acceso.
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-white"></span>
                            Configuración guiada desde el primer día.
                        </li>
                    </ul>
                </section>

                <section class="p-6 sm:p-10">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Registro de la organización</h2>
                    <p class="mb-6 text-sm text-gray-500">
                        Estos datos crean la organización y el usuario responsable de la cuenta.
                    </p>

                    <RegistroOrganizadorForm />

                    <p class="mt-6 text-center text-sm">
                        ¿Ya tienes una cuenta?
                        <Link :href="route('login')" class="font-semibold text-primary underline">Inicia sesión</Link>
                    </p>
                </section>
            </div>
        </div>
    </AuthLayout>
</template>
