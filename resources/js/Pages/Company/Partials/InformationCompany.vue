<script setup>
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from "@headlessui/vue";
import { useAppStore } from "@/stores/index";
import IconHome from "@/Components/vristo/icon/icon-home.vue";
import IconDollarSignCircle from "@/Components/vristo/icon/icon-dollar-sign-circle.vue";
import IconAt from "@/Components/vristo/icon/icon-at.vue";
import DangerButton from "@/Components/DangerButton.vue";
import IconLinkedin from "@/Components/vristo/icon/icon-linkedin.vue";
import IconTwitter from "@/Components/vristo/icon/icon-twitter.vue";
import IconFacebook from "@/Components/vristo/icon/icon-facebook.vue";
import IconGithub from "@/Components/vristo/icon/icon-github.vue";
import IconImages from "@/Components/vristo/icon/icon-images.vue";
import { useForm } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ModalSmall from "@/Components/ModalSmall.vue";
import TextInput from "@/Components/TextInput.vue";
import Swal from "sweetalert2";
import iconCaretDown from "@/Components/vristo/icon/icon-caret-down.vue";
import { ref, onMounted,computed } from "vue";
import ImageCompressorjs from '@/Components/ImageCompressorjs.vue';
import cardAccount from '@/Components/cards/cardAccount.vue';
import { Cascader } from 'ant-design-vue';

const store = useAppStore();
const props = defineProps({
    company: {
        type: Object,
        default: () => ({}),
    },
    ubigeo: {
        type: Object,
        default: () => ({}),
    },
    banks: {
        type: Object,
        default: () => ({}),
    },
    bankAccounts: {
        type: Object,
        default: () => ({}),
    },
    currencyTypes: {
        type: Object,
        default: () => ({}),
    }
});

const baseUrl = assetUrl;

const getBasePath = (path) => {
    return baseUrl + "storage/" + path;
};

const form = useForm({
    id: props.company.id,
    ruc: props.company.ruc,
    name: props.company.name,
    business_name: props.company.business_name,
    tradename: props.company.tradename,
    fiscal_address: props.company.fiscal_address,
    phone: props.company.phone,
    representative: props.company.representative,
    email: props.company.email,
    key_sunat: props.company.key_sunat,
    user_sunat: props.company.user_sunat,
    certificate_sunat: props.company.certificate_sunat,
    logo: null,
    logo_old: (props.company.logo == '/img/logo176x32.png' ? props.company.logo : getBasePath(props.company.logo)),
    logo_document_old: (props.company.logo_document == '/img/logo176x32.png' ? props.company.logo_document : getBasePath(props.company.logo_document)),
    logo_document: null,
    ubigeo: props.company.ubigeo,
    ubigeo_description: null,
    logo_negative: null,
    logo_negative_old: (props.company.logo_negative == '/img/logo176x32_negativo.png' ? props.company.logo_negative : getBasePath(props.company.logo_negative)),
    logo_dark: null,
    logo_dark_old: (props.company.logo_dark == '/img/logo176x32Dark.jpeg' ? props.company.logo_dark : getBasePath(props.company.logo_dark)),
    isotipo: null,
    isotipo_old: (props.company.isotipo == '/img/isotipo.png' ? props.company.isotipo : getBasePath(props.company.isotipo)),
    isotipo_negative: null,
    isotipo_negative_old: (props.company.isotipo_negative == '/img/isotipo_negativo.png' ? props.company.isotipo_negative : getBasePath(props.company.isotipo_negative)),
    isotipo_dark: null,
    isotipo_dark_old:(props.company.isotipo_dark == '/img/isotipo_azul.jpeg' ? props.company.isotipo_dark : getBasePath(props.company.isotipo_dark)),
    social_networks: props.company.social_networks ? JSON.parse(props.company.social_networks) : null,
    mode: props.company.mode,
    withdrawal_account_number: props.company.withdrawal_account_number
});

const createOrUpdateCompany = () => {
    form.post(route("company_update_create"), {
        errorBag: "createOrUpdateCompany",
        preserveScroll: true,
        onSuccess: () => {
            showMessage("Datos registrados con éxito");
        },
    });
};

