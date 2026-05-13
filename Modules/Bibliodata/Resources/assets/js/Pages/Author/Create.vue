<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Swal2 from 'sweetalert2';
import axios from 'axios';
import AuthorForm from './Partials/Form.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    identityDocumentTypes: { type: Array, default: () => [] },
    countries: { type: Array, default: () => [] },
});

const formRef = ref(null);

const openSearchModal = () => {
    const docTypes = props.identityDocumentTypes;
    let optionsHtml = docTypes.map(dt => `<option value="${dt.id}">${dt.description}</option>`).join('');

    Swal2.fire({
        title: 'Verificar Persona',
        text: 'Ingrese el número de documento para verificar si la persona ya está registrada.',
        html: `
            <div class="text-left space-y-3">
                <label class="block text-sm font-medium">Tipo de Documento</label>
                <select id="swal-doc-type" class="form-select w-full">${optionsHtml}</select>
                <label class="block text-sm font-medium">Número</label>
                <input id="swal-number" type="text" class="form-input w-full" placeholder="N° de documento" />
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Buscar',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        icon: 'question',
        padding: '2em',
        customClass: 'sweet-alerts',
        preConfirm: () => {
            const data = {
                document_type: document.getElementById('swal-doc-type').value,
                number: document.getElementById('swal-number').value,
            };
            return axios.post(route('search_person_number'), data).then((res) => {
                if (!res.data.status) {
                    const form = formRef.value?.form;
                    if (form) {
                        form.document_type_id = data.document_type;
                        form.number = data.number;
                    }
                    Swal2.showValidationMessage(res.data.alert || 'Persona no encontrada')
                }
                return res;
            });
        },
        allowOutsideClick: () => !Swal2.isLoading(),
    }).then((result) => {
        if (result.isConfirmed && result.value?.data?.person) {
            const person = result.value.data.person;
            Swal2.fire({
                title: person.full_name,
                imageUrl: person.image || undefined,
                text: `Ya fue registrado con el documento ${person.number}`,
                imageHeight: 180,
                imageWidth: 180,
                padding: '2em',
                customClass: { image: 'rounded-full', ...'sweet-alerts' },
                confirmButtonText: 'OK',
            }).then((res) => {
                if (res.isConfirmed && formRef.value) {
                    formRef.value.setPersonData(person);
                }
            });
        }
    });
};

onMounted(() => {
    openSearchModal();
});
</script>

<template>
    <AppLayout title="Nuevo Autor">
        <Navigation :routeModule="route('bib_dashboard')" :titleModule="'Biblio Data'"
            :data="[
                {title: 'Autores', route: route('bib_authors')},
                {title: 'Nuevo'}
            ]"
        />
        <div class="pt-5">
            <AuthorForm ref="formRef" :identity-document-types="identityDocumentTypes" :countries="countries" />
        </div>
    </AppLayout>
</template>
