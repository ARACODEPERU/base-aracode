@extends('layouts.webpage')

@section('meta_title', 'Política de Privacidad | ARACODE Smart Solutions')
@section('meta_description', 'Conoce cómo ARACODE Smart Solutions recopila, utiliza y protege tu información personal.')

@section('content')
    @include('components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-16 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Legal</span>
                <h1 class="text-4xl sm:text-5xl font-bold text-white mb-6 reveal reveal-delay-1">
                    Política de <span class="text-gradient">Privacidad</span>
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

                <h2 class="text-2xl font-bold text-ara-slate-700">1. Información que Recopilamos</h2>
                <p>En ARACODE Smart Solutions S.A.C., recopilamos información que usted nos proporciona directamente al:</p>
                <ul>
                    <li>Completar formularios de contacto</li>
                    <li>Registrarse en nuestras plataformas</li>
                    <li>Solicitar cotizaciones o propuestas</li>
                    <li>Contratar nuestros servicios o productos</li>
                </ul>
                <p>Esta información puede incluir: nombre, correo electrónico, número de teléfono, nombre de empresa, número de documento de identidad y datos de facturación.</p>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">2. Uso de la Información</h2>
                <p>Utilizamos su información personal para:</p>
                <ul>
                    <li>Responder a sus consultas y solicitudes</li>
                    <li>Proveer los servicios y productos contratados</li>
                    <li>Enviar información sobre nuestros servicios (solo si ha dado su consentimiento)</li>
                    <li>Mejorar nuestros servicios y experiencia de usuario</li>
                    <li>Cumplir obligaciones legales y fiscales</li>
                    <li>Procesar pagos y facturación electrónica</li>
                </ul>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">3. Protección de Datos</h2>
                <p>Implementamos medidas de seguridad técnicas y organizativas para proteger su información personal contra acceso no autorizado, alteración, divulgación o destrucción. Utilizamos conexiones seguras (SSL/TLS) y sistemas de autenticación robustos.</p>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">4. Compartición de Información</h2>
                <p>No vendemos ni compartimos su información personal con terceros, excepto en los siguientes casos:</p>
                <ul>
                    <li>Con proveedores de servicios que nos ayudan a operar (procesadores de pago, hosting)</li>
                    <li>Cuando sea requerido por ley o autoridad competente</li>
                    <li>Con su consentimiento explícito</li>
                </ul>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">5. Cookies y Tecnologías de Rastreo</h2>
                <p>Nuestro sitio web utiliza cookies para mejorar la experiencia del usuario. Puede configurar su navegador para rechazar cookies, aunque esto podría afectar la funcionalidad del sitio.</p>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">6. Sus Derechos</h2>
                <p>De conformidad con la Ley N° 29733 - Ley de Protección de Datos Personales de Perú, usted tiene derecho a:</p>
                <ul>
                    <li>Acceder a sus datos personales</li>
                    <li>Solicitar la rectificación de datos inexactos</li>
                    <li>Solicitar la eliminación de sus datos</li>
                    <li>Oponerse al tratamiento de sus datos</li>
                    <li>Revocar su consentimiento en cualquier momento</li>
                </ul>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">7. Retención de Datos</h2>
                <p>Mantendremos su información personal solo durante el tiempo necesario para los fines para los que fue recopilada, o según lo requiera la ley aplicable.</p>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">8. Cambios en esta Política</h2>
                <p>Nos reservamos el derecho de actualizar esta política de privacidad. Los cambios serán publicados en esta página con la fecha de última actualización.</p>

                <h2 class="text-2xl font-bold text-ara-slate-700 mt-12">9. Contacto</h2>
                <p>Si tiene preguntas sobre esta Política de Privacidad o sobre el tratamiento de sus datos personales, puede contactarnos:</p>
                <ul>
                    <li><strong>Email:</strong> contacto@aracodeperu.com</li>
                    <li><strong>Teléfono:</strong> (+51) 917 295 856</li>
                    <li><strong>Empresa:</strong> ARACODE Smart Solutions S.A.C.</li>
                    <li><strong>RUC:</strong> 20611376031</li>
                </ul>

            </div>
        </div>
    </section>

    @include('components.v2.footer')
@endsection