const showMessage = (msg = '', type = 'success') => {
    const toast = Swal.mixin({
        toast: true,
        position: 'top',
        showConfirmButton: false,
        timer: 3000,
        customClass: { container: 'toast' },
    });
    toast.fire({
        icon: type,
        title: msg,
        padding: '10px 20px',
    });
};

const logoCompressed = (file) => {
    form.logo = file;
    form.logo_old = file;
};
const logoDocumentCompressed = (file) => {
    form.logo_document = file;
    form.logo_document_old = file;
};
const logoNegativeCompressed = (file) => {
    form.logo_negative = file;
    form.logo_negative_old = file;
};
const logoDarkCompressed = (file) => {
    form.logo_dark = file;
    form.logo_dark_old = file;
};
const isoTipoCompressed = (file) => {
    form.isotipo = file;
    form.isotipo_old = file;
};
const isoTipoNegativeCompressed = (file) => {
    form.isotipo_negative = file;
    form.isotipo_negative_old = file;
};
const isoTipoDarkCompressed = (file) => {
    form.isotipo_dark = file;
    form.isotipo_dark_old = file;
};

const options = ref([]);
const ubigeoSelected = ref([]);

onMounted(() => {
    options.value = props.ubigeo.map((dep) => ({
        value: dep.id,
        label: dep.name,
        children: dep.provinces.map(prov => ({
            value: prov.id,
            label: prov.name,
            children: prov.districts.map(dist => ({
                value: dist.id,
                label: dist.name
            }))
        }))
     }));
     if (form.ubigeo) {
        let dep = props.company.district.province.department.name;
        let pro = props.company.district.province.name;
        let dis = props.company.district.name;
        ubigeoSelected.value = [dep,pro,dis];
    }

    if(!form.social_networks){
        form.social_networks = [
            {id: 'facebook', route: null},
            {id: 'youtube', route: null},
            {id: 'linkedin', route: null},
            {id: 'x-twiter', route: null},
            {id: 'tiktok', route: null},
            {id: 'instagram', route: null},
        ]
    }
});

const filter = (inputValue, path) => {
    return path.some(option => option.label.toLowerCase().indexOf(inputValue.toLowerCase()) > -1);
};

const changeSelected = (e, label) => {
    form.ubigeo = label[2].value
};

const createFormCertificate= () => {

    let formHTML = document.createElement('form');
    formHTML.classList.add('max-w-sm', 'mx-auto');

    let passwordLabel = document.createElement('label');
    passwordLabel.setAttribute('for', 'txtPass');
    passwordLabel.classList.add('text-left','text-sm','mt-4');
    passwordLabel.textContent = 'Contraseña certificado';

    let passwordInput = document.createElement('input');
    passwordInput.type = 'text';
    passwordInput.id = 'txtPass';
    passwordInput.classList.add(
        'form-input'
    );

    let certificateLabel = document.createElement('label');
    certificateLabel.setAttribute('for', 'fileCer');
    certificateLabel.classList.add('text-left','text-sm','mt-4');
    certificateLabel.textContent = 'Certificado pfx o p12';

    let certificateInput = document.createElement('input');
    certificateInput.type = 'file';
    certificateInput.id = 'fileCer';
    certificateInput.classList.add(
        'form-input'
    );

    passwordInput.required = true;
    passwordInput.autocomplete = 'new-password';
    certificateInput.required = true;

    formHTML.appendChild(passwordLabel);
    formHTML.appendChild(passwordInput);
    formHTML.appendChild(certificateLabel);
    formHTML.appendChild(certificateInput);

    return formHTML;

}

