<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/Vristo/AuthLayout.vue';
import RegistroOrganizadorForm from './Partials/RegistroOrganizadorForm.vue';

/*
 * Variante de KIRAFACT (PW00001 = 4). Misma alta que el resto de portadas;
 * solo cambian la piel y los textos. Está deshabilitada en config/registro.php.
 */
</script>

<template>
    <AuthLayout>
        <Head title="Crea tu cuenta" />

        <div class="min-h-screen bg-[#0B1B3A] px-4 py-10 sm:px-6">
            <div class="mx-auto grid w-full max-w-6xl gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                <section class="text-white">
                    <p class="text-sm font-semibold uppercase tracking-[0.35em] text-[#7CC1FA]">KIRAFACT</p>

                    <h1 class="mt-4 text-4xl font-extrabold leading-tight">
                        Crea la cuenta de tu empresa
                    </h1>
                    <p class="mt-4 max-w-md text-white/70">
                        Facturación, inventario y reportes en una sola plataforma. Registra tu empresa y
                        empieza a ordenar la operación de tu negocio.
                    </p>

                    <ul class="mt-8 space-y-3 text-sm text-white/80">
                        <li class="flex items-start gap-2">
                            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-[#168CF0]"></span>
                            Comprobantes y ventas con SUNAT.
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-[#168CF0]"></span>
                            Inventario y almacenes bajo control.
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-[#168CF0]"></span>
                            Reportes claros para decidir mejor.
                        </li>
                    </ul>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-2xl sm:p-8">
                    <h2 class="text-2xl font-bold text-[#0B1B3A]">Registro de la organización</h2>
                    <p class="mb-6 text-sm text-gray-500">
                        Estos datos crean la empresa y el usuario responsable de la cuenta.
                    </p>

                    <RegistroOrganizadorForm />

                    <p class="mt-6 text-center text-sm">
                        ¿Ya tienes una cuenta?
                        <Link :href="route('login')" class="font-semibold text-[#0E6FC4] underline">Inicia sesión</Link>
                    </p>
                </section>
            </div>
        </div>
    </AuthLayout>
</template>
