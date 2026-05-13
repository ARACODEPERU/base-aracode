<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import ModalLarge from '@/Components/ModalLarge.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    enabled: {
        type: Boolean,
        default: false
    },
    message: {
        type: String,
        default: ''
    },
    products: {
        type: Array,
        default: () => []
    },
    auto_print: {
        type: Boolean,
        default: false
    },
    clientDefault: {
        type: Object,
        default: () => ({ id: 1, full_name: 'Clientes Varios', number: '99999999' })
    }
});

const search = ref('');
const products = ref(props.products || []);
const cart = ref([]);
const saving = ref(false);
const showCart = ref(false);
const showPrintButton = ref(false);
const documentId = ref(null);

// Toolbar state
const documentType = ref('80'); // '80' = Nota de Venta, '03' = Boleta, '01' = Factura
const printOption = ref('auto');
const selectedClient = ref(props.clientDefault);
const showClientModal = ref(false);

// Client form
const clientForm = ref({
    document_type: '1',
    number: '',
    full_name: '',
    telephone: '',
    email: '',
    address: '',
    id: null
});
const clientSearchLoading = ref(false);
const clientApiesLoading = ref(false);

// Obtener precio del producto
const getProductPrice = (product) => {
    if (product.sale_prices) {
        try {
            const prices = JSON.parse(product.sale_prices);
            return parseFloat(prices.high) || 0;
        } catch (e) {
            return 0;
        }
    }
    return 0;
};

// BÃºsqueda de productos
const searchProducts = async () => {
    if (search.value.length < 2) {
        products.value = props.products;
        return;
    }
    try {
        const response = await axios.post(route('search_product_all'), {
            search: search.value
        });
        products.value = response.data.products || response.data;
    } catch (error) {
        console.error('Error searching products:', error);
    }
};

// Agregar al carrito
const addToCart = (product) => {
    const existing = cart.value.find(item => item.id === product.id);
    if (existing) {
        existing.qty++;
    } else {
        cart.value.push({
            id: product.id,
            description: product.description,
            price: getProductPrice(product),
            interne: product.interne,
            qty: 1
        });
    }
    if (navigator.vibrate) {
        navigator.vibrate(50);
    }
};

// Actualizar cantidad
const updateQty = (item, change) => {
    const index = cart.value.findIndex(i => i.id === item.id);
    if (index >= 0) {
        cart.value[index].qty += change;
        if (cart.value[index].qty <= 0) {
            cart.value.splice(index, 1);
        }
    }
};

// Eliminar del carrito
const removeItem = (item) => {
    const index = cart.value.findIndex(i => i.id === item.id);
    if (index >= 0) {
        cart.value.splice(index, 1);
    }
};

// Total calculado
const total = computed(() => {
    return cart.value.reduce((sum, item) => sum + (item.price * item.qty), 0);
});