const openSwal2Certificate = () => {
    Swal.fire({
        title: "Generar Certificado PEM",
        html: createFormCertificate(),
        showCancelButton: true,
        confirmButtonText: 'Aceptar',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        allowEscapeKey: false,
        padding: '2em',
        customClass: 'sweet-alerts',
        preConfirm: async (login) => {
            let formData = new FormData();
            formData.append('password', document.getElementById("txtPass").value);
            formData.append('certificate', document.getElementById("fileCer").files[0]);

            return axios.post(route('company_convert_upload_certificate'), formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }).then((res) => {
                if (!res.data.certificate.success) {
                    Swal.showValidationMessage(res.data.certificate.error);
                }
                return res
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            form.certificate_sunat = result.data.certificate.file_name;
            showMessage('El archivo .pem se genero correctamente');
        }
    });
}

const saveSunatCredentials = () => {
    form.post(route('company_save_sunat_credentials'), {
        errorBag: "saveSunatCredentials",
        preserveScroll: true,
        onSuccess: () => {
            showMessage("Datos registrados con éxito");
        },
    });
}


const saveSocialNetworks = () => {
    form.post(route('company_save_social_networks'), {
        errorBag: "saveSocialNetworks",
        preserveScroll: true,
        onSuccess: () => {
            showMessage("Datos registrados con éxito");
        },
    });
}

    const uploadImages = () => {
        form.post(route('company_upload_images'), {
            forceFormData: true,
            errorBag: "uploadImages",
            preserveScroll: true,
            onSuccess: () => {
                showMessage("Datos registrados con éxito");
            },
        });
    };

    const selectBank = ref({
        id: props.banks[0].id,
        image: props.banks[0].image,
        name: props.banks[0].full_name
    });

    const changeBank = (item) => {
        selectBank.value.id = item.id;
        selectBank.value.image = item.image;
        selectBank.value.name = item.full_name;
    }

    const displayModalBankAccount = ref(false);
    const formAccount = useForm({
        id: null,
        bank_id: null,
        description: null,
        number: null,
        cci: null,
        currency_type_id: 'PEN',
        status: true,
        invoice_show: false
    });

    const openModalBankAccount = (item = null) => {
        if(item && item.id){
            formAccount.id = item.id;
            formAccount.bank_id = item.bank_id;
            formAccount.description = item.description;
            formAccount.number = item.number;
            formAccount.cci = item.cci;
            formAccount.currency_type_id = item.currency_type_id;
            formAccount.status = item.status == 1 ? true : false;
            formAccount.invoice_show = item.invoice_show == 1 ? true : false;
        }
        displayModalBankAccount.value = true;
    }

    const closeModalBankAccount = () => {
        displayModalBankAccount.value = false;
    }

    const saveBankAccount = () =>{
        formAccount.post(route('bank-account-store'), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                displayModalBankAccount.value = false;
                formAccount.reset();
            },
            onFinish: () => {
                showMessage("Datos registrados con éxito",'success');
            },
        });
    }


