<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { Link, router } from '@inertiajs/vue3';
import Keypad from '@/Components/Keypad.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    resmenu: {
        type: Object,
        default: () => ({}),
    },

});
const xhttp =  assetUrl;


const printMenu = () => {

    var ventana = window.open('', '_blank');
    var estilos = document.querySelectorAll('link[rel="stylesheet"]');
    estilos.forEach(function(estilo) {
        ventana.document.head.appendChild(estilo.cloneNode(true));
    });

    // Agrega el contenido del div a la nueva ventana
    var contenidoDiv = document.getElementById('divPrintMenu').outerHTML;
    ventana.document.body.innerHTML = contenidoDiv;

    // Imprime la nueva ventana
    ventana.print();
}

</script>
<template>
    <AppLayout title="Imprimir carta">
        <Navigation
            :routeModule="route('res_dashboard')"
            titleModule="Restaurante"
            :data="[{ title: 'Carta del día', route: route('res_menu_list') }, { title: 'Imprimir' }]"
        />
        <div class="pt-5">
            <div id="divPrintMenu" class="flex flex-col gap-10">
                <!-- ====== Table One Start -->
                <div style="border-color: black;background-color: #FFF;border-radius: 4px;padding: 6px;">
                    <table style="width: 100%;">
                        <tr>
                            <th colspan="3" >
                                <h2>{{ resmenu.name }}</h2>
                                <p style="border-bottom: 2px solid #000;">{{ resmenu.description }}</p>
                            </th>
                        </tr>
                        <tr>
                            <th colspan="3" >
                                
                            </th>
                        </tr>
                        <tr v-for="(item, key) in resmenu.comandas">
                            <td style="width: 70px;height: 70px;">
                                <img style="width: 70px;height: 70px;" :src="xhttp + 'storage/' + item.comanda.image" alt="">
                            </td >
                            <td style="padding: 10px;">
                                <h4>{{  item.comanda.name }}</h4>
                                <p>{{  item.comanda.description }}</p>
                            </td >
                            <td>
                                {{  item.comanda.price }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <Keypad class="mt-6">
                <template #botones>
                    <PrimaryButton @click="printMenu()">
                        Imprimir
                    </PrimaryButton>
                    <Link :href="route('res_menu_list')"  class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                </template>
            </Keypad>
        </div>
        
    </AppLayout>
</template>

  