// Buscar cliente en RENIEC/SUNAT
const searchClientApies = async () => {
    clientApiesLoading.value = true;
    try {
        const response = await axios.post(route('sales_search_person_apies'), {
            document_type: clientForm.value.document_type,
            number: clientForm.value.number
        });
        if (response.data.success) {
            clientForm.value.full_name = response.data.person.razonSocial || response.data.person.nombres || '';
            clientForm.value.address = response.data.person.direccion || '';
        } else {
            Swal.fire('Error', response.data.error || 'No se encontrÃ³ el cliente', 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Error al consultar API', 'error');
    } finally {
        clientApiesLoading.value = false;
    }
};

// Buscar cliente internamente
const searchClientInternal = async () => {
    clientSearchLoading.value = true;
    try {
        const response = await axios.post(route('search_person_number'), {
            document_type: clientForm.value.document_type,
            number: clientForm.value.number
        });
        if (response.data.status) {
            clientForm.value.id = response.data.person.id;
            clientForm.value.full_name = response.data.person.full_name;
            clientForm.value.telephone = response.data.person.telephone;
            clientForm.value.email = response.data.person.email;
            clientForm.value.address = response.data.person.address;
        } else {
            Swal.fire('Info', 'Cliente no encontrado, puedes registrarlo', 'info');
        }
    } catch (error) {
        Swal.fire('Error', 'Error en la bÃºsqueda', 'error');
    } finally {
        clientSearchLoading.value = false;
    }
};

// Guardar cliente y seleccionar
const saveClient = async () => {
    try {
        const response = await axios.post(route('save_person_update_create'), clientForm.value);
        selectedClient.value = {
            id: response.data.id || clientForm.value.id,
            full_name: response.data.full_name || clientForm.value.full_name,
            number: clientForm.value.number
        };
        showClientModal.value = false;
        Swal.fire({
            icon: 'success',
            title: 'Cliente seleccionado',
            text: selectedClient.value.full_name,
            timer: 1500,
            showConfirmButton: false
        });
    } catch (error) {
        Swal.fire('Error', 'Error al guardar cliente', 'error');
    }
};

// Procesar venta
const checkout = async () => {
    if (cart.value.length === 0) {
        Swal.fire('Error', 'El carrito estÃ¡ vacÃ­o', 'error');
        return;
    }

    saving.value = true;
    try {
        const response = await axios.post(route('sales_quick_sale_store'), {
            items: cart.value.map(item => ({
                id: item.id,
                qty: item.qty,
                price: item.price
            })),
            payment_amount: total.value,
            client_id: selectedClient.value.id,
            document_type_id: documentType.value
        });

        if (response.data.success) {
            cart.value = [];
            documentId.value = response.data.document_id;
            
            if (printOption.value === 'auto') {
                window.open(response.data.ticket_url, '_blank');
            } else if (printOption.value === 'ask') {
                const result = await Swal.fire({
                    icon: 'question',
                    title: 'Â¿Imprimir ticket?',
                    showCancelButton: true,
                    confirmButtonText: 'SÃ­, imprimir',
                    cancelButtonText: 'No'
                });
                if (result.isConfirmed) {
                    window.open(response.data.ticket_url, '_blank');
                }
            }

            Swal.fire({
                icon: 'success',
                title: 'Venta realizada',
                text: 'La venta se registrÃ³ correctamente',
                timer: 2000,
                showConfirmButton: false
            });
        }
    } catch (error) {
        Swal.fire('Error', error.response?.data?.message || 'No se pudo procesar la venta', 'error');
    } finally {
        saving.value = false;
    }
};

// Detectar Enter en bÃºsqueda
const handleSearchKeyup = (event) => {
    if (event.key === 'Enter' && search.value.length > 0) {
        const matches = products.value.filter(p => 
            p.interne?.includes(search.value) || 
            p.description?.toLowerCase().includes(search.value.toLowerCase())
        );
        if (matches.length === 1) {
            addToCart(matches[0]);
            search.value = '';
            products.value = props.products;
        }
    }
};

// Volver a Notas de Venta
const goToNotes = () => {
    router.visit(route('sale_credit_notes_list'));
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-zinc-900 p-4 pb-32">
        <!-- Mensaje de deshabilitado -->
        <div v-if="!enabled" class="text-center py-20">
            <div class="text-6xl mb-4">ðŸš«</div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">TPV no disponible</h2>
            <p class="text-gray-600 dark:text-gray-400">{{ message || 'El punto de venta rÃ¡pido estÃ¡ deshabilitado' }}</p>
        </div>

        <div v-else>
            <!-- Toolbar superior -->
            <div class="bg-white dark:bg-zinc-800 p-3 rounded-xl shadow mb-4">
                <div class="flex flex-wrap gap-3 items-center justify-between">
                    <!-- Tipo de Documento -->
                    <div class="flex items-center gap-2">
                        <select v-model="documentType" class="form-select text-sm">
                            <option value="80">ðŸ“„ Nota de Venta (Ticket)</option>
                            <option value="03">ðŸ“„ Boleta ElectrÃ³nica</option>
                            <option value="01">ðŸ“‹ Factura ElectrÃ³nica</option>
                        </select>
                    </div>
                    
                    <!-- OpciÃ³n de ImpresiÃ³n -->
                    <div class="flex items-center gap-2">
                        <select v-model="printOption" class="form-select text-sm">
                            <option value="auto">ðŸ–¨ï¸ Imprimir al pagar</option>
                            <option value="ask">â“ Preguntar antes</option>
                            <option value="manual">ðŸš« No imprimir</option>
                        </select>
                    </div>
                    
                    <!-- Cliente -->
                    <button @click="showClientModal = true" class="btn btn-primary text-sm">
                        ðŸ‘¤ {{ selectedClient.full_name }}
                    </button>

                    <!-- BotÃ³n Volver a Notas de Venta -->
                    <button @click="goToNotes" class="btn btn-secondary text-sm">
                        â† Volver a Notas de Venta
                    </button>
                </div>
            </div>

            <!-- Barra de bÃºsqueda -->
            <div class="mb-4">
                <input 
                    v-model="search"
                    @input="searchProducts"
                    @keyup="handleSearchKeyup"
                    type="text" 
                    class="w-full p-4 text-lg form-input rounded-xl"
                    placeholder="ðŸ” Buscar producto por cÃ³digo o nombre..."
                    autofocus
                >
            </div>

            <!-- Grid de productos (2 columnas para mÃ³vil) -->
            <div class="grid grid-cols-2 gap-3 mb-6">
                <div 
                    v-for="product in products" 
                    @click="addToCart(product)"
                    class="p-4 bg-white dark:bg-zinc-800 rounded-xl shadow hover:shadow-lg cursor-pointer transition transform hover:scale-105 min-h-[100px] flex flex-col items-center justify-center active:scale-95"
                >
                    <div class="text-center">
                        <div class="text-xl font-bold text-primary mb-2">S/ {{ getProductPrice(product) }}</div>
                        <div class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2">{{ product.description }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ product.interne }}</div>
                    </div>
                </div>

                <div v-if="products.length === 0" class="col-span-2 text-center py-12 text-gray-400">
                    No se encontraron productos
                </div>
            </div>

            <!-- Carrito fijo abajo (mÃ³vil) -->
            <div class="fixed bottom-0 left-0 right-0 bg-white dark:bg-zinc-800 shadow-lg p-4 border-t border-gray-200 dark:border-zinc-700">
                <div class="flex justify-between items-center mb-3">
                    <div>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">Total: S/ {{ total.toFixed(2) }}</span>
                    </div>
                    <button 
                        @click="showCart = !showCart"
                        class="text-sm text-primary hover:text-primary/80"
                    >
                        {{ cart.length }} item(s)
                    </button>
                </div>

                <button 
                    @click="checkout"
                    :disabled="saving || cart.length === 0"
                    :class="{ 'opacity-50 cursor-not-allowed': saving || cart.length === 0 }"
                    class="w-full py-4 bg-green-600 text-white text-xl font-bold rounded-xl hover:bg-green-700 transition active:bg-green-800 flex items-center justify-center gap-2"
                >
                    <span v-if="saving" class="animate-spin">â³</span>
                    <span v-else>ðŸ’° PAGAR AHORA</span>
                </button>
            </div>

            <!-- Modal del carrito (slide-up en mÃ³vil) -->
            <div 
                v-if="showCart" 
                class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-end"
                @click.self="showCart = false"
            >
                <div class="bg-white dark:bg-zinc-800 w-full max-h-[70vh] rounded-t-2xl overflow-y-auto p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold">Carrito de Compras</h3>
                        <button @click="showCart = false" class="text-gray-500 hover:text-gray-700">
                            âœ•
                        </button>
                    </div>

                    <div v-if="cart.length === 0" class="text-center py-8 text-gray-400">
                        El carrito estÃ¡ vacÃ­o
                    </div>

                    <div v-else class="space-y-3">
                        <div 
                            v-for="item in cart" 
                            class="flex justify-between items-center p-3 bg-gray-50 dark:bg-zinc-700 rounded-lg"
                        >
                            <div class="flex-1">
                                <div class="font-medium text-gray-900 dark:text-white">{{ item.description }}</div>
                                <div class="text-sm text-gray-500">S/ {{ item.price }} c/u</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <button 
                                    @click="updateQty(item, -1)"
                                    class="w-8 h-8 rounded-full bg-gray-200 dark:bg-zinc-600 flex items-center justify-center font-bold hover:bg-gray-300"
                                >
                                    -
                                </button>
                                <span class="w-8 text-center font-medium">{{ item.qty }}</span>
                                <button 
                                    @click="updateQty(item, 1)"
                                    class="w-8 h-8 rounded-full bg-gray-200 dark:bg-zinc-600 flex items-center justify-center font-bold hover:bg-gray-300"
                                >
                                    +
                                </button>
                                <button 
                                    @click="removeItem(item)"
                                    class="ml-2 text-red-500 hover:text-red-700"
                                >
                                    âœ•
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="cart.length > 0" class="mt-4 pt-4 border-t border-gray-200 dark:border-zinc-700">
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total:</span>
                            <span>S/ {{ total.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal de bÃºsqueda de cliente -->
            <ModalLarge :show="showClientModal" :onClose="() => showClientModal = false" :icon="'/img/comunidad.png'">
                <template #title>
                    Seleccionar Cliente
                </template>
                <template #message>
                    Buscar en sistema, RENIEC o SUNAT
                </template>
                <template #content>
                    <div class="grid grid-cols-6 gap-4">
                        <div class="col-span-6 sm:col-span-2">
                            <InputLabel value="Tipo de Documento" />
                            <select v-model="clientForm.document_type" class="form-select">
                                <option value="1">DNI</option>
                                <option value="6">RUC</option>
                            </select>
                        </div>
                        <div class="col-span-6 sm:col-span-2">
                            <InputLabel value="NÃºmero" />
                            <TextInput v-model="clientForm.number" type="text" class="form-input" />
                        </div>
                        <div class="col-span-6 sm:col-span-2 flex items-end gap-2">
                            <button @click="searchClientApies()" :disabled="clientApiesLoading" type="button" class="btn btn-primary text-xs">
                                <span v-if="clientApiesLoading" class="animate-spin">â³</span>
                                <span v-else>{{ clientForm.document_type == 6 ? 'SUNAT' : 'RENIEC' }}</span>
                            </button>
                            <button @click="searchClientInternal()" :disabled="clientSearchLoading" type="button" class="btn btn-danger text-xs">
                                <span v-if="clientSearchLoading" class="animate-spin">â³</span>
                                <span v-else>Buscar</span>
                            </button>
                        </div>
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel :value="clientForm.document_type == 6 ? 'RazÃ³n Social' : 'Nombres'" />
                            <TextInput v-model="clientForm.full_name" type="text" class="form-input" />
                        </div>
                        <div class="col-span-6 sm:col-span-2">
                            <InputLabel value="TelÃ©fono" />
                            <TextInput v-model="clientForm.telephone" type="text" class="form-input" />
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel value="Email" />
                            <TextInput v-model="clientForm.email" type="email" class="form-input" />
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel value="DirecciÃ³n" />
                            <TextInput v-model="clientForm.address" type="text" class="form-input" />
                        </div>
                    </div>
                </template>
                <template #buttons>
                    <PrimaryButton @click="saveClient()">
                        Seleccionar Cliente
                    </PrimaryButton>
                </template>
            </ModalLarge>
        </div>
    </div>
</template>