</script>
<template>
    <div class="flex items-center justify-between mb-5">
        <h5 class="font-semibold text-lg dark:text-white-light">Ajustes</h5>
    </div>
    <TabGroup>
        <TabList
            class="grid grid-cols-4 gap-2 sm:flex sm:flex-wrap sm:justify-center mt-3 mb-5 sm:space-x-3 rtl:space-x-reverse"
        >
            <Tab as="template" v-slot="{ selected }">
                <a
                    href="javascript:;"
                    class="p-7 py-3 flex flex-col items-center justify-center rounded-lg bg-[#f1f2f3] dark:bg-[#191e3a] hover:!bg-success hover:text-white hover:shadow-[0_5px_15px_0_rgba(0,0,0,0.30)] !outline-none transition duration-300"
                    :class="{ '!bg-success text-white': selected }"
                >
                    <icon-home class="w-5 h-5 mb-1" />
                    Información general
                </a>
            </Tab>
            <Tab as="template" v-slot="{ selected }">
                <a
                    href="javascript:;"
                    class="p-7 py-3 flex flex-col items-center justify-center rounded-lg bg-[#f1f2f3] dark:bg-[#191e3a] hover:!bg-success hover:text-white hover:shadow-[0_5px_15px_0_rgba(0,0,0,0.30)] !outline-none transition duration-300"
                    :class="{ '!bg-success text-white': selected }"
                >
                    <icon-images class="w-5 h-5 mb-1" />
                    Logotipos e imágenes
                </a>
            </Tab>
            <Tab as="template" v-slot="{ selected }">
                <a
                    href="javascript:;"
                    class="p-7 py-3 flex flex-col items-center justify-center rounded-lg bg-[#f1f2f3] dark:bg-[#191e3a] hover:!bg-success hover:text-white hover:shadow-[0_5px_15px_0_rgba(0,0,0,0.30)] !outline-none transition duration-300"
                    :class="{ '!bg-success text-white': selected }"
                >
                    <icon-dollar-sign-circle class="w-5 h-5 mb-1" />
                    Facturación electrónica
                </a>
            </Tab>
            <Tab as="template" v-slot="{ selected }">
                <a
                    href="javascript:;"
                    class="p-7 py-3 flex flex-col items-center justify-center rounded-lg bg-[#f1f2f3] dark:bg-[#191e3a] hover:!bg-success hover:text-white hover:shadow-[0_5px_15px_0_rgba(0,0,0,0.30)] !outline-none transition duration-300"
                    :class="{ '!bg-success text-white': selected }"
                >
                    <icon-at class="w-5 h-5 mb-1" />
                    Redes sociales
                </a>
            </Tab>
            <Tab as="template" v-slot="{ selected }">
                <a
                    href="javascript:;"
                    class="p-7 py-3 flex flex-col items-center justify-center rounded-lg bg-[#f1f2f3] dark:bg-[#191e3a] hover:!bg-success hover:text-white hover:shadow-[0_5px_15px_0_rgba(0,0,0,0.30)] !outline-none transition duration-300"
                    :class="{ '!bg-success text-white': selected }"
                >
                    <svg class="w-5 h-5 mb-1"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                        <path fill="currentColor" d="M243.4 2.6l-224 96c-14 6-21.8 21-18.7 35.8S16.8 160 32 160l0 8c0 13.3 10.7 24 24 24l400 0c13.3 0 24-10.7 24-24l0-8c15.2 0 28.3-10.7 31.3-25.6s-4.8-29.9-18.7-35.8l-224-96c-8-3.4-17.2-3.4-25.2 0zM128 224l-64 0 0 196.3c-.6 .3-1.2 .7-1.8 1.1l-48 32c-11.7 7.8-17 22.4-12.9 35.9S17.9 512 32 512l448 0c14.1 0 26.5-9.2 30.6-22.7s-1.1-28.1-12.9-35.9l-48-32c-.6-.4-1.2-.7-1.8-1.1L448 224l-64 0 0 192-40 0 0-192-64 0 0 192-48 0 0-192-64 0 0 192-40 0 0-192zM256 64a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/>
                    </svg>
                    Bancos y Cuentas Bancarias
                </a>
            </Tab>
        </TabList>
        <TabPanels>
            <TabPanel>
                <div>
                    <div class="border border-[#ebedf2] dark:border-[#191e3a] rounded-md p-4 mb-5 bg-white dark:bg-[#0e1726]">
                        <h6 class="text-lg font-bold mb-5">
                            Información general
                        </h6>
                        <div class="flex flex-col sm:flex-row">
                            <div  class="ltr:sm:mr-4 rtl:sm:ml-4 w-full sm:w-2/12 mb-5" >
                                <img :src="form.isotipo_old"
                                    alt=""
                                    class="w-20 h-20 md:w-32 md:h-32 rounded-full object-cover mx-auto"
                                />
                            </div>
                            <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-5" >
                                <div>
                                    <label for="name">Nombre</label>
                                    <TextInput
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                    />
                                    <InputError :message="form.errors.name" class="mt-2" />
                                </div>
                                <div>
                                    <label for="ruc">RUC</label>
                                    <TextInput
                                        id="ruc"
                                        v-model="form.ruc"
                                        type="text"
                                        class="block w-full mt-1"
                                        autofocus
                                    />
                                    <InputError :message="form.errors.ruc" class="mt-2" />
                                </div>
                                <div>
                                    <label for="business_name"
                                        >Nombre del Negocio</label
                                    >
                                    <TextInput
                                        id="business_name"
                                        v-model="form.business_name"
                                        type="text"
                                        class="block w-full mt-1"
                                    />
                                    <InputError :message="form.errors.business_name" class="mt-2" />
                                </div>

                                <div>
                                    <label for="tradename" >Nombre comercial*</label>
                                    <TextInput
                                        id="tradename"
                                        v-model="form.tradename"
                                        type="text"
                                        class="block w-full mt-1"
                                    />
                                    <InputError :message="form.errors.tradename" class="mt-2" />
                                </div>
                                <div>
                                    <label for="withdrawal_account_number" >N° Cuenta de detracción*</label>
                                    <TextInput
                                        id="withdrawal_account_number"
                                        v-model="form.withdrawal_account_number"
                                        type="text"
                                        class="block w-full mt-1"
                                    />
                                    <InputError :message="form.errors.withdrawal_account_number" class="mt-2" />
                                </div>
                                <div>
                                    <label for="fiscal_address" >Ciudad*</label>
                                    <Cascader
                                        style="width: 100%"
                                        :options="options"
                                        :show-search="{ filter }"
                                        placeholder="Please select"
                                        v-model="form.ubigeo"
                                        v-model:value="ubigeoSelected"
                                        @change="changeSelected"
                                    >
                                    </Cascader>
                                    <InputError :message="form.errors.fiscal_address" class="mt-2" />
                                </div>
                                <div>
                                    <label for="fiscal_address" >Dirección fiscal*</label>
                                    <TextInput
                                        id="fiscal_address"
                                        v-model="form.fiscal_address"
                                        type="text"
                                        class="block w-full mt-1"
                                    />
                                    <InputError :message="form.errors.fiscal_address" class="mt-2" />
                                </div>
                                <div>
                                    <label for="email">Email</label>
                                    <TextInput
                                        id="email"
                                        v-model="form.email"
                                        type="text"
                                        class="block w-full mt-1"
                                    />
                                    <InputError :message="form.errors.email" class="mt-2" />
                                </div>
                                <div>
                                    <label for="representative" >Representante</label>
                                    <TextInput
                                        id="representative"
                                        v-model="form.representative"
                                        type="text"
                                        class="block w-full mt-1"
                                    />
                                    <InputError :message="form.errors.representative" class="mt-2" />
                                </div>
                                <div>
                                    <label for="phone">Teléfono</label>
                                    <TextInput
                                        id="phone"
                                        v-model="form.phone"
                                        type="text"
                                        class="block w-full mt-1"
                                    />
                                    <InputError :message="form.errors.phone" class="mt-2" />
                                </div>
                                <div>
                                    <label for="phone">Servidor</label>
                                    <div class="flex items-center space-x-5">
                                        <label class="inline-flex">
                                            <input v-model="form.mode" :value="'demo'" type="radio" name="square_radio" class="form-radio text-success rounded-none" />
                                            <span>Demo</span>
                                        </label>
                                        <label class="inline-flex">
                                            <input v-model="form.mode" :value="'prod'" type="radio" name="square_radio" class="form-radio rounded-none" />
                                            <span>Producción</span>
                                        </label>
                                    </div>
                                    <InputError :message="form.errors.modo" class="mt-2" />
                                </div>
                                <div class="sm:col-span-2 mt-3">
                                    <button @click="createOrUpdateCompany" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" type="button" class="btn btn-primary" >
                                        <svg v-show="form.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                        </svg>
                                        guardar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </TabPanel>
            <TabPanel>
                <div class="p-0">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4" >
                        <div class="space-y-5 rounded-md border border-white-light bg-white p-5 shadow-[0px_0px_2px_0px_rgba(145,158,171,0.20),0px_12px_24px_-4px_rgba(145,158,171,0.12)] dark:border-[#1B2E4B] dark:bg-black">
                            <div class="max-h-56 overflow-hidden rounded-md">
                                <img :src="form.logo_old"
                                    alt="..."
                                    class="w-full object-cover"
                                />
                            </div>
                            <h5 class="text-xl dark:text-white">Logo en sistema</h5>
                            <span class="text-success m-0">Se recomienda las medidas 176x32</span>
                            <div class="flex">
                                <ImageCompressorjs :onImageCompressed="logoCompressed" />
                            </div>
                        </div>
                        <div class="space-y-5 rounded-md border border-white-light bg-white p-5 shadow-[0px_0px_2px_0px_rgba(145,158,171,0.20),0px_12px_24px_-4px_rgba(145,158,171,0.12)] dark:border-[#1B2E4B] dark:bg-black">
                            <div class="max-h-56 overflow-hidden rounded-md">
                                <img :src="form.logo_document_old"
                                    alt="..."
                                    class="w-full object-cover"
                                />
                            </div>
                            <h5 class="text-xl dark:text-white">
                                Logo en Documentos
                            </h5>
                            <span class="text-success m-0">Se recomienda las medidas 176x49</span>
                            <div class="flex">
                                <ImageCompressorjs :onImageCompressed="logoDocumentCompressed" />
                            </div>
                        </div>
                        <div class="space-y-5 rounded-md border border-white-light bg-gray-600 p-5 shadow-[0px_0px_2px_0px_rgba(145,158,171,0.20),0px_12px_24px_-4px_rgba(145,158,171,0.12)] dark:border-[#1B2E4B] dark:bg-black">
                            <div class="max-h-56 overflow-hidden rounded-md">
                                <img :src="form.logo_negative_old"
                                    alt="..."
                                    class="w-full object-cover"
                                />
                            </div>
                            <h5 class="text-xl dark:text-white">
                                Logo Negativo
                            </h5>
                            <span class="text-success m-0">Se recomienda las medidas 176x32</span>
                            <div class="flex">
                                <ImageCompressorjs :onImageCompressed="logoNegativeCompressed" />
                            </div>
                        </div>
                        <div class="space-y-5 rounded-md border border-white-light bg-white p-5 shadow-[0px_0px_2px_0px_rgba(145,158,171,0.20),0px_12px_24px_-4px_rgba(145,158,171,0.12)] dark:border-[#1B2E4B] dark:bg-black">
                            <div class="max-h-56 overflow-hidden rounded-md">
                                <img :src="form.logo_dark_old"
                                    alt="..."
                                    class="w-full object-cover"
                                />
                            </div>
                            <h5 class="text-xl dark:text-white">
                                Logo con Fondo
                            </h5>
                            <span class="text-success m-0">Se recomienda las medidas 176x32</span>
                            <div class="flex">
                                <ImageCompressorjs :onImageCompressed="logoDarkCompressed" />
                            </div>
                        </div>
                        <div class="space-y-5 rounded-md border border-white-light bg-white p-5 shadow-[0px_0px_2px_0px_rgba(145,158,171,0.20),0px_12px_24px_-4px_rgba(145,158,171,0.12)] dark:border-[#1B2E4B] dark:bg-black">
                            <div class="max-h-56 overflow-hidden rounded-md">
                                <img :src="form.isotipo_old"
                                    alt="..."
                                    class="w-full object-cover"
                                />
                            </div>
                            <h5 class="text-xl dark:text-white">
                                Isotipo
                            </h5>
                            <span class="text-success m-0">Se recomienda las medidas 1375x1376</span>
                            <div class="flex">
                                <ImageCompressorjs :onImageCompressed="isoTipoCompressed" />
                            </div>
                        </div>
                        <div class="space-y-5 rounded-md border border-white-light bg-gray-600 p-5 shadow-[0px_0px_2px_0px_rgba(145,158,171,0.20),0px_12px_24px_-4px_rgba(145,158,171,0.12)] dark:border-[#1B2E4B] dark:bg-black">
                            <div class="max-h-56 overflow-hidden rounded-md">
                                <img :src="form.isotipo_negative_old"
                                    alt="..."
                                    class="w-full object-cover"
                                />
                            </div>
                            <h5 class="text-xl dark:text-white">
                                Isotipo Negativo
                            </h5>
                            <span class="text-success m-0">Se recomienda las medidas 1375x1376</span>
                            <div class="flex">
                                <ImageCompressorjs :onImageCompressed="isoTipoNegativeCompressed" />
                            </div>
                        </div>
                        <div class="space-y-5 rounded-md border border-white-light bg-white p-5 shadow-[0px_0px_2px_0px_rgba(145,158,171,0.20),0px_12px_24px_-4px_rgba(145,158,171,0.12)] dark:border-[#1B2E4B] dark:bg-black">
                            <div class="max-h-56 overflow-hidden rounded-md">
                                <img :src="form.isotipo_dark_old"
                                    alt="..."
                                    class="w-full object-cover"
                                />
                            </div>
                            <h5 class="text-xl dark:text-white">
                                Isotipo con Fondo
                            </h5>
                            <span class="text-success m-0">Se recomienda las medidas 1375x1376</span>
                            <div class="flex">
                                <ImageCompressorjs :onImageCompressed="isoTipoDarkCompressed" />
                            </div>
                        </div>
                    </div>
                    <button @click="uploadImages" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" type="button" class="btn btn-primary mt-5">
                        <svg v-show="form.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                        </svg>
                        Guardar
                    </button>
                </div>
            </TabPanel>
            <TabPanel>
                <div class="switch">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
                        <div class="panel space-y-5">
                            <h5 class="font-semibold text-lg mb-4">
                                Usuario Secundario Sunat
                            </h5>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                                <div>
                                    <label for="user_sunat">SOAP Usuario *</label>
                                    <input v-model="form.user_sunat" id="user_sunat" type="text"  placeholder="Usuario" class="form-input" />
                                    <InputError :message="form.errors.user_sunat" class="mt-2" />
                                </div>
                                <div>
                                    <label for="key_sunat">SOAP Password *</label>
                                    <input v-model="form.key_sunat" id="key_sunat" type="text" class="form-input" />
                                    <InputError :message="form.errors.key_sunat" class="mt-2" />
                                </div>
                            </div>
                            <button @click="saveSunatCredentials" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" type="button" class="btn btn-primary">
                                <svg v-show="form.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                    <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                </svg>
                                Guardar
                            </button>
                        </div>
                        <div class="panel space-y-5">
                            <h5 class="font-semibold text-lg mb-4">
                                Certificado
                            </h5>
                            <template v-if="form.certificate_sunat">
                                <blockquote class="text-black py-3 px-5 bg-white shadow-md rounded-tr-md rounded-br-md border border-white-light border-l-2 !border-l-primary dark:bg-[#060818] dark:border-[#060818]">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2 ">
                                            <div class="w-8 h-8 items-center">
                                                <img src="/img/pem.png" alt="" class="w-8 h-8 object-cover">
                                            </div>
                                            <p class="not-italic text-[#515365] text-sm dark:text-white-light">{{ form.certificate_sunat }}</p>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-outline-danger btn-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                    <polyline points="7 10 12 15 17 10"></polyline>
                                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </blockquote>
                            </template>
                            <template v-else>
                                <p>Seleccionar archivo .pfx o p12 </p>
                            </template>
                            <button @click="openSwal2Certificate" type="button" class="btn btn-primary">
                                Subir
                            </button>
                        </div>
                    </div>
                </div>
            </TabPanel>
            <TabPanel>
                <div class="border border-[#ebedf2] dark:border-[#191e3a] rounded-md p-4 bg-white dark:bg-[#0e1726]">
                    <h6 class="text-lg font-bold mb-5">Social</h6>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                        <div v-for="(network, index) in form.social_networks" class="flex">
                            <div class="bg-[#eee] flex justify-center items-center rounded px-3 font-semibold dark:bg-[#1b2e4b] ltr:mr-2 rtl:ml-2">
                                <img :src="`/img/${network.id}.png`" class="w-5" />
                            </div>
                            <input v-model="network.route" type="text" class="form-input" />
                        </div>
                    </div>
                    <button @click="saveSocialNetworks" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" type="button" class="btn btn-primary">
                        <svg v-show="form.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                        </svg>
                        Guardar
                    </button>
                </div>
            </TabPanel>
            <TabPanel>
                <div class="relative w-full">
                    <div class="relative panel">
                        <div class="">
                            <div class="flex items-center justify-start space-x-4">
                                <div class="dropdown">
                                    <Popper :placement="'bottom-end'" offsetDistance="8">
                                        <button
                                            type="button"
                                            class="flex items-center gap-2.5 rounded-lg border border-white-dark/30 bg-white px-2 py-1.5 text-white-dark hover:border-primary hover:text-primary dark:bg-black min-w-[315px]"
                                        >
                                            <img
                                                :src="selectBank.image"
                                                alt="image"
                                                class="h-5 w-5 rounded-full object-cover"
                                            />
                                            <div class="text-base font-bold uppercase">{{ selectBank.name }}</div>
                                            <span class="shrink-0 ml-auto">
                                                <icon-caret-down />
                                            </span>
                                        </button>
                                        <template #content="{ close }">
                                            <perfect-scrollbar
                                                :options="{
                                                    swipeEasing: true,
                                                    wheelPropagation: false,
                                                    suppressScrollX: true  // ← here
                                                }"
                                                class="max-h-[280px]"
                                            >
                                                <ul class="w-full !px-2 text-dark dark:text-white-dark font-semibold dark:text-white-light/90">
                                                    <template v-for="item in banks" :key="item.id">
                                                        <li>
                                                            <button
                                                                type="button"
                                                                class="w-full hover:text-primary"
                                                                :class="{ 'bg-primary/10 text-primary': selectBank.id === item.id }"
                                                                @click="changeBank(item), close()"
                                                            >
                                                                <img
                                                                    class="w-5 h-5 object-cover rounded-full"
                                                                    :src="item.image"
                                                                    alt=""
                                                                />
                                                                <span class="ltr:ml-3 rtl:mr-3">{{ item.full_name }}</span>
                                                            </button>
                                                        </li>
                                                    </template>
                                                </ul>
                                            </perfect-scrollbar>
                                        </template>
                                    </Popper>
                                </div>
                                <!-- <PrimaryButton>NUEVO BANCO</PrimaryButton> -->
                                <DangerButton @click="openModalBankAccount" type="button" >NUEVA CUENTA</DangerButton>
                            </div>
                            <div class="mt-6 w-full">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div v-for="cuenta in bankAccounts">
                                        <cardAccount :bankAccount="cuenta" @openModalEditAccount="openModalBankAccount" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </TabPanel>
        </TabPanels>
    </TabGroup>
    <ModalSmall :show="displayModalBankAccount" :onClose="closeModalBankAccount" :icon="'/img/banco.png'">
        <template #title>Crear cuenta bancaria</template>
        <template #message>Los campos * son obligatorios</template>
        <template #content>
            <div class="space-y-4">
                <div>
                    <InputLabel for="bank_id" value="Banco" />
                    <select id="bank_id" v-model="formAccount.bank_id" class="form-select">
                        <template v-for="xbank in banks">
                            <option :value="xbank.id">{{ xbank.short_name }}</option>
                        </template>
                    </select>
                </div>
                <div>
                    <InputLabel for="description" value="Descripción" />
                    <TextInput id="description" v-model="formAccount.description" ></TextInput>
                </div>
                <div>
                    <InputLabel for="number" value="Número de cuenta soles" />
                    <TextInput id="number" v-model="formAccount.number" ></TextInput>
                </div>
                <div>
                    <InputLabel for="cci" value="N° de cuenta interbancario (cci)" />
                    <TextInput id="cci" v-model="formAccount.cci" ></TextInput>
                </div>
                <div>
                    <label class="inline-flex">
                        <input v-model="formAccount.invoice_show" type="checkbox" class="form-checkbox text-success rounded-full" />
                        <span>Mostrar cuenta en documentos de venta</span>
                    </label>
                    <label class="inline-flex">
                        <input v-model="formAccount.status" type="checkbox" class="form-checkbox rounded-full" />
                        <span>Activo</span>
                    </label>
                </div>
                <div>
                    <InputLabel for="currency_type_id" value="Tipo moneda" />
                    <select id="currency_type_id" v-model="formAccount.currency_type_id" class="form-select" >
                        <template v-for="row in currencyTypes">
                            <option :value="row.id">{{ row.description }}</option>
                        </template>
                    </select>
                </div>
            </div>
        </template>
        <template #buttons>
            <PrimaryButton
                @click="saveBankAccount"
                type="button"
                :class="{ 'opacity-25': formAccount.processing }" :disabled="formAccount.processing"
            >
                <svg v-show="formAccount.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                    <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                </svg>
                GUARDAR
            </PrimaryButton>
        </template>
    </ModalSmall>
</template>
