<script setup>
    import '@Public/themes/personalLanding/assets/css/style.css';
    import { Head, Link, useForm } from '@inertiajs/vue3';
    import Header from './Components/Header.vue';
    import Footer from './Components/Footer.vue';
    import Swal from 'sweetalert2';
    import InputError from '@/Components/InputError.vue';
    const xassetUrl = assetUrl;

    defineProps({
        dataBook: {
            type: Object,
            default: () => ({})
        }
    });

    const bookForm = useForm({
        names: null,
        tipoIdentificacion: 1,
        dni: null,
        email: null,
        telefono: null,
        tipoBien: 'service',
        descripcion_bien: null,
        chxMonto: false,
        moneda: 'PEN',
        monto: null,
        tipoReclamo: null,
        reclamo: null,
        pedido: null,
        acepto: false
    });

    const saveBook = () => {
        bookForm.post(route('complaints_book_store'), {
            preserveScroll: true,
            onSuccess: () => {
                bookForm.reset();
                Swal.fire({
                    icon: 'success',
                    title: '¡Tu Reclamo ha Sido Enviado!',
                    text: '¡Gracias por contactarnos! Hemos recibido su reclamo. Le hemos enviado el número de folio a su correo electrónico para que pueda darle seguimiento. Nuestro equipo de Aracode Perú lo revisará y le daremos una respuesta en un plazo no mayor a quince (15) días hábiles, tal como lo establece la ley. Apreciamos su paciencia y nos comprometemos a trabajar en una solución.',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            },
        });
    }

</script>

<template>
    <Head>
        <title>Aracode</title>
        <!-- Meta para Facturación Electrónica -->
        <meta name="description" content="Simplifica tu facturación electrónica con nuestra solución. Envía facturas y boletas electrónicas a SUNAT de forma rápida, segura y eficiente.">
        <meta name="keywords" content="facturación electrónica, enviar facturas SUNAT, boletas electrónicas, software SUNAT, sistema de facturación Perú, boletas Perú, SUNAT online">

        <!-- Meta para Desarrollo Web a Medida -->
        <meta name="description" content="Desarrollamos sitios web a medida y aplicaciones personalizadas según las necesidades de tu negocio. Soluciones modernas y adaptadas a tus objetivos.">
        <meta name="keywords" content="desarrollo web a medida, diseño web profesional, creación de sitios web, aplicaciones personalizadas, desarrollo de software, soluciones digitales, páginas web empresariales, desarrollo web Perú">

        <meta name="author" content="Aracode">
        <meta name="robots" content="index, follow">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </Head>
    <Header :dataHeader="dataBook.header" />
    <section  class=" bg-blend-multiply block">
        <div class="px-4 mx-auto max-w-screen-xl py-24 lg:py-56">
            <!-- Hero -->
            <div class="relative bg-linear-to-bl from-blue-100 via-transparent dark:from-blue-950 dark:via-transparent">
                <div class="max-w-[85rem] px-4 px-6 mx-auto">
                    <!-- Grid -->
                    <div class="grid md:grid-cols-2 gap-8 lg:gap-12">
                        <div>
                            <p class="inline-block text-sm font-medium bg-clip-text bg-linear-to-l from-blue-600 to-violet-500 text-transparent dark:from-blue-400 dark:to-violet-400">
                                Reclamo Virtual
                            </p>

                            <!-- Title -->
                            <div class="mt-4 md:mb-16 max-w-2xl">
                                <h1 class="mb-14 font-semibold text-gray-800 text-4xl lg:text-5xl dark:text-neutral-200">
                                    Libro de reclamaciones
                                </h1>
                                <p class="text-gray-600 text-justify dark:text-neutral-400">
                                    <strong>¿Qué sucede después de enviar su reclamación?</strong>
                                </p>
                                <p class="text-gray-600 text-justify dark:text-neutral-400">
                                    La empresa recibirá su reclamación y comenzará a procesarla. Usted recibirá un correo electrónico de confirmación con un código de seguimiento para monitorizar el estado de su reclamación.
                                </p>
                            </div>
                            <!-- End Title -->
                             <blockquote class="relative w-full">
                                <svg class="absolute top-0 start-0 transform -translate-x-6 -translate-y-8 size-16 text-gray-200 dark:text-neutral-800" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M7.39762 10.3C7.39762 11.0733 7.14888 11.7 6.6514 12.18C6.15392 12.6333 5.52552 12.86 4.76621 12.86C3.84979 12.86 3.09047 12.5533 2.48825 11.94C1.91222 11.3266 1.62421 10.4467 1.62421 9.29999C1.62421 8.07332 1.96459 6.87332 2.64535 5.69999C3.35231 4.49999 4.33418 3.55332 5.59098 2.85999L6.4943 4.25999C5.81354 4.73999 5.26369 5.27332 4.84476 5.85999C4.45201 6.44666 4.19017 7.12666 4.05926 7.89999C4.29491 7.79332 4.56983 7.73999 4.88403 7.73999C5.61716 7.73999 6.21938 7.97999 6.69067 8.45999C7.16197 8.93999 7.39762 9.55333 7.39762 10.3ZM14.6242 10.3C14.6242 11.0733 14.3755 11.7 13.878 12.18C13.3805 12.6333 12.7521 12.86 11.9928 12.86C11.0764 12.86 10.3171 12.5533 9.71484 11.94C9.13881 11.3266 8.85079 10.4467 8.85079 9.29999C8.85079 8.07332 9.19117 6.87332 9.87194 5.69999C10.5789 4.49999 11.5608 3.55332 12.8176 2.85999L13.7209 4.25999C13.0401 4.73999 12.4903 5.27332 12.0713 5.85999C11.6786 6.44666 11.4168 7.12666 11.2858 7.89999C11.5215 7.79332 11.7964 7.73999 12.1106 7.73999C12.8437 7.73999 13.446 7.97999 13.9173 8.45999C14.3886 8.93999 14.6242 9.55333 14.6242 10.3Z" fill="currentColor"/>
                                </svg>

                                <div class="relative z-10">
                                    <p class="text-sm italic text-justify text-gray-800 dark:text-white">
                                        Al enviar esta reclamación, usted autoriza a Aracode Perú a tratar sus datos personales para la gestión de su reclamo y, en general, para los fines relacionados con la provisión de nuestros productos o servicios. Su información será procesada respetando nuestra <Link class="text-primary duration-300 hover:underline dark:text-warning">Política de Privacidad</Link> y la Ley N° 29733. Garantizamos la confidencialidad y seguridad de sus datos. Para mayor información, puede revisar nuestra <Link class="text-primary duration-300 hover:underline dark:text-warning">Política de Privacidad</Link> y nuestros <Link :href="route('terms_main')" class="text-primary duration-300 hover:underline dark:text-warning">Términos y Condiciones</Link>.
                                    </p>
                                </div>
                                <div class="relative z-10 mt-16 border-2 p-6 rounded-lg">
                                    <p class="text-sm italic text-justify text-gray-800 dark:text-white">
                                        {{ $page.props.company.name }}<br />
                                        Av. A Casuarinas P1 26, Nuevo Chimbote, Áncash, Perú<br />
                                        El libro de reclamaciones virtual de Aracode Perú es un servicio proporcionado en cumplimiento de la Ley N° 29571, Código de Protección y Defensa del Consumidor.
                                        La formulación del reclamo no impide acudir a otras vías de solución de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI.
                                        El proveedor {{ $page.props.company.name }} dará respuesta al reclamo o queja en un plazo no mayor a quince (15) días hábiles, el cual es improrrogable.
                                        Sus datos personales serán conservados durante el tiempo requerido por la normativa vigente para asegurar la correcta gestión de su reclamación y cumplir con nuestras obligaciones legales. Más información sobre el tratamiento de sus datos, sus derechos y los términos de uso de nuestro servicio, consulte nuestra <Link class="text-primary duration-300 hover:underline dark:text-warning">Política de Privacidad</Link> y nuestros <Link :href="route('terms_main')" class="text-primary duration-300 hover:underline dark:text-warning">Términos y Condiciones</Link>.
                                    </p>
                                </div>
                            </blockquote>
                        </div>
                        <!-- End Col -->
                        <div>
                            <!-- Form -->
                            <form>
                                <div class="lg:max-w-lg lg:mx-auto lg:me-0 ms-auto space-y-6">
                                    <!-- Card -->
                                    <div class="panel dark:bg-neutral-900">
                                        <div>
                                            <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">Consumidor reclamante</h1>
                                            <p class="mt-2 text-sm text-gray-600 dark:text-neutral-400">
                                                Por favor, ingrese sus datos personales para poder atender su reclamación.
                                            </p>
                                        </div>
                                        <div class="mt-6 space-y-6">
                                            <div>
                                                <label for="txtname" class="dark:text-gray-200">Nombre</label>
                                                <input v-model="bookForm.names" id="txtname" type="text" class="form-input" required />
                                                <p class="text-gray-900 text-xs mt-1">Su nombre completo.<br />Ejemplo: "Juan José Pérez Quispe".</p>
                                                <InputError :message="bookForm.errors.names" class="mt-2" />
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <label for="cbxtdni" class="dark:text-gray-200">Tipo de documento de identidad</label>
                                                    <select v-model="bookForm.tipoIdentificacion" id="cbxtdni" class="form-select" >
                                                        <template v-for="tipo in dataBook.tipoDocuemntos">
                                                            <option :value="tipo.id">{{ tipo.description }}</option>
                                                        </template>
                                                    </select>
                                                    <p class="text-gray-900 text-xs mt-1">Elija el tipo de documento.</p>
                                                    <InputError :message="bookForm.errors.tipoIdentificacion" class="mt-2" />
                                                </div>
                                                <div>
                                                    <label for="txtdni" class="dark:text-gray-200">Número de documento de identidad</label>
                                                    <input v-model="bookForm.dni" id="txtdni" type="text" class="form-input" />
                                                    <p class="text-gray-900 text-xs mt-1">Ejemplo: "12345678".</p>
                                                    <InputError :message="bookForm.errors.dni" class="mt-2" />
                                                </div>
                                            </div>
                                            <div>
                                                <label for="txtemail" class="dark:text-gray-200">Correo electrónico</label>
                                                <input v-model="bookForm.email" id="txtemail" type="email" class="form-input" />
                                                <p class="text-gray-900 text-xs mt-1">Su dirección de correo electrónico para poder comunicar la respuesta a su reclamación. <br />Ejemplo: "juan.perez@reclamovirtual.pe".</p>
                                                <InputError :message="bookForm.errors.email" class="mt-2" />
                                            </div>
                                            <div>
                                                <label for="txtphone" class="dark:text-gray-200">Teléfono (Opcional)</label>
                                                <input v-model="bookForm.telefono" id="txtphone" type="email" class="form-input" />
                                                <p class="text-gray-900 text-xs mt-1">Su número de teléfono para poder comunicar la respuesta a su reclamación.<br />Ejemplo: "987654321".</p>
                                                <InputError :message="bookForm.errors.telefono" class="mt-2" />
                                            </div>
                                        </div>
                                    </div>
                                    <!-- End Card -->
                                    <!-- Card -->
                                    <div class="panel dark:bg-neutral-900">
                                        <div>
                                            <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">Bien contratado</h1>
                                            <p class="mt-2 text-sm text-gray-600 dark:text-neutral-400">
                                                Por favor, ingrese los detalles del producto o servicio sobre el que desea reclamar.
                                            </p>
                                        </div>
                                        <div class="mt-6 space-y-6">
                                            <div>
                                                <label for="cbxSer" class="dark:text-gray-200">Tipo de bien contratado</label>
                                                <select v-model="bookForm.tipoBien" id="cbxSer" class="form-select">
                                                    <option value="product">Producto </option>
                                                    <option value="service">Servicio</option>
                                                </select>
                                                <p class="text-gray-900 text-xs mt-1">Elija si la reclamación es sobre un producto o un servicio.</p>
                                                <InputError :message="bookForm.errors.tipoBien" class="mt-2" />
                                            </div>
                                            <div>
                                                <label for="txtdesc" class="dark:text-gray-200">Descripción del producto o servicio</label>
                                                <textarea v-model="bookForm.descripcion_bien" id="txtdesc" class="form-textarea" rows="6"></textarea>
                                                <p class="text-gray-900 text-xs mt-1">Ayúdenos a identificar el producto o servicio sobre el que desea reclamar.<br />
                                                    Ejemplo: "Laptop marca ABC modelo 123" o "Servicios de consultoría tecnológica o auditoría de software.".</p>
                                                <InputError :message="bookForm.errors.descripcion_bien" class="mt-2" />
                                            </div>
                                            <div>
                                                <label class="inline-flex">
                                                    <input v-model="bookForm.chxMonto" type="checkbox" class="form-checkbox outline-primary peer" />
                                                    <span class="peer-checked:text-primary">Deseo reclamar un monto por el producto o servicio.</span>
                                                </label>
                                            </div>
                                            <div v-show="bookForm.chxMonto" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <label for="cbxMoneda">Monto reclamado (moneda)</label>
                                                    <select v-model="bookForm.moneda" id="cbxMoneda" class="form-select">
                                                        <template v-for="moneda in dataBook.monedas">
                                                            <option :value="moneda.id">{{ moneda.description }} ({{ moneda.symbol }})</option>
                                                        </template>
                                                    </select>
                                                    <p class="text-gray-900 text-xs mt-1">Elija la moneda en la que realizó el pago por el producto o servicio.</p>
                                                    <InputError :message="bookForm.errors.moneda" class="mt-2" />
                                                </div>
                                                <div>
                                                    <label for="txtmonto">Monto reclamado (valor)</label>
                                                    <input v-model="bookForm.monto" id="txtmonto" type="number"  class="form-input" />
                                                    <p class="text-gray-900 text-xs mt-1">Ingrese el monto reclamado, que usualmente es el valor pagado por el producto o servicio.<br />Ejemplo: "100".</p>
                                                    <InputError :message="bookForm.errors.monto" class="mt-2" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- End Card -->
                                     <!-- Card -->
                                    <div class="panel dark:bg-neutral-900">
                                        <div>
                                            <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">Reclamación y pedido</h1>
                                            <p class="mt-2 text-sm text-gray-600 dark:text-neutral-400">
                                                Por favor, cuéntenos sobre su reclamación y qué espera como solución.
                                            </p>
                                        </div>
                                        <div class="mt-6 space-y-6">
                                            <div>
                                                <label for="cbxTipoQueja" class="dark:text-gray-200">Tipo de reclamación</label>
                                                <select v-model="bookForm.tipoReclamo" id="cbxTipoQueja" class="form-select">
                                                    <option value="queja">Queja </option>
                                                    <option value="reclamo">Reclamo</option>
                                                </select>
                                                <p class="text-gray-900 text-xs mt-1">Elija "Reclamo" si la disconformidad es con el producto o servicio. Elija "Queja" si la disconformidad es con la atención, u otros aspectos no relacionados con el producto o servicio.</p>
                                                <InputError :message="bookForm.errors.tipoReclamo" class="mt-2" />
                                            </div>
                                            <div>
                                                <label for="txtReclamo" class="dark:text-gray-200">¿Cuál es su reclamación?</label>
                                                <textarea v-model="bookForm.reclamo" id="txtReclamo" class="form-textarea" rows="6"></textarea>
                                                <p class="text-gray-900 text-xs mt-1">Detalle por favor el motivo de su reclamación.</p>
                                                <InputError :message="bookForm.errors.reclamo" class="mt-2" />
                                            </div>
                                            <div>
                                                <label for="txtPedido" class="dark:text-gray-200">¿Cuál es su pedido?</label>
                                                <textarea v-model="bookForm.pedido" id="txtPedido" class="form-textarea" rows="6"></textarea>
                                                <p class="text-gray-900 text-xs mt-1">Cuéntenos qué espera como solución a su reclamación.</p>
                                                <InputError :message="bookForm.errors.pedido" class="mt-2" />
                                            </div>
                                        </div>
                                    </div>
                                    <!-- End Card -->
                                     <div>
                                        <p class="text-info text-xs my-4"><Link class="text-primary duration-300 hover:underline dark:text-warning">Política de Privacidad</Link> y nuestros <Link :href="route('terms_main')" class="text-primary duration-300 hover:underline dark:text-warning">Términos y Condiciones</Link>.</p>
                                        <label class="inline-flex">
                                            <input v-model="bookForm.acepto" type="checkbox" class="form-checkbox outline-primary peer" />
                                            <span class="peer-checked:text-primary">He leído y acepto el tratamiento de mis datos personales según lo descrito.</span>
                                        </label>
                                        <InputError :message="bookForm.errors.acepto" class="mt-2" />
                                    </div>
                                    <button @click="saveBook" :disabled="!bookForm.acepto" type="button" class="btn btn-primary btn-lg md:w-full">Enviar reclamación</button>
                                </div>
                            </form>
                            <!-- End Form -->
                        </div>
                        <!-- End Col -->
                    </div>
                    <!-- End Grid -->
                </div>
            </div>
            <!-- End Hero -->
        </div>
    </section>
    <Footer />
</template>
