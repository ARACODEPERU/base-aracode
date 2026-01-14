<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { ref } from "vue";
    import MinutesMatchEditForm from './Partials/MinutesMatchEditForm.vue';


    const props = defineProps({
        accordance: {
            type: Object,
            default: () => ({}),
        },
        edicion: {
            type: Object,
            default: () => ({}),
        },
        resolutionStatus: {
            type: Array,
            default: () => ([]),
        },
        tinyApiKey: {
            type: String,
            default: null,
        },
        ubigeo: {
            type: Object,
            default: () => ({}),
        },
        documentTypes: {
            type: Object,
            default: () => ({}),
        }
    });

    const updateMatchAccordance = () => {
        const data = {
            protest_status: accordance.protest_status,
            protest_description: accordance.protest_description,
            has_protest: accordance.has_protest,
        };

        if (accordance.has_protest) {
            data.resolution_status = accordance.resolution_status;
            data.resolution_description = accordance.resolution_description;
        }

        const form = useForm(data);

        form.post(route('even_ediciones_match_accordance_update', accordance.id), {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    title: 'Éxito',
                    text: 'Actualizado correctamente',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            },
            onError: () => {
                Swal.fire({
                    title: 'Error',
                    text: 'Ocurrió un error al actualizar',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    };
</script>
<template>
    <AppLayout title="Ediciones">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('even_ediciones_listado')" class="text-primary hover:underline">Ediciones</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('even_ediciones_editar', edicion.id)" class="text-primary hover:underline">{{ edicion.name }}</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('even_ediciones_actas_listado', edicion.id)" class="text-primary hover:underline">Libro de Actas y Acuerdos</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Nuevo</span>
            </li>
        </Navigation>
        <div class="mt-6 w-full">
            <MinutesMatchEditForm
                :edicion="edicion"
                :resolutionStatus="resolutionStatus"
                :tinyApiKey="tinyApiKey"
                :ubigeo="ubigeo"
                :documentTypes="documentTypes"
                :accordance="accordance"
                :updateMatchAccordance="updateMatchAccordance"
            />
        </div>
    </AppLayout>
</template>
