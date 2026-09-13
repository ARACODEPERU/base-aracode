@extends('layouts.webpage')

@section('meta_title', 'Términos y Condiciones | ARACODE Smart Solutions')
@section('meta_description', 'Términos y condiciones de uso de los servicios y productos de ARACODE Smart Solutions.')

@section('content')
    @include('components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-16 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Legal</span>
                <h1 class="text-4xl sm:text-5xl font-bold text-white mb-6 reveal reveal-delay-1">
                    Términos y <span class="text-gradient">Condiciones</span>
                </h1>
                <p class="text-lg text-white/70 reveal reveal-delay-2">
                    Última actualización: {{ now()->format('d/m/Y') }}
                </p>
            </div>
        </div>
    </section>

    {{-- Content --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="prose prose-lg max-w-none text-ara-slate-50">

                <h2 class="text-2xl font-bold text-ara-slate-700">1. Aceptación de los Términos</h2>
                <p>Al acceder y utilizar los servicios y productos de ARACODE Smart Solutions S.A.C. (en adelante "ARACODE"), usted acepta estos Términos y Condiciones. Si no está de acuerdo, por favor no utilice nuestros servicios.</p>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">2. Descripción de Servicios</h2>
                <p>ARACODE ofrece servicios de desarrollo de software, automatización de procesos, inteligencia artificial, plataformas SaaS, facturación electrónica y transformación digital. Nuestros productos incluyen:</p>
                <ul>
                    <li><strong>KAPTA LMS:</strong> Plataforma SaaS para gestión y formación educativa</li>
                    <li><strong>Sistema de Facturación Electrónica:</strong> Solución para emisión de comprobantes electrónicos</li>
                    <li><strong>Desarrollo de Software a Medida:</strong> Soluciones personalizadas para empresas</li>
                </ul>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">3. Cuentas de Usuario</h2>
                <p>Para acceder a ciertos servicios, usted deberá crear una cuenta. Usted es responsable de:</p>
                <ul>
                    <li>Mantener la confidencialidad de sus credenciales</li>
                    <li>Todas las actividades que ocurran bajo su cuenta</li>
                    <li>Notificar inmediatamente cualquier uso no autorizado</li>
                </ul>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">4. Planes y Pagos</h2>
                <p>Los precios de nuestros planes están disponibles en nuestro sitio web. Nos reservamos el derecho de modificar los precios con previo aviso de 30 días. Los pagos se realizan de manera anticipada según el plan contratado.</p>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">5. Propiedad Intelectual</h2>
                <p>Todo el contenido, software, marcas registradas y materiales de ARACODE están protegidos por las leyes de propiedad intelectual. Queda prohibida su reproducción, distribución o modificación sin autorización expresa.</p>
                <p>Para proyectos de desarrollo a medida, los derechos de propiedad del código fuente se transferirán al cliente según lo acordado en el contrato específico.</p>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">6. Uso Aceptable</h2>
                <p>El usuario se compromete a:</p>
                <ul>
                    <li>No utilizar los servicios para fines ilícitos</li>
                    <li>No intentar acceder no autorizado a sistemas o datos</li>
                    <li>No interferir con el funcionamiento de la plataforma</li>
                    <li>Cumplir con todas las leyes aplicables</li>
                </ul>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">7. Disponibilidad del Servicio</h2>
                <p>Nos esforzamos por mantener alta disponibilidad (99% Uptime garantizado). Sin embargo, podrían presentarse interrupciones programadas para mantenimiento, las cuales serán notificadas con anticipación.</p>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">8. Limitación de Responsabilidad</h2>
                <p>ARACODE no será responsable por daños indirectos, incidentales o consecuentes derivados del uso de nuestros servicios. Nuestra responsabilidad máxima se limitará al monto pagado por el servicio en los últimos 12 meses.</p>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">9. Cancelación y Reembolso</h2>
                <p>El usuario puede cancelar su suscripción en cualquier momento. Los reembolsos se realizarán según la política de reembolso vigente y dentro de los plazos establecidos por la ley peruana.</p>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">10. Legislación Aplicable</h2>
                <p>Estos términos se rigen por las leyes de la República del Perú. Cualquier disputa será resuelta ante los tribunales competentes de la ciudad de Lima, Perú.</p>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">11. Contacto</h2>
                <p>Para preguntas sobre estos Términos y Condiciones:</p>
                <ul>
                    <li><strong>Email:</strong> contacto@aracodeperu.com</li>
                    <li><strong>Teléfono:</strong> (+51) 917 295 856</li>
                </ul>

            </div>
        </div>
    </section>

    @include('components.v2.footer')
@endsection
