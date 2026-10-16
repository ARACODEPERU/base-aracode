@extends('aracode.layouts.webpage')

@section('meta_title', 'Política de Privacidad | ARACODE Smart Solutions S.A.C.')
@section('meta_description', 'Política de Privacidad de ARACODE Smart Solutions S.A.C.: qué datos personales tratamos en nuestras webs y aplicaciones, con qué finalidades, con quién los compartimos, cuánto los conservamos y cómo ejercer tus derechos (Ley N° 29733).')

@section('content')
    @include('aracode.components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-16 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Legal</span>
                <h1 class="text-4xl sm:text-5xl font-bold text-white mb-6 reveal reveal-delay-1">
                    Política de <span class="text-gradient">Privacidad</span>
                </h1>
                {{-- La fecha de vigencia se escribe a mano (no con now()): la politica
                     debe mantener la fecha de su ultima version real, no cambiar a diario. --}}
                <p class="text-lg text-white/70 reveal reveal-delay-2">
                    Versión 2.0 — Última actualización: 16 de octubre de 2026
                </p>
            </div>
        </div>
    </section>

    {{-- Content --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Resumen --}}
            <div class="ara-card p-8 mb-12 reveal">
                <h2 class="text-xl font-bold text-ara-slate-700 mb-4">Resumen</h2>
                <ul class="list-disc list-inside text-ara-slate-500 leading-relaxed space-y-2">
                    <li><strong>Responsable:</strong> ARACODE Smart Solutions S.A.C. (RUC 20611376031), Nuevo Chimbote, Áncash, Perú.</li>
                    <li>Tratamos datos personales para atender tus consultas, matricularte en nuestros cursos, gestionar citas y atenciones de salud, administrar torneos deportivos y cumplir nuestras obligaciones legales, tributarias y de consumo.</li>
                    <li>No vendemos tus datos personales. Solo los compartimos con los proveedores indispensables para operar (pagos, correo, analítica, verificación de identidad y servicios en la nube) y cuando la ley lo exige.</li>
                    <li>Puedes ejercer tus derechos de información, acceso, portabilidad, rectificación, supresión, oposición y los demás que reconoce la ley escribiendo a <a href="mailto:contacto@aracodeperu.com" class="text-ara-blue hover:underline">contacto@aracodeperu.com</a>.</li>
                    <li>Si la aplicación tiene cuenta de usuario, puedes pedir su eliminación y la de tus datos asociados en la sección <a href="#eliminacion-de-cuenta" class="text-ara-blue hover:underline">Eliminación de tu cuenta y de tus datos</a>.</li>
                </ul>
            </div>

            {{-- Indice --}}
            <div class="border border-ara-slate-100 rounded-2xl p-8 mb-16 reveal">
                <h2 class="text-xl font-bold text-ara-slate-700 mb-4">Contenido</h2>
                <ol class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-ara-slate-500 text-sm list-decimal list-inside">
                    <li><a href="#responsable" class="hover:text-ara-blue hover:underline">Responsable del tratamiento</a></li>
                    <li><a href="#ambito" class="hover:text-ara-blue hover:underline">Ámbito y definiciones</a></li>
                    <li><a href="#datos" class="hover:text-ara-blue hover:underline">Qué datos tratamos</a></li>
                    <li><a href="#finalidades" class="hover:text-ara-blue hover:underline">Finalidades y base legal</a></li>
                    <li><a href="#obligatoriedad" class="hover:text-ara-blue hover:underline">Datos obligatorios y facultativos</a></li>
                    <li><a href="#consentimiento" class="hover:text-ara-blue hover:underline">Consentimiento y su revocación</a></li>
                    <li><a href="#bancos" class="hover:text-ara-blue hover:underline">Bancos de datos personales</a></li>
                    <li><a href="#terceros" class="hover:text-ara-blue hover:underline">Destinatarios y transferencias</a></li>
                    <li><a href="#retencion" class="hover:text-ara-blue hover:underline">Plazo de conservación</a></li>
                    <li><a href="#cookies" class="hover:text-ara-blue hover:underline">Cookies y tecnologías similares</a></li>
                    <li><a href="#seguridad" class="hover:text-ara-blue hover:underline">Seguridad de los datos</a></li>
                    <li><a href="#derechos" class="hover:text-ara-blue hover:underline">Tus derechos y cómo ejercerlos</a></li>
                    <li><a href="#menores" class="hover:text-ara-blue hover:underline">Menores de edad</a></li>
                    <li><a href="#automatizadas" class="hover:text-ara-blue hover:underline">Decisiones automatizadas</a></li>
                    <li><a href="#eliminacion-de-cuenta" class="hover:text-ara-blue hover:underline">Eliminación de cuenta y datos</a></li>
                    <li><a href="#enlaces" class="hover:text-ara-blue hover:underline">Enlaces y servicios de terceros</a></li>
                    <li><a href="#cambios" class="hover:text-ara-blue hover:underline">Cambios en esta política</a></li>
                    <li><a href="#normativa" class="hover:text-ara-blue hover:underline">Normativa aplicable y autoridad</a></li>
                    <li><a href="#contacto" class="hover:text-ara-blue hover:underline">Contacto</a></li>
                </ol>
            </div>

            {{-- 1. Responsable --}}
            <div id="responsable" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">1. Responsable del tratamiento</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    El responsable del tratamiento de tus datos personales (titular de los bancos de datos personales) es:
                </p>
                <div class="ara-card p-6">
                    <ul class="space-y-2 text-ara-slate-500 text-sm">
                        <li><strong class="text-ara-slate-700">Razón social:</strong> ARACODE Smart Solutions S.A.C.</li>
                        <li><strong class="text-ara-slate-700">RUC:</strong> 20611376031</li>
                        <li><strong class="text-ara-slate-700">Domicilio:</strong> Nuevo Chimbote, provincia del Santa, departamento de Áncash, Perú</li>
                        <li><strong class="text-ara-slate-700">Canal de privacidad:</strong> <a href="mailto:contacto@aracodeperu.com" class="text-ara-blue hover:underline">contacto@aracodeperu.com</a></li>
                        <li><strong class="text-ara-slate-700">Teléfono:</strong> (+51) 917 295 856</li>
                    </ul>
                </div>
                <p class="text-ara-slate-500 leading-relaxed mt-4">
                    Ese canal, además de atender consultas generales, es el medio habilitado para presentar solicitudes de ejercicio de derechos,
                    revocar tu consentimiento y pedir la eliminación de tu cuenta y de tus datos.
                </p>
            </div>

            {{-- 2. Ambito --}}
            <div id="ambito" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">2. Ámbito y definiciones</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    Esta política se aplica al tratamiento de datos personales que realizamos a través de:
                </p>
                <ul class="list-disc list-inside text-ara-slate-500 leading-relaxed space-y-2 mb-4">
                    <li>Nuestros sitios y páginas web corporativas (por ejemplo <strong>aracodeperu.com</strong> y las páginas de cada producto del ecosistema).</li>
                    <li>Nuestras plataformas y paneles en línea, incluidos los módulos comercial, académico, salud, facturación y gestión.</li>
                    <li>Las <strong>aplicaciones móviles para Android</strong> que publicamos en Google Play: la app de torneos <strong>Pichanguero</strong>, la app de alumnos de la academia y la app de salud (agenda de citas, consultas y atenciones). Cuando en esta política decimos «las aplicaciones», incluimos todas ellas y las que publiquemos en el futuro.</li>
                    <li>Los enlaces públicos que enviamos por correo o mensajería a nuestros clientes, alumnos y pacientes.</li>
                </ul>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    Para leer esta política ten en cuenta que:
                </p>
                <ul class="list-disc list-inside text-ara-slate-500 leading-relaxed space-y-2">
                    <li><strong>Dato personal</strong> es toda información que identifica o hace identificable a una persona natural, incluidos los identificadores en línea cuando permiten identificarla (por ejemplo una dirección IP, una cookie o un identificador de dispositivo).</li>
                    <li><strong>Datos sensibles</strong> son, entre otros, los datos de salud, los biométricos y genéticos y los que se refieren a la vida afectiva o familiar y a hábitos de la esfera íntima. Reciben protección reforzada.</li>
                    <li><strong>Titular</strong> es la persona natural a la que corresponden los datos; <strong>tratamiento</strong> es cualquier operación sobre ellos (recoger, guardar, usar, comunicar, suprimir).</li>
                    <li><strong>Encargado</strong> es el proveedor que trata datos por encargo nuestro y bajo nuestras instrucciones; <strong>destinatario</strong> es quien recibe los datos.</li>
                </ul>
            </div>

            {{-- 3. Datos --}}
            <div id="datos" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">3. Qué datos tratamos</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-6">
                    Solo tratamos los datos necesarios para cada finalidad. Estos son los grupos de datos que tratamos según el canal:
                </p>

                <div class="space-y-4">
                    <div class="ara-card p-6">
                        <h3 class="text-lg font-semibold text-ara-slate-700 mb-2">Formularios de contacto y asesoría</h3>
                        <p class="text-ara-slate-500 text-sm leading-relaxed">
                            Nombres y apellidos, correo electrónico, teléfono, empresa o institución, servicio o producto de interés y el contenido del mensaje.
                            También registramos la fecha del envío y datos técnicos del envío para protegernos del spam.
                        </p>
                    </div>

                    <div class="ara-card p-6">
                        <h3 class="text-lg font-semibold text-ara-slate-700 mb-2">Compras, pagos y comprobantes</h3>
                        <p class="text-ara-slate-500 text-sm leading-relaxed">
                            Nombres y apellidos o razón social, tipo y número de documento, correo, teléfono, dirección, tipo de comprobante
                            (boleta o factura) y datos de facturación. En la confirmación de una negociación comercial también podemos tratar
                            la constancia de pago o voucher que nos adjuntas. Los datos de tu tarjeta son ingresados y procesados directamente por
                            la pasarela de pagos: <strong>no almacenamos el número completo de tarjeta ni su código de seguridad</strong>.
                        </p>
                    </div>

                    <div class="ara-card p-6">
                        <h3 class="text-lg font-semibold text-ara-slate-700 mb-2">Cuentas de usuario, alumnos y apoderados</h3>
                        <p class="text-ara-slate-500 text-sm leading-relaxed">
                            Nombres y apellidos, tipo y número de documento, correo, teléfono, dirección, fecha de nacimiento, usuario y
                            contraseña (guardada cifrada), matrículas, cursos o programas, calificaciones, certificados, asistencia,
                            pagos y estado de tus suscripciones. En el caso de escolares, también los datos de contacto de la madre, el padre
                            o el apoderado que la institución educativa nos proporciona.
                        </p>
                    </div>

                    <div class="ara-card p-6">
                        <h3 class="text-lg font-semibold text-ara-slate-700 mb-2">Aplicaciones de alumnos y de salud</h3>
                        <p class="text-ara-slate-500 text-sm leading-relaxed mb-2">
                            En la <strong>app de alumnos</strong>: los datos de tu cuenta, tu avance académico, tus certificados y las notificaciones
                            que te enviamos. En la <strong>app de salud</strong>: datos del paciente (nombres, documento, fecha de nacimiento, sexo,
                            contacto y, cuando corresponde, dirección), agenda y citas, motivo de consulta, atenciones, indicaciones y tratamientos,
                            exámenes, odontograma y los antecedentes y alergias que consigna el profesional de salud.
                        </p>
                        <p class="text-ara-slate-500 text-sm leading-relaxed">
                            <strong>Los datos de salud son datos sensibles</strong> y reciben el tratamiento reforzado que describe esta política.
                        </p>
                    </div>

                    <div class="ara-card p-6">
                        <h3 class="text-lg font-semibold text-ara-slate-700 mb-2">App de torneos (Pichanguero)</h3>
                        <p class="text-ara-slate-500 text-sm leading-relaxed mb-2">
                            La app muestra información de un campeonato: nombres de equipos, nombres de jugadores y del cuerpo técnico,
                            resultados, tablas de posiciones, goleadores y estadísticas deportivas. La app <strong>no requiere una cuenta propia</strong>:
                            su contenido es el que el organizador del torneo publica.
                        </p>
                        <p class="text-ara-slate-500 text-sm leading-relaxed">
                            Si participas en un torneo, quien organiza (el club, la liga o la institución) decide qué datos de los jugadores se publican
                            y debe contar con tu autorización o, si eres menor de edad, la de tu madre, padre o apoderado. Si quieres corregir o retirar
                            tu información de un torneo, escríbenos y la derivaremos al organizador.
                        </p>
                    </div>

                    <div class="ara-card p-6">
                        <h3 class="text-lg font-semibold text-ara-slate-700 mb-2">Libro de Reclamaciones, postulaciones y otros canales</h3>
                        <p class="text-ara-slate-500 text-sm leading-relaxed">
                            En el Libro de Reclamaciones tratamos los datos que exige la normativa de protección al consumidor (nombres, documento,
                            domicilio, correo, teléfono, tipo de bien o servicio y el detalle del reclamo o queja). En las postulaciones laborales,
                            tu currículum y los datos de contacto profesional. Si nos escribes por WhatsApp, redes sociales o correo, tratamos los datos
                            que nos entregues en esa conversación.
                        </p>
                    </div>

                    <div class="ara-card p-6">
                        <h3 class="text-lg font-semibold text-ara-slate-700 mb-2">Verificación de identidad (DNI y RUC)</h3>
                        <p class="text-ara-slate-500 text-sm leading-relaxed">
                            Cuando confirmas una negociación o completas un formulario que lo requiere, consultamos tu número de documento en los
                            servicios de consulta de RENIEC (persona natural) y SUNAT (persona jurídica) a través de un proveedor autorizado de
                            intermediación, para verificar tus nombres y razón social. Esos proveedores <strong>no reciben otra información tuya</strong>
                            que el número consultado.
                        </p>
                    </div>

                    <div class="ara-card p-6">
                        <h3 class="text-lg font-semibold text-ara-slate-700 mb-2">Datos de navegación y cookies</h3>
                        <p class="text-ara-slate-500 text-sm leading-relaxed">
                            Dirección IP, tipo de dispositivo y navegador, páginas visitadas, fecha y hora, y estadísticas agregadas de uso obtenidas
                            con Google Analytics. Usamos estas mediciones para analizar el tráfico y mejorar el sitio; no las usamos para publicidad
                            personalizada ni para vender perfiles.
                        </p>
                    </div>
                </div>
            </div>

            {{-- 4. Finalidades --}}
            <div id="finalidades" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">4. Para qué usamos tus datos y con qué base legal</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-6">
                    Tratamos tus datos personales solo para las finalidades informadas en el momento de la recolección:
                </p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead>
                            <tr class="border-b border-ara-slate-100">
                                <th class="py-3 pr-4 font-semibold text-ara-slate-700 align-top">Finalidad</th>
                                <th class="py-3 pr-4 font-semibold text-ara-slate-700 align-top">Datos que usa</th>
                                <th class="py-3 font-semibold text-ara-slate-700 align-top">Base legal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-ara-slate-100 align-top">
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Responder consultas, cotizaciones y solicitudes de asesoría</td>
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Formularios de contacto</td>
                                <td class="py-3 text-ara-slate-500 align-top">Tu consentimiento</td>
                            </tr>
                            <tr class="border-b border-ara-slate-100 align-top">
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Matricular, brindar y evaluar los servicios académicos; emitir certificados</td>
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Cuenta, matrícula, calificaciones y asistencia</td>
                                <td class="py-3 text-ara-slate-500 align-top">Ejecución del contrato y consentimiento</td>
                            </tr>
                            <tr class="border-b border-ara-slate-100 align-top">
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Gestionar la agenda, las citas y las atenciones de salud</td>
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Datos del paciente, datos sensibles de salud</td>
                                <td class="py-3 text-ara-slate-500 align-top">Consentimiento expreso y por escrito del paciente (o de quien legalmente lo represente) y normativa sanitaria</td>
                            </tr>
                            <tr class="border-b border-ara-slate-100 align-top">
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Administrar torneos deportivos y publicar fixture, resultados y estadísticas</td>
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Datos deportivos de equipos y jugadores</td>
                                <td class="py-3 text-ara-slate-500 align-top">Autorización del organizador y de los participantes (o de sus representantes)</td>
                            </tr>
                            <tr class="border-b border-ara-slate-100 align-top">
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Procesar pagos, emitir comprobantes electrónicos y gestionar cobranzas</td>
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Datos de compra y facturación</td>
                                <td class="py-3 text-ara-slate-500 align-top">Ejecución del contrato y cumplimiento de obligaciones tributarias</td>
                            </tr>
                            <tr class="border-b border-ara-slate-100 align-top">
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Enviar avisos operativos de tus servicios (suscripciones por vencer, citas, asistencia, avisos escolares) por correo, notificación o mensaje de texto</td>
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Datos de contacto</td>
                                <td class="py-3 text-ara-slate-500 align-top">Ejecución del contrato e interés legítimo en avisarte de un servicio que ya usas</td>
                            </tr>
                            <tr class="border-b border-ara-slate-100 align-top">
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Enviar promociones, novedades y contenidos comerciales</td>
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Correo, teléfono y nombre</td>
                                <td class="py-3 text-ara-slate-500 align-top">Tu consentimiento, que puedes revocar en cualquier momento</td>
                            </tr>
                            <tr class="border-b border-ara-slate-100 align-top">
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Atender reclamos y quejas del Libro de Reclamaciones</td>
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Datos del reclamo</td>
                                <td class="py-3 text-ara-slate-500 align-top">Cumplimiento legal (Código de Protección y Defensa del Consumidor)</td>
                            </tr>
                            <tr class="border-b border-ara-slate-100 align-top">
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Medir el tráfico y mejorar la seguridad y el rendimiento de la web y las apps</td>
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Datos de navegación y cookies de medición</td>
                                <td class="py-3 text-ara-slate-500 align-top">Tu consentimiento e interés legítimo en la seguridad del servicio</td>
                            </tr>
                            <tr class="border-b border-ara-slate-100 align-top">
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Prevenir el fraude, los abusos y los accesos indebidos</td>
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Identificadores de acceso, registros de auditoría</td>
                                <td class="py-3 text-ara-slate-500 align-top">Interés legítimo y cumplimiento legal</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-ara-slate-500 leading-relaxed mt-6">
                    <strong>No usamos tus datos para finalidades distintas</strong> a las aquí informadas. Si necesitáramos tratarlos para un fin diferente,
                    te lo informaríamos antes y, cuando corresponda, pediríamos tu consentimiento. Tampoco cedemos tus datos a terceros para que los usen
                    con fines propios, ni los vendemos ni los usamos para publicidad de terceros.
                </p>
            </div>

            {{-- 5. Obligatoriedad --}}
            <div id="obligatoriedad" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">5. Datos obligatorios y facultativos</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    En cada formulario indicamos qué campos son necesarios y qué campos opcionales puedes dejar en blanco:
                </p>
                <ul class="list-disc list-inside text-ara-slate-500 leading-relaxed space-y-2">
                    <li>Los campos <strong>marcados como obligatorios</strong> son necesarios para atender tu solicitud, celebrar el contrato, emitir el comprobante o brindarte el servicio. Si no los proporcionas, no podremos continuar: por ejemplo, sin tu nombre y documento no podemos emitir una boleta o factura, y sin la constancia de pago no podemos verificar el pago.</li>
                    <li>Los campos <strong>facultativos</strong> (por ejemplo teléfono o empresa en el formulario de contacto) nos ayudan a atenderte mejor. Si decides no completarlos, recibirás el servicio igual.</li>
                    <li>En el <strong>Libro de Reclamaciones</strong> los datos que exige la ley son obligatorios; no podemos tramitar un reclamo sin ellos.</li>
                    <li>En la <strong>app de salud</strong> los datos clínicos son necesarios para atenderte con seguridad: un dato de salud omitido o incorrecto puede afectar la atención del profesional.</li>
                </ul>
            </div>

            {{-- 6. Consentimiento --}}
            <div id="consentimiento" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">6. Consentimiento y su revocación</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    Cuando la base del tratamiento es tu consentimiento, este debe ser <strong>libre, previo, expreso, inequívoco e informado</strong>.
                    Por eso:
                </p>
                <ul class="list-disc list-inside text-ara-slate-500 leading-relaxed space-y-2 mb-4">
                    <li>Solo marcamos las casillas de aceptación después de una acción tuya; nunca las dejamos premarcadas ni deducimos tu consentimiento por tu silencio o por seguir navegando.</li>
                    <li>Los consentimientos de cada documento son independientes: puedes aceptar los términos y rechazar, por ejemplo, el envío de publicidad.</li>
                    <li>Para <strong>datos sensibles</strong> (datos de salud, biométricos o que afecten tu intimidad) pedimos tu consentimiento <strong>expreso y por escrito</strong>, con una modalidad que garantice tu voluntad, o el de tu representante legal cuando corresponda.</li>
                    <li>Puedes <strong>revocar tu consentimiento</strong> en cualquier momento, sin costo y sin necesidad de justificar tu decisión, escribiendo al canal de privacidad. La revocación no afecta los tratamientos ya realizados ni los que la ley nos obliga a mantener (por ejemplo, los comprobantes de pago emitidos).</li>
                    <li>Si quieres <strong>impedir que te enviemos publicidad</strong> o que te contactemos por un canal concreto, basta que nos lo pidas por el mismo canal: atenderemos tu solicitud en un plazo máximo de 10 días hábiles.</li>
                </ul>
            </div>

            {{-- 7. Bancos de datos --}}
            <div id="bancos" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">7. Bancos de datos personales</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    Tus datos se almacenan en bancos de datos personales de titularidad de ARACODE Smart Solutions S.A.C., organizados según el
                    tipo de relación que tengas con nosotros: clientes y contactos comerciales, alumnos y apoderados, pacientes, usuarios de las
                    aplicaciones, postulantes y suscriptores del blog.
                </p>
                <p class="text-ara-slate-500 leading-relaxed">
                    La inscripción de los bancos de datos personales ante el Registro Nacional de Protección de Datos Personales a cargo de la
                    Autoridad Nacional de Protección de Datos Personales es gratuita y de aprobación automática. Si necesitas conocer el detalle del
                    banco que corresponde a tus datos, escríbenos al canal de privacidad y te informaremos.
                </p>
            </div>

            {{-- 8. Terceros --}}
            <div id="terceros" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">8. Destinatarios, encargados y transferencias</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-6">
                    <strong>No vendemos ni alquilamos tus datos personales.</strong> Los compartimos solo con los proveedores que necesitamos para
                    operar el servicio (que actúan como encargados de tratamiento bajo nuestras instrucciones), con quien tú autorices y con las
                    autoridades cuando la ley lo exige. Estos son los destinatarios principales:
                </p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead>
                            <tr class="border-b border-ara-slate-100">
                                <th class="py-3 pr-4 font-semibold text-ara-slate-700 align-top">Proveedor / destinatario</th>
                                <th class="py-3 pr-4 font-semibold text-ara-slate-700 align-top">Datos que recibe</th>
                                <th class="py-3 font-semibold text-ara-slate-700 align-top">Para qué</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-ara-slate-100 align-top">
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Pasarela de pagos (Mercado Pago)</td>
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Importe, datos del comprobante y los datos de la tarjeta que tú ingresas en su plataforma</td>
                                <td class="py-3 text-ara-slate-500 align-top">Procesar el pago de tus compras</td>
                            </tr>
                            <tr class="border-b border-ara-slate-100 align-top">
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Google (Analytics y, si lo autorizas, sincronización de agenda con Google Calendar)</td>
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Datos de navegación y cookies de medición; en la sincronización de agenda, el detalle de las citas que decidas sincronizar</td>
                                <td class="py-3 text-ara-slate-500 align-top">Medir el uso del sitio y coordinar agendas de atención</td>
                            </tr>
                            <tr class="border-b border-ara-slate-100 align-top">
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Proveedor de consulta de identidad (RENIEC / SUNAT)</td>
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">El número de DNI o RUC consultado</td>
                                <td class="py-3 text-ara-slate-500 align-top">Verificar tu identidad y razón social antes de formalizar una operación</td>
                            </tr>
                            <tr class="border-b border-ara-slate-100 align-top">
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Proveedor de mensajería de texto</td>
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Número de celular y el texto del aviso</td>
                                <td class="py-3 text-ara-slate-500 align-top">Enviar avisos operativos de asistencia, citas y vencimientos</td>
                            </tr>
                            <tr class="border-b border-ara-slate-100 align-top">
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Proveedores de infraestructura (hosting, nube y correo transaccional)</td>
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Los datos necesarios para alojar el servicio y enviar nuestros correos</td>
                                <td class="py-3 text-ara-slate-500 align-top">Operar la plataforma, las copias de seguridad y las comunicaciones</td>
                            </tr>
                            <tr class="border-b border-ara-slate-100 align-top">
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Organizadores de torneos y entidades educativas o de salud cliente</td>
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Los datos que ellos mismos registran para su campeonato, su institución o su consulta</td>
                                <td class="py-3 text-ara-slate-500 align-top">Prestarles el servicio; ellos actúan como responsables de esos datos</td>
                            </tr>
                            <tr class="border-b border-ara-slate-100 align-top">
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Autoridades competentes (SUNAT, INDECOPI, ANPDP, juzgados, Ministerio Público)</td>
                                <td class="py-3 pr-4 text-ara-slate-500 align-top">Los datos requeridos en el pedido</td>
                                <td class="py-3 text-ara-slate-500 align-top">Cumplir una obligación legal o atender un requerimiento válido</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-ara-slate-500 leading-relaxed mt-6 mb-4">
                    <strong>Transferencias internacionales.</strong> Algunos de estos proveedores almacenan o procesan información en servidores
                    ubicados fuera del Perú. Cuando eso ocurre, exigimos contractualmente que apliquen un nivel de protección adecuado equivalente
                    al de la normativa peruana, y cumplimos con las obligaciones de información e inscripción de flujos internacionales que correspondan.
                    Si quieres conocer el país de destino de un flujo concreto, escríbenos al canal de privacidad.
                </p>
                <p class="text-ara-slate-500 leading-relaxed">
                    Con todos los encargados suscribimos compromisos de confidencialidad y de seguridad de la información. Si un proveedor tratara
                    tus datos para fines propios, tendría que informártelo e identificarse como responsable.
                </p>
            </div>

            {{-- 9. Retencion --}}
            <div id="retencion" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">9. Cuánto tiempo conservamos tus datos</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    Conservamos tus datos solo mientras sean necesarios para la finalidad con la que los obtuvimos y durante los plazos que la ley
                    nos obliga a mantenerlos. Terminados esos plazos, los eliminamos o los anonimizamos de forma que ya no permitan identificarte:
                </p>
                <ul class="list-disc list-inside text-ara-slate-500 leading-relaxed space-y-2">
                    <li><strong>Consultas y cotizaciones:</strong> hasta 2 años desde el último contacto, para dar seguimiento comercial.</li>
                    <li><strong>Cuentas de usuario y datos académicos:</strong> mientras la cuenta esté activa y hasta 5 años después de la última actividad o del término de la relación, para atender requerimientos de certificación y reclamos.</li>
                    <li><strong>Datos de salud (historia clínica, atenciones, odontograma):</strong> durante el plazo que exige la normativa sectorial de salud para su conservación, con acceso restringido al personal asistencial autorizado.</li>
                    <li><strong>Comprobantes, pagos y facturación:</strong> durante los plazos de conservación de la documentación contable y tributaria que exige la normativa vigente.</li>
                    <li><strong>Avisos y comunicaciones comerciales:</strong> hasta que revoques tu consentimiento o solicites la baja.</li>
                    <li><strong>Libro de Reclamaciones:</strong> durante el plazo de conservación de los registros de reclamos exigido por la normativa de protección al consumidor.</li>
                    <li><strong>Postulaciones laborales:</strong> hasta 1 año desde la postulación, salvo que autorices conservar tu candidatura para futuras convocatorias.</li>
                    <li><strong>Registros de seguridad y auditoría:</strong> hasta 2 años, para investigar incidentes y prevenir fraudes.</li>
                </ul>
            </div>

            {{-- 10. Cookies --}}
            <div id="cookies" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">10. Cookies y tecnologías similares</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    Usamos cookies y tecnologías similares para que el sitio funcione (sesión y protección contra ataques), para recordar tus
                    preferencias y para medir el tráfico con Google Analytics. Los identificadores en línea son datos personales cuando permiten
                    identificarte; por eso los tratamos con estas mismas reglas.
                </p>
                <p class="text-ara-slate-500 leading-relaxed">
                    Puedes aceptar o rechazar las cookies no necesarias y configurar tu navegador para bloquearlas, aunque algunas funciones podrían
                    dejar de operar. Detallamos las categorías, los ejemplos de cookies que usamos y cómo administrarlas en nuestra
                    <a href="{{ route('politica_cookies') }}" class="text-ara-blue hover:underline">Política de Cookies</a>.
                </p>
            </div>

            {{-- 11. Seguridad --}}
            <div id="seguridad" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">11. Seguridad de los datos</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    Aplicamos medidas técnicas, organizativas y legales para proteger tus datos personales contra acceso no autorizado, pérdida,
                    alteración, divulgación o destrucción:
                </p>
                <ul class="list-disc list-inside text-ara-slate-500 leading-relaxed space-y-2">
                    <li>Conexiones cifradas mediante <strong>HTTPS con certificados TLS</strong> en la web, las plataformas y el tráfico de las aplicaciones.</li>
                    <li>Contraseñas almacenadas <strong>cifradas con algoritmos de hash</strong>; nunca guardamos contraseñas en texto legible ni el número completo de tarjeta.</li>
                    <li>Control de acceso por roles y por módulo, con registro de auditoría de las operaciones sensibles (por ejemplo el acceso a historias clínicas y atenciones).</li>
                    <li>Copias de seguridad, servidores actualizados, cortafuegos y monitoreo del servicio.</li>
                    <li>El personal y los proveedores que tratan datos personales asumen compromisos de confidencialidad.</li>
                    <li>Tratamiento reforzado para datos sensibles: acceso limitado al personal autorizado y finalidades estrictamente asistenciales.</li>
                </ul>
                <p class="text-ara-slate-500 leading-relaxed mt-4">
                    Ninguna medida es infalible, pero si detectamos un incidente de seguridad que afecte tus datos personales lo gestionamos de
                    inmediato, lo notificamos a la Autoridad Nacional de Protección de Datos Personales cuando la norma lo exige —en un plazo no
                    mayor de 48 horas desde que tomamos conocimiento, si involucra datos sensibles o un número significativo de personas— y te
                    informamos cuando el incidente pueda afectar tus derechos.
                </p>
            </div>

            {{-- 12. Derechos --}}
            <div id="derechos" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">12. Tus derechos y cómo ejercerlos</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    La Ley N° 29733 y su Reglamento te reconocen estos derechos, que puedes ejercer de forma gratuita:
                </p>
                <ul class="list-disc list-inside text-ara-slate-500 leading-relaxed space-y-2 mb-6">
                    <li><strong>Información:</strong> saber qué datos tratamos, con qué finalidad, quién los recibe y cómo ejercer tus derechos.</li>
                    <li><strong>Acceso:</strong> conocer qué datos tuyos tenemos, cómo los obtuvimos y las transferencias realizadas o previstas.</li>
                    <li><strong>Portabilidad:</strong> recibir tus datos en un formato estructurado que te permita transmitirlos a otro responsable.</li>
                    <li><strong>Actualización, inclusión, rectificación y supresión:</strong> corregir datos inexactos, incompletos o desactualizados y pedir la supresión de los que no correspondan.</li>
                    <li><strong>Oposición:</strong> oponerte a un tratamiento concreto y, en particular, a recibir publicidad o prospección comercial.</li>
                    <li><strong>Impedir el suministro:</strong> solicitar que no se compartan tus datos con determinados destinatarios.</li>
                    <li><strong>Tratamiento objetivo:</strong> no ser objeto de decisiones con efectos jurídicos o significativos basadas únicamente en un tratamiento automatizado.</li>
                    <li><strong>Tutela e indemnización:</strong> reclamar ante la autoridad cuando se te deniegue un derecho y ser indemnizado por el daño que un tratamiento indebido te cause.</li>
                </ul>

                <div class="ara-card p-6 mb-6">
                    <h3 class="text-lg font-semibold text-ara-slate-700 mb-3">Cómo presentar tu solicitud</h3>
                    <ol class="list-decimal list-inside text-ara-slate-500 text-sm leading-relaxed space-y-2">
                        <li>Escribe a <a href="mailto:contacto@aracodeperu.com" class="text-ara-blue hover:underline">contacto@aracodeperu.com</a> con el asunto «Derechos de datos personales».</li>
                        <li>Indica tu nombre completo, tu documento de identidad, un medio para responderte y el derecho que deseas ejercer (también puedes enviar el formulario del Anexo si lo prefieres).</li>
                        <li>Describe con claridad tu pedido y adjunta, si lo tienes, la documentación de sustento.</li>
                        <li>Si actúas en representación de otra persona, acompaña el poder o la representación vigente (para menores de edad, la acreditación de la patria potestad o tutela).</li>
                    </ol>
                    <p class="text-ara-slate-500 text-sm leading-relaxed mt-4">
                        No cobramos por atender tu solicitud. Si el pedido está incompleto te indicaremos qué falta para poder atenderlo.
                    </p>
                </div>

                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    Estos son los plazos máximos para responderte, contados en días hábiles desde el día siguiente de recibida tu solicitud:
                </p>
                <ul class="list-disc list-inside text-ara-slate-500 leading-relaxed space-y-2 mb-4">
                    <li><strong>Información:</strong> 8 días hábiles.</li>
                    <li><strong>Acceso y portabilidad:</strong> 20 días hábiles.</li>
                    <li><strong>Actualización, inclusión, rectificación, supresión y oposición:</strong> 10 días hábiles.</li>
                    <li><strong>Oposición a publicidad y prospección comercial:</strong> 10 días hábiles.</li>
                </ul>
                <p class="text-ara-slate-500 leading-relaxed">
                    Podemos ampliar el plazo bajo las condiciones que prevé el Reglamento, y te lo comunicaremos dentro del plazo original indicando
                    el motivo y la nueva fecha. Si no respondemos o si deniegan tu pedido, puedes acudir a la Autoridad Nacional de Protección de Datos
                    Personales (MINJUSDH) mediante el procedimiento de tutela o presentar una denuncia por mal uso de datos personales, sin perjuicio
                    de tu derecho a interponer una demanda de hábeas data ante el Poder Judicial.
                </p>
            </div>

            {{-- 13. Menores --}}
            <div id="menores" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">13. Menores de edad</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    Nuestros servicios están dirigidos a personas adultas. Cuando tratamos datos de menores de edad, lo hacemos con especial cuidado:
                </p>
                <ul class="list-disc list-inside text-ara-slate-500 leading-relaxed space-y-2">
                    <li>Si el titular es <strong>menor de 14 años</strong>, el consentimiento debe otorgarlo su madre, padre o quien ejerza la patria potestad o tutela.</li>
                    <li>Si el titular tiene <strong>entre 14 y 18 años</strong>, se admite su consentimiento conforme a su capacidad, siempre que la información se le presente de manera comprensible.</li>
                    <li>No solicitamos datos de menores que no sean necesarios para el servicio (por ejemplo, los datos de un alumno que gestiona su institución educativa o los de un jugador que inscribe un organizador de torneo) y no los usamos con fines publicitarios.</li>
                    <li>En las aplicaciones de torneos y de alumnos, los datos de los menores los registra el organizador o la institución, que deben contar con la autorización de los padres o apoderados. Los padres o apoderados pueden pedir la corrección o supresión de esos datos escribiéndonos.</li>
                </ul>
            </div>

            {{-- 14. Decisiones automatizadas --}}
            <div id="automatizadas" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">14. Decisiones automatizadas y elaboración de perfiles</h2>
                <p class="text-ara-slate-500 leading-relaxed">
                    <strong>No tomamos decisiones que afecten tus derechos basadas únicamente en un tratamiento automatizado</strong> ni elaboramos
                    perfiles comerciales con tus datos. Cuando usamos herramientas de medición como Google Analytics, obtenemos estadísticas agregadas
                    para entender el uso del sitio, no para evaluar tu comportamiento individual. Si en el futuro incorporáramos un tratamiento de este
                    tipo, te informaríamos con claridad sobre su lógica, su alcance y sus consecuencias, y habilitaríamos un mecanismo de revisión humana.
                </p>
            </div>

            {{-- 15. Eliminacion de cuenta --}}
            <div id="eliminacion-de-cuenta" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">15. Eliminación de tu cuenta y de tus datos</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    Si creaste una cuenta en nuestras plataformas o en alguna de nuestras aplicaciones (alumnos, salud, comercial), puedes solicitar
                    en cualquier momento la eliminación de tu cuenta y de los datos asociados a ella.
                </p>
                <div class="ara-card p-6 mb-4">
                    <h3 class="text-lg font-semibold text-ara-slate-700 mb-3">Cómo solicitarla</h3>
                    <ol class="list-decimal list-inside text-ara-slate-500 text-sm leading-relaxed space-y-2">
                        <li>Envía un correo a <a href="mailto:contacto@aracodeperu.com?subject=Eliminaci%C3%B3n%20de%20cuenta%20y%20datos%20personales" class="text-ara-blue hover:underline">contacto@aracodeperu.com</a> con el asunto «Eliminación de cuenta y datos personales».</li>
                        <li>Indica el nombre de la aplicación o plataforma, el correo o usuario con el que te registraste y el documento de identidad del titular de la cuenta.</li>
                        <li>Si no eres el titular (por ejemplo, eres apoderado de un alumno o el organizador de un torneo), indica tu vínculo con el titular y adjunta la acreditación correspondiente.</li>
                    </ol>
                </div>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    <strong>Qué eliminamos:</strong> los datos de la cuenta, tus datos de contacto, tus preferencias y notificaciones, y los contenidos
                    personales que no debamos conservar por obligación legal. Al eliminar la cuenta pierdes el acceso a ella y a los servicios asociados.
                </p>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    <strong>Qué podemos conservar:</strong> mantendremos solo lo que la ley nos obliga a conservar o lo que necesitemos para atender un
                    derecho o una controversia en curso. En concreto, los comprobantes y registros contables y tributarios, los registros de atención y
                    la historia clínica (conservación exigida por la normativa de salud), los registros del Libro de Reclamaciones y los registros de
                    seguridad necesarios para investigar fraudes. Esos datos quedan bloqueados, accesibles solo para cumplir la obligación que los
                    justifica, y se eliminan al vencer su plazo.
                </p>
                <p class="text-ara-slate-500 leading-relaxed">
                    Desactivar, congelar o suspender una cuenta <strong>no equivale a eliminarla</strong>. Eliminar la cuenta implica el borrado (o la
                    anonimización irreversible) de los datos asociados dentro de los 30 días calendario siguientes a la verificación de tu solicitud; te
                    confirmaremos por correo cuando se complete. Si el servicio lo presta una institución cliente nuestra (por ejemplo tu colegio o tu
                    consultorio), coordinaremos con ella la eliminación y te informaremos en el mismo plazo.
                </p>
            </div>

            {{-- 16. Enlaces --}}
            <div id="enlaces" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">16. Enlaces y servicios de terceros</h2>
                <p class="text-ara-slate-500 leading-relaxed">
                    Nuestro sitio y nuestras aplicaciones pueden contener enlaces a servicios de terceros (tiendas de aplicaciones, redes sociales,
                    plataformas de pago, mapas o servicios de mensajería). Esos terceros tienen sus propias políticas de privacidad y son responsables
                    de los datos que recogen cuando los usas directamente: el pago con tarjeta ocurre en la plataforma de la pasarela y la descarga de
                    nuestras aplicaciones se realiza a través de Google Play, sujeto a las políticas de Google. Te recomendamos revisar sus políticas
                    antes de usar esos servicios.
                </p>
            </div>

            {{-- 17. Cambios --}}
            <div id="cambios" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">17. Cambios en esta política</h2>
                <p class="text-ara-slate-500 leading-relaxed">
                    Podemos actualizar esta política para reflejar cambios en nuestros servicios, en los proveedores que usamos o en la normativa
                    aplicable. La versión vigente estará siempre publicada en esta página con su fecha de actualización y un número de versión.
                    Si el cambio es sustancial y afecta tus derechos, además de publicarlo aquí te avisaremos por correo o mediante un aviso visible
                    en el servicio antes de que empiece a regir, y cuando la ley lo exija volveremos a pedirte tu consentimiento.
                </p>
            </div>

            {{-- 18. Normativa --}}
            <div id="normativa" class="mb-14 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">18. Normativa aplicable y autoridad</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-4">
                    Esta política se rige por la legislación peruana, en particular:
                </p>
                <ul class="list-disc list-inside text-ara-slate-500 leading-relaxed space-y-2">
                    <li><strong>Ley N° 29733</strong>, Ley de Protección de Datos Personales, y su reglamento vigente aprobado por el <strong>Decreto Supremo N° 016-2024-JUS</strong>.</li>
                    <li>Normas complementarias y directivas de la <strong>Autoridad Nacional de Protección de Datos Personales</strong>, adscrita al Ministerio de Justicia y Derechos Humanos.</li>
                    <li><strong>Ley N° 29571</strong>, Código de Protección y Defensa del Consumidor, para la atención del Libro de Reclamaciones.</li>
                    <li>La normativa sectorial aplicable a los datos de salud y a la actividad académica, en lo que resulte pertinente.</li>
                </ul>
                <p class="text-ara-slate-500 leading-relaxed mt-4">
                    Si consideras que no hemos atendido correctamente tus derechos, puedes presentar una denuncia ante la Autoridad Nacional de Protección
                    de Datos Personales (MINJUSDH) o acudir a su procedimiento de tutela. También puedes ejercer la acción de hábeas data ante el Poder
                    Judicial.
                </p>
            </div>

            {{-- 19. Contacto --}}
            <div id="contacto" class="bg-ara-slate-50 rounded-2xl p-8 reveal">
                <h2 class="text-2xl font-bold text-ara-slate-700 mb-4">19. Contacto</h2>
                <p class="text-ara-slate-500 leading-relaxed mb-6">
                    Si tienes dudas sobre esta Política de Privacidad o sobre el tratamiento de tus datos personales, contáctanos:
                </p>
                <ul class="space-y-2 text-ara-slate-500">
                    <li class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-ara-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>contacto@aracodeperu.com</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-ara-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span>(+51) 917 295 856</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-ara-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>ARACODE Smart Solutions S.A.C. — Nuevo Chimbote, Áncash, Perú</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-ara-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>RUC: 20611376031</span>
                    </li>
                </ul>
            </div>

        </div>
    </section>

    @include('aracode.components.v2.footer')
@endsection
