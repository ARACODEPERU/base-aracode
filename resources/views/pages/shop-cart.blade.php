@extends('layouts.webpage')

@section('content')
    <script src="https://sdk.mercadopago.com/js/v2"></script>

    <div class="tap-top"><i data-feather="chevrons-up"></i></div>
    <div class="page-wrapper" id="pageWrapper">
        <x-header />
        <div class="page-body-wrapper">
            <x-sidebar />

            <div class="page-body checkout-page-body">
                <div class="container-fluid checkout-container">
                    <div class="checkout-page-heading">
                        <span>Compra segura</span>
                        <h1>Finaliza tu inscripción</h1>
                        <p>Revisa tus cursos, completa el pago y activa tu acceso en pocos pasos.</p>
                    </div>

                    <div id="checkout-alert" class="alert alert-danger d-none"></div>
                    <div id="doc-type-toast" class="doc-type-toast d-none"></div>

                    <section id="step-payment" class="checkout-panel">
                        <div class="checkout-wide">
                            <div class="payment-checkout-topbar">
                                <div class="payment-brand">
                                    <span>
                                        <strong>
                                            <img class="payment-brand-logo" src="https://academy.globalcpaperu.com/themes/webpage/images/Logo_cpa_modificado.png" alt="CPA Academy">
                                        </strong>
                                        <small>Formación que impulsa tu futuro</small>
                                    </span>
                                </div>
                                <div class="card checkout-progress-card">
                                    <div class="checkout-steps">
                                        <button class="checkout-step active" data-step-label="payment">
                                            <span class="checkout-step-number">1</span>
                                            <span class="checkout-step-text" id="payment-step-text">Comprar</span>
                                        </button>
                                        <button class="checkout-step" data-step-label="final">
                                            <span class="checkout-step-number">2</span>
                                            <span class="checkout-step-text" id="final-step-text">Crear cuenta y comprobante</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-4 align-items-start checkout-main-grid">
                            <div class="col-12">
                                <div class="card cart-table-card">
                                    <div class="cart-table-header">
                                        <div>
                                            <span>Paso 1 de 2</span>
                                            <h2>Finaliza tu inscripción</h2>
                                            <p>Pago 100% seguro con Mercado Pago</p>
                                        </div>
                                        <small id="total_productos">Cursos seleccionados</small>
                                    </div>
                                    <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                                        <table class="is-hoverable w-full text-left">
                                            <thead>
                                                <tr>
                                                    <th class="cart-product-column bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 lg:px-5">Producto</th>
                                                    <th class="cart-type-column bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 lg:px-5">Tipo</th>
                                                    <th class="cart-price-column bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 lg:px-5">Precio</th>
                                                    <th class="cart-action-column bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 lg:px-5">Accion</th>
                                                </tr>
                                            </thead>
                                            <tbody id="cart">
                                                <tr>
                                                    <td colspan="4" class="px-4 py-5 text-center">Cargando carrito...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="card p-4 checkout-payment-card">
                                    <div class="checkout-payment-header">
                                        <span class="secure-payment-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg">
                                                <path opacity="0.5" d="M2 16C2 13.1716 2 11.7574 2.87868 10.8787C3.75736 10 5.17157 10 8 10H16C18.8284 10 20.2426 10 21.1213 10.8787C22 11.7574 22 13.1716 22 16C22 18.8284 22 20.2426 21.1213 21.1213C20.2426 22 18.8284 22 16 22H8C5.17157 22 3.75736 22 2.87868 21.1213C2 20.2426 2 18.8284 2 16Z" fill="#1C274C"/>
                                                <path d="M12.75 14C12.75 13.5858 12.4142 13.25 12 13.25C11.5858 13.25 11.25 13.5858 11.25 14V18C11.25 18.4142 11.5858 18.75 12 18.75C12.4142 18.75 12.75 18.4142 12.75 18V14Z" fill="#1C274C"/>
                                                <path d="M6.75 8C6.75 5.10051 9.10051 2.75 12 2.75C14.8995 2.75 17.25 5.10051 17.25 8V10.0036C17.8174 10.0089 18.3135 10.022 18.75 10.0546V8C18.75 4.27208 15.7279 1.25 12 1.25C8.27208 1.25 5.25 4.27208 5.25 8V10.0546C5.68651 10.022 6.18264 10.0089 6.75 10.0036V8Z" fill="#1C274C"/>
                                            </svg>
                                        </span>
                                        <span class="secure-payment-copy">
                                            <strong>Ingresa los datos de tu tarjeta</strong>
                                            <small>Usamos esta informaci&oacute;n para procesar tu pago y contactarte.</small>
                                        </span>
                                    </div>
                                    <div class="checkout-total-row d-flex justify-content-between align-items-center mb-4">
                                        <span>Total</span>
                                        <strong id="totalid">S/ 0.00</strong>
                                    </div>
                                    <div class="mercadopago-shell">
                                        <div id="payment-phone-field-home">
                                        <div id="payment-phone-field" class="checkout-field mb-3">
                                            <label>Teléfono</label>
                                            <div class="phone-input-group">
                                                <select id="payment_phone_country" aria-label="Código de país">
                                                    <option value="" data-area-code="" selected>Selecciona código</option>
                                                    <option value="+51" data-area-code="51">Peru +51</option>
                                                    <option value="+54" data-area-code="54">Argentina +54</option>
                                                    <option value="+591" data-area-code="591">Bolivia +591</option>
                                                    <option value="+55" data-area-code="55">Brasil +55</option>
                                                    <option value="+56" data-area-code="56">Chile +56</option>
                                                    <option value="+57" data-area-code="57">Colombia +57</option>
                                                    <option value="+506" data-area-code="506">Costa Rica +506</option>
                                                    <option value="+53" data-area-code="53">Cuba +53</option>
                                                    <option value="+593" data-area-code="593">Ecuador +593</option>
                                                    <option value="+503" data-area-code="503">El Salvador +503</option>
                                                    <option value="+34" data-area-code="34">Espana +34</option>
                                                    <option value="+1" data-area-code="1">Estados Unidos +1</option>
                                                    <option value="+1" data-area-code="1">Canada +1</option>
                                                    <option value="+502" data-area-code="502">Guatemala +502</option>
                                                    <option value="+504" data-area-code="504">Honduras +504</option>
                                                    <option value="+52" data-area-code="52">Mexico +52</option>
                                                    <option value="+505" data-area-code="505">Nicaragua +505</option>
                                                    <option value="+507" data-area-code="507">Panama +507</option>
                                                    <option value="+595" data-area-code="595">Paraguay +595</option>
                                                    <option value="+1" data-area-code="1">Puerto Rico +1</option>
                                                    <option value="+1" data-area-code="1">Republica Dominicana +1</option>
                                                    <option value="+598" data-area-code="598">Uruguay +598</option>
                                                    <option value="+58" data-area-code="58">Venezuela +58</option>
                                                    <option value="+49" data-area-code="49">Alemania +49</option>
                                                    <option value="+376" data-area-code="376">Andorra +376</option>
                                                    <option value="+244" data-area-code="244">Angola +244</option>
                                                    <option value="+966" data-area-code="966">Arabia Saudita +966</option>
                                                    <option value="+213" data-area-code="213">Argelia +213</option>
                                                    <option value="+61" data-area-code="61">Australia +61</option>
                                                    <option value="+43" data-area-code="43">Austria +43</option>
                                                    <option value="+32" data-area-code="32">Belgica +32</option>
                                                    <option value="+501" data-area-code="501">Belice +501</option>
                                                    <option value="+229" data-area-code="229">Benin +229</option>
                                                    <option value="+359" data-area-code="359">Bulgaria +359</option>
                                                    <option value="+237" data-area-code="237">Camerun +237</option>
                                                    <option value="+86" data-area-code="86">China +86</option>
                                                    <option value="+357" data-area-code="357">Chipre +357</option>
                                                    <option value="+82" data-area-code="82">Corea del Sur +82</option>
                                                    <option value="+225" data-area-code="225">Costa de Marfil +225</option>
                                                    <option value="+385" data-area-code="385">Croacia +385</option>
                                                    <option value="+45" data-area-code="45">Dinamarca +45</option>
                                                    <option value="+971" data-area-code="971">Emiratos Arabes Unidos +971</option>
                                                    <option value="+421" data-area-code="421">Eslovaquia +421</option>
                                                    <option value="+386" data-area-code="386">Eslovenia +386</option>
                                                    <option value="+372" data-area-code="372">Estonia +372</option>
                                                    <option value="+63" data-area-code="63">Filipinas +63</option>
                                                    <option value="+358" data-area-code="358">Finlandia +358</option>
                                                    <option value="+33" data-area-code="33">Francia +33</option>
                                                    <option value="+30" data-area-code="30">Grecia +30</option>
                                                    <option value="+852" data-area-code="852">Hong Kong +852</option>
                                                    <option value="+36" data-area-code="36">Hungria +36</option>
                                                    <option value="+91" data-area-code="91">India +91</option>
                                                    <option value="+62" data-area-code="62">Indonesia +62</option>
                                                    <option value="+353" data-area-code="353">Irlanda +353</option>
                                                    <option value="+354" data-area-code="354">Islandia +354</option>
                                                    <option value="+972" data-area-code="972">Israel +972</option>
                                                    <option value="+39" data-area-code="39">Italia +39</option>
                                                    <option value="+81" data-area-code="81">Japon +81</option>
                                                    <option value="+371" data-area-code="371">Letonia +371</option>
                                                    <option value="+370" data-area-code="370">Lituania +370</option>
                                                    <option value="+352" data-area-code="352">Luxemburgo +352</option>
                                                    <option value="+60" data-area-code="60">Malasia +60</option>
                                                    <option value="+212" data-area-code="212">Marruecos +212</option>
                                                    <option value="+31" data-area-code="31">Paises Bajos +31</option>
                                                    <option value="+64" data-area-code="64">Nueva Zelanda +64</option>
                                                    <option value="+47" data-area-code="47">Noruega +47</option>
                                                    <option value="+48" data-area-code="48">Polonia +48</option>
                                                    <option value="+351" data-area-code="351">Portugal +351</option>
                                                    <option value="+44" data-area-code="44">Reino Unido +44</option>
                                                    <option value="+420" data-area-code="420">Republica Checa +420</option>
                                                    <option value="+40" data-area-code="40">Rumania +40</option>
                                                    <option value="+7" data-area-code="7">Rusia +7</option>
                                                    <option value="+65" data-area-code="65">Singapur +65</option>
                                                    <option value="+27" data-area-code="27">Sudafrica +27</option>
                                                    <option value="+46" data-area-code="46">Suecia +46</option>
                                                    <option value="+41" data-area-code="41">Suiza +41</option>
                                                    <option value="+66" data-area-code="66">Tailandia +66</option>
                                                    <option value="+90" data-area-code="90">Turquia +90</option>
                                                </select>
                                                <input id="payment_phone" type="tel" placeholder="Agrega tu número de teléfono">
                                            </div>
                                        </div>
                                        </div>
                                        <div id="cardPaymentBrick_container"></div>
                                        <div class="secure-payment-note">
                                            <span class="secure-payment-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" fill="none" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg">
                                                    <path opacity="0.5" d="M2 16C2 13.1716 2 11.7574 2.87868 10.8787C3.75736 10 5.17157 10 8 10H16C18.8284 10 20.2426 10 21.1213 10.8787C22 11.7574 22 13.1716 22 16C22 18.8284 22 20.2426 21.1213 21.1213C20.2426 22 18.8284 22 16 22H8C5.17157 22 3.75736 22 2.87868 21.1213C2 20.2426 2 18.8284 2 16Z" fill="#1C274C"/>
                                                    <path d="M12.75 14C12.75 13.5858 12.4142 13.25 12 13.25C11.5858 13.25 11.25 13.5858 11.25 14V18C11.25 18.4142 11.5858 18.75 12 18.75C12.4142 18.75 12.75 18.4142 12.75 18V14Z" fill="#1C274C"/>
                                                    <path d="M6.75 8C6.75 5.10051 9.10051 2.75 12 2.75C14.8995 2.75 17.25 5.10051 17.25 8V10.0036C17.8174 10.0089 18.3135 10.022 18.75 10.0546V8C18.75 4.27208 15.7279 1.25 12 1.25C8.27208 1.25 5.25 4.27208 5.25 8V10.0546C5.68651 10.022 6.18264 10.0089 6.75 10.0036V8Z" fill="#1C274C"/>
                                                </svg>
                                            </span>
                                            <span>Al continuar, aceptas nuestros <a href="{{ route('terms_main') }}" target="_blank" rel="noopener noreferrer">t&eacute;rminos y condiciones</a> y confirmas que los datos ingresados son correctos.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>
                            <div class="security-check-grid">
                                <div class="security-check-card security-check-card-static">
                                    <span class="security-check-icon-svg" aria-hidden="true">
                                        <svg viewBox="0 0 64 64" role="img" focusable="false">
                                            <path d="M32 6 52 14v15c0 13.2-7.8 24.8-20 29.4C19.8 53.8 12 42.2 12 29V14L32 6Z" fill="#25D366"/>
                                            <path d="M27.6 38.6 19.8 30.8l4.2-4.2 3.6 3.6L40 17.8l4.2 4.2-16.6 16.6Z" fill="#fff"/>
                                        </svg>
                                    </span>
                                    <span>
                                        <strong>Pago 100% seguro</strong>
                                        <small>Tu informaci&oacute;n est&aacute; protegida</small>
                                    </span>
                                </div>
                                <div class="security-check-card security-check-card-static security-check-card-mercado">
                                    <span>
                                        <strong>Con el respaldo de</strong>
                                        <img class="security-check-logo-wide" src="{{ asset('img/mercadopago.png') }}" alt="Mercado Pago">
                                    </span>
                                </div>
                                <div class="security-check-card security-check-card-static">
                                    <span class="security-check-icon-svg security-check-icon-whatsapp" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" role="img" focusable="false">
                                            <path d="M380.9 97.1c-41.9-42-97.7-65.1-157-65.1-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480 117.7 449.1c32.4 17.7 68.9 27 106.1 27l.1 0c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3 18.6-68.1-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1s56.2 81.2 56.1 130.5c0 101.8-84.9 184.6-186.6 184.6zM325.1 300.5c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8s-14.3 18-17.6 21.8c-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7s-12.5-30.1-17.1-41.2c-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2s-9.7 1.4-14.8 6.9c-5.1 5.6-19.4 19-19.4 46.3s19.9 53.7 22.6 57.4c2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4s4.6-24.1 3.2-26.4c-1.3-2.5-5-3.9-10.5-6.6z"/>
                                        </svg>
                                    </span>
                                    <span>
                                        <strong>Asistencia Inmediata</strong>
                                        <small>Te ayudamos en todo el proceso</small>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="step-final" class="checkout-panel d-none">
                        <div class="account-shell">
                            <div class="card checkout-progress-card">
                                <div class="checkout-steps">
                                    <button class="checkout-step done" data-step-label="payment">
                                        <span class="checkout-step-number">1</span>
                                        <span class="checkout-step-text">Comprar</span>
                                    </button>
                                    <button class="checkout-step active" data-step-label="final">
                                        <span class="checkout-step-number">2</span>
                                        <span class="checkout-step-text">Crear cuenta y comprobante</span>
                                    </button>
                                </div>
                            </div>
                            <div class="account-header">
                                <span class="final-step-kicker">Paso 2 de 2</span>
                                <h2 id="account-title">Crea tu cuenta y elige tu comprobante</h2>
                                <p id="account-subtitle">Completa tus datos para asociar la compra y emitir el comprobante.</p>
                            </div>

                            <div id="final-success-card" class="final-success-card">
                                <span class="final-success-icon" aria-hidden="true">✓</span>
                                <span>
                                    <strong>Pago recibido con exito!</strong>
                                    <small>Ahora solo falta crear tu cuenta y generar tu comprobante.</small>
                                </span>
                            </div>

                            <div class="final-section-heading">
                                <span class="final-section-icon" aria-hidden="true">
                                    <i class="fa fa-user-o"></i>
                                </span>
                                <div>
                                    <h3>1. Crea tu cuenta</h3>
                                    <p>Usaremos estos datos para tu acceso al campus virtual.</p>
                                </div>
                            </div>

                            <div class="account-choice-grid">
                                <button type="button" class="account-tab active" data-account-mode="create">
                                    <span class="account-choice-icon">+</span>
                                    <span>
                                        <strong>Crear cuenta</strong>
                                        <small>Con los datos del pago</small>
                                    </span>
                                </button>
                                <button type="button" class="account-tab" data-account-mode="login">
                                    <span class="account-choice-icon">-&gt;</span>
                                    <span>
                                        <strong>Iniciar sesion</strong>
                                        <small>Usar una cuenta existente</small>
                                    </span>
                                </button>
                            </div>

                            <input type="hidden" id="account_mode" value="create">
                            <input type="hidden" id="invoice_type" value="boleta">

                            <div id="create-account-panel" class="account-form-grid">
                                <div class="checkout-field">
                                    <label>Tipo de Documento</label>
                                    <select id="create_document_type" class="checkout-select">
                                        @foreach($documentTypes as $docType)
                                            <option value="{{ $docType->id }}" {{ $docType->id == '1' ? 'selected' : '' }}>{{ $docType->description }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="checkout-field">
                                    <label>Número de Documento</label>
                                    <input id="create_dni" type="text" placeholder="Número de documento">
                                </div>
                                <div class="checkout-field">
                                    <label>Email</label>
                                    <input id="create_email" type="email" placeholder="correo@dominio.com">
                                </div>
                                <div class="checkout-field">
                                    <label>Nombres</label>
                                    <input id="create_names" type="text" placeholder="Nombres completos">
                                </div>
                                <div class="checkout-field">
                                    <label>Contraseña</label>
                                    <input id="create_password" type="password" placeholder="Opcional, por defecto tu número de documento">
                                </div>
                            </div>

                            <div id="login-account-panel" class="account-form-grid compact d-none">
                                <div class="checkout-field">
                                    <label>Email</label>
                                    <input id="login_email" type="email" placeholder="correo@dominio.com">
                                </div>
                                <div class="checkout-field">
                                    <label>Contraseña</label>
                                    <input id="login_password" type="password" placeholder="Tu contraseña">
                                </div>
                                <div class="mt-2 text-center">
                                    <a href="#" onclick="showPasswordRecovery(); return false;" class="text-sm text-blue-600 hover:underline">
                                        ¿Olvidaste tu contraseña?
                                    </a>
                                </div>
                            </div>

                            <div class="invoice-block" id="invoice-block">
                                <div class="final-section-heading invoice-section-heading">
                                    <span class="final-section-icon" aria-hidden="true">
                                        <i class="fa fa-file-text-o"></i>
                                    </span>
                                    <div>
                                        <h3>2. Elige tu comprobante</h3>
                                        <p>Selecciona el tipo de comprobante que deseas.</p>
                                    </div>
                                </div>
                                <div class="invoice-tab-grid">
                                    <button type="button" class="invoice-tab active" data-invoice-type="boleta">
                                        <span class="invoice-radio-dot" aria-hidden="true"></span>
                                        <span class="invoice-tab-icon" aria-hidden="true"><i class="fa fa-file-text-o"></i></span>
                                        <span>
                                            <strong>Boleta de venta</strong>
                                            <small>Para personas naturales</small>
                                        </span>
                                    </button>
                                    <button type="button" class="invoice-tab" data-invoice-type="factura">
                                        <span class="invoice-radio-dot" aria-hidden="true"></span>
                                        <span class="invoice-tab-icon" aria-hidden="true"><i class="fa fa-file-text-o"></i></span>
                                        <span>
                                            <strong>Factura</strong>
                                            <small>Para empresas</small>
                                        </span>
                                    </button>
                                </div>

                                <div id="boleta-panel" class="invoice-form-grid">
                                    <div class="checkout-field">
                                        <label>Número de Documento</label>
                                        <input id="invoice_dni" type="text" placeholder="Número de documento">
                                    </div>
                                    <div class="checkout-field">
                                        <label>Nombre completo</label>
                                        <input id="invoice_name" type="text" placeholder="Nombre completo">
                                    </div>
                                    <div class="checkout-field">
                                        <label>Email</label>
                                        <input id="invoice_email" type="email" placeholder="correo@dominio.com">
                                    </div>
                                </div>

                                <div id="factura-panel" class="invoice-form-grid d-none">
                                    <div class="checkout-field">
                                        <label>RUC</label>
                                        <input id="invoice_ruc" type="text" placeholder="Número de RUC">
                                    </div>
                                    <div class="checkout-field">
                                        <label>Razón social</label>
                                        <input id="invoice_business_name" type="text" placeholder="Razón social">
                                    </div>
                                    <div class="checkout-field">
                                        <label>Dirección</label>
                                        <input id="invoice_address" type="text" placeholder="Dirección fiscal">
                                    </div>
                                </div>
                            </div>

                             <div class="invoice-actions">
                                 <div class="final-info-note">
                                     <span class="final-section-icon" aria-hidden="true">
                                         <i class="fa fa-shield"></i>
                                     </span>
                                     <span>Al finalizar te enviaremos tu comprobante de pago por correo.</span>
                                 </div>
                                 <button type="button" id="btn-finalize" class="boton-degradado-courses">
                                     <b id="btn-finalize-text">FINALIZAR COMPRA</b>
                                 </button>
                                 <small class="final-secure-note">
                                     <i class="fa fa-lock" aria-hidden="true"></i>
                                     Tu información esta protegida y solo será utilizada para tu acceso al campus.
                                 </small>
                             </div>
                         </div>
                     </section>

                     <!-- Password Recovery Modal -->
                     <div id="password-recovery-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
                         <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full">
                             <h3 class="text-lg font-medium mb-4">Recuperar Contraseña</h3>
                             <input type="email" id="recovery-email" placeholder="Tu correo electrónico" class="w-full border rounded p-2 mb-4">
                             <div class="flex gap-2">
                                 <button onclick="sendPasswordRecovery()" class="btn boton-degradado-courses">Enviar</button>
                                 <button onclick="hidePasswordRecovery()" class="btn btn-secondary">Cancelar</button>
                             </div>
                             <div id="recovery-message" class="mt-2 text-sm"></div>
                         </div>
                     </div>

                     <div id="payment-warning-modal" class="hidden payment-warning-backdrop fixed inset-0 flex items-center justify-center z-50">
                         <div class="payment-warning-dialog">
                             <div class="payment-warning-icon">!</div>
                             <h3>Pago no aprobado</h3>
                             <p id="payment-warning-message"></p>
                             <div class="payment-warning-actions">
                                 <button type="button" onclick="hidePaymentWarningModal()" class="payment-warning-button">Entendido</button>
                             </div>
                             <small class="payment-warning-note">*descuida tus datos estan protegidos y ningun cobro se ha realizado.</small>
                         </div>
                     </div>

                     <div id="phone-required-modal" class="hidden phone-required-backdrop fixed inset-0 flex items-center justify-center z-50">
                         <div class="phone-required-dialog">
                             <button type="button" onclick="hidePhoneRequiredModal()" class="phone-required-close" aria-label="Cerrar">x</button>
                             <div class="phone-required-icon">!</div>
                             <span class="phone-required-kicker">Dato requerido por seguridad</span>
                             <h3>Ingresa tu número de teléfono</h3>
                             <p id="phone-required-message">Para continuar con el pago necesitamos que selecciones el código de país e ingreses tu número de teléfono. Esto nos ayuda a proteger tu compra y contactarte si hubiera algún inconveniente.</p>
                             <button type="button" onclick="hidePhoneRequiredModal()" class="phone-required-primary">Entendido</button>
                         </div>
                     </div>

                     <div id="account-conflict-modal" class="hidden account-conflict-backdrop fixed inset-0 flex items-center justify-center z-50">
                         <div class="account-conflict-dialog">
                             <button type="button" onclick="hideAccountConflictModal()" class="account-conflict-close" aria-label="Cerrar">x</button>
                             <div class="account-conflict-icon">!</div>
                             <span class="account-conflict-kicker">Tu compra esta protegida</span>
                             <h3 id="account-conflict-title">Revisemos tus datos</h3>
                             <p id="account-conflict-message"></p>
                             <div class="account-conflict-support">
                                 <a href="mailto:informes@globalcpaperu.com">informes@globalcpaperu.com</a>
                                 <a href="tel:+51967052506">+51 967052506</a>
                             </div>
                             <div class="account-conflict-actions">
                                 <button type="button" onclick="hideAccountConflictModal()" class="account-conflict-secondary">Cerrar</button>
                                 <button type="button" onclick="goToLoginFromConflictModal()" class="account-conflict-primary">Iniciar sesion</button>
                             </div>
                         </div>
                     </div>

                     <div id="pending-paid-modal" class="hidden pending-paid-backdrop fixed inset-0 flex items-center justify-center z-50">
                         <div class="pending-paid-dialog">
                             <button type="button" onclick="hidePendingPaidModal()" class="pending-paid-close" aria-label="Cerrar">x</button>
                             <div class="pending-paid-icon">✓</div>
                             <span class="pending-paid-kicker">Tu pago esta protegido</span>
                             <h3>Tus cursos estan esperando por ti</h3>
                             <p>Tranquilo, encontramos una compra aprobada por MercadoPago que aun falta finalizar. Completa el proceso creando tu cuenta o iniciando sesion para activar tus cursos.</p>
                             <div id="pending-paid-courses" class="pending-paid-courses"></div>
                             <button type="button" onclick="hidePendingPaidModal()" class="pending-paid-primary">Completar ahora</button>
                         </div>
                     </div>
                </div>
            </div>
        </div>
        <x-footer />
    </div>

    <style>
        .checkout-page-body {
            padding: 72px 0 48px;
            background: #f6f8fb;
        }

        .checkout-container {
            max-width: 1360px;
        }

        #step-payment .checkout-wide {
            max-width: 860px;
            padding: 28px;
            border: 1px solid #e5eaf2;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
            font-family: "Poppins", sans-serif;
        }

        #step-payment .checkout-page-heading {
            display: none;
        }

        .checkout-page-heading {
            display: none;
            margin: 0 auto 12px;
            max-width: 1280px;
        }

        .checkout-page-heading span,
        .cart-table-header span,
        .checkout-payment-header span {
            display: block;
            margin-bottom: 5px;
            color: #dc2626;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .checkout-page-heading h1 {
            margin: 0;
            color: #0f172a;
            font-size: 28px;
            font-weight: 900;
            line-height: 1.15;
        }

        .checkout-page-heading p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .checkout-progress-card,
        .cart-table-card,
        .checkout-payment-card,
        .account-shell {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.07);
        }

        .checkout-progress-card {
            background: #fff;
            padding: 12px 16px !important;
            margin-bottom: 8px !important;
        }

        .payment-checkout-topbar {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) minmax(360px, 1.2fr);
            gap: 24px;
            align-items: flex-start;
            margin-bottom: 24px;
        }

        .payment-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            color: #14235f;
        }

        .payment-brand-shield {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: auto;
            height: 20px;
            flex: 0 0 auto;
        }

        .payment-brand-shield img {
            display: block;
            width: auto;
            height: 20px;
            object-fit: contain;
        }

        .payment-brand strong,
        .payment-brand small {
            display: block;
        }

        .payment-brand strong {
            color: #14235f;
            font-size: 18px;
            font-weight: 900;
            line-height: 1.1;
            text-transform: uppercase;
        }

        .payment-brand-logo {
            display: block;
            width: auto;
            height: 40px;
            object-fit: contain;
        }

        .payment-brand small {
            margin-top: 4px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        #step-payment .checkout-progress-card {
            margin: 0 !important;
            padding: 0 !important;
            border: 0;
            background: transparent;
            box-shadow: none;
        }

        #step-final .checkout-progress-card {
            margin: 0 0 30px !important;
            padding: 0 !important;
            border: 0;
            background: transparent;
            box-shadow: none;
        }

        .checkout-steps {
            position: relative;
            display: flex;
            justify-content: space-between;
            gap: 96px;
            max-width: 560px;
            margin: 0 auto;
        }

        .checkout-steps::before {
            content: "";
            position: absolute;
            top: 18px;
            left: 72px;
            right: 72px;
            height: 2px;
            background: #e2e8f0;
        }

        .checkout-step {
            position: relative;
            z-index: 1;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            width: 132px;
            border: 0;
            background: transparent;
            color: #475569;
            padding: 0;
            font-weight: 700;
            text-align: center;
        }

        .checkout-step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid #fff;
            color: #0f172a;
            box-shadow: 0 0 0 2px #d6dee9, 0 8px 18px rgba(15, 23, 42, 0.1);
            font-size: 16px;
            line-height: 1;
        }

        .checkout-step-text {
            color: #334155;
            font-size: 13px;
            line-height: 1.25;
        }

        .invoice-tab {
            border: 1px solid #d6dee9;
            background: #fff;
            color: #334155;
            padding: 10px 14px;
            font-weight: 600;
        }

        .invoice-tab.active {
            background: #0f766e;
            border-color: #0f766e;
            color: #fff;
        }

        .checkout-step.active .checkout-step-number {
            background: #dc2626;
            color: #fff;
            box-shadow: 0 0 0 2px #dc2626, 0 8px 18px rgba(220, 38, 38, 0.24);
        }

        .checkout-step.done .checkout-step-number {
            background: #fff;
            color: #0f172a;
            box-shadow: 0 0 0 2px #d6dee9, 0 8px 18px rgba(15, 23, 42, 0.1);
        }

        .checkout-step.active {
            color: #fff;
        }

        .checkout-step.active .checkout-step-text {
            color: #dc2626;
        }

        .checkout-wide {
            max-width: 1280px;
            margin: 0 auto;
        }

        .security-check-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 16px;
            padding: 14px 16px;
            border: 1px solid #e8eef5;
            border-radius: 8px;
            background: #f8fbfa;
        }

        .security-check-card {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            min-height: 72px;
            padding: 0 12px;
            background: transparent;
            border: 0;
            border-right: 1px solid #dfe7ef;
            border-radius: 0;
            color: #334155;
            text-decoration: none;
            box-shadow: none;
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }

        .security-check-card:last-child {
            border-right: 0;
        }

        .security-check-card:hover {
            color: #0f766e;
            border-color: #0f766e;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.1);
        }

        .security-check-card-static:hover {
            color: #334155;
            border-color: #dce5ef;
            transform: none;
        }

        .security-check-card img {
            width: 38px;
            height: 38px;
            object-fit: contain;
            flex: 0 0 auto;
        }

        .security-check-card img.security-check-logo-wide {
            width: 154px;
            height: 44px;
        }

        .security-check-card-mercado {
            justify-content: center;
        }

        .security-check-card-mercado span {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            width: 100%;
        }

        .security-check-icon-svg {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            flex: 0 0 auto;
        }

        .security-check-icon-svg svg {
            width: 48px;
            height: 48px;
            display: block;
        }

        .security-check-card strong,
        .security-check-card small {
            display: block;
        }

        .security-check-card strong {
            color: #0f172a;
            font-size: 15px;
            font-weight: 700;
        }

        .security-check-card small {
            margin-top: 3px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.35;
        }

        .security-check-icon-whatsapp {
            color: #25D366;
        }

        .security-check-icon-whatsapp svg {
            fill: currentColor;
        }

        .checkout-main-grid {
            align-items: flex-start;
        }

        .cart-table-card {
            overflow: hidden;
            background: #fff;
        }

        #step-payment .cart-table-card,
        #step-payment .checkout-payment-card {
            border: 0;
            box-shadow: none;
        }

        .cart-table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 0 0 16px;
            border-bottom: 0;
        }

        .cart-table-header h2,
        .checkout-payment-header h2 {
            margin: 0;
            color: #0f172a;
            font-size: 26px;
            font-weight: 900;
            line-height: 1.2;
        }

        .cart-table-header p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
        }

        .cart-table-header small {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .cart-table-card table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .cart-table-card thead th {
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc !important;
            color: #475569 !important;
            font-size: 12px;
            letter-spacing: .03em;
        }

        #step-payment .cart-table-card thead {
            display: none;
        }

        #step-payment .cart-table-card .overflow-x-auto {
            overflow: visible !important;
        }

        .cart-table-card thead th,
        .cart-table-card tbody td {
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        .cart-table-card tbody tr {
            transition: background .18s ease;
        }

        .cart-table-card tbody tr:hover {
            background: #fff7f7;
        }

        #step-payment .cart-table-card tbody tr:hover {
            background: transparent;
        }

        .cart-summary-cell {
            padding: 0 !important;
        }

        .cart-summary-item {
            display: grid;
            grid-template-columns: 170px minmax(0, 1fr) minmax(150px, auto);
            gap: 18px;
            align-items: center;
            padding: 14px;
            border: 1px solid #e5eaf2;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .cart-summary-media {
            width: 170px;
            aspect-ratio: 16 / 9;
            overflow: hidden;
            border-radius: 8px;
            background: #0f172a;
        }

        .cart-summary-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .cart-summary-name {
            display: block;
            margin: 0 0 14px;
            color: #14235f;
            font-size: 17px;
            font-weight: 900;
            line-height: 1.35;
        }

        .cart-summary-name a {
            color: inherit;
        }

        .cart-summary-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
        }

        .cart-summary-meta span {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .cart-summary-meta i {
            color: #7c8aa4;
        }

        .cart-summary-price {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
            min-width: 150px;
            color: #14235f;
        }

        .cart-summary-price span {
            color: #475569;
            font-size: 12px;
            font-weight: 800;
        }

        .cart-summary-price strong {
            color: #0676ff;
            font-size: 28px;
            font-weight: 900;
            line-height: 1;
            white-space: nowrap;
        }

        .cart-total-summary-row td {
            padding: 14px 0 0 !important;
        }

        .cart-total-summary {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 18px;
            padding: 16px 18px;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            background: #f8fbff;
        }

        .cart-total-summary span {
            color: #14235f;
            font-size: 15px;
            font-weight: 900;
        }

        .cart-total-summary strong {
            color: #0676ff;
            font-size: 30px;
            font-weight: 900;
            line-height: 1;
            white-space: nowrap;
        }

        #step-payment .card:first-child {
            overflow: hidden;
        }

        #cart .avatar {
            flex: 0 0 auto;
        }

        #cart .flex.items-center.space-x-4 > :not([hidden]) ~ :not([hidden]) {
            margin-left: 10px;
        }

        .cart-product-column {
            width: 46%;
            min-width: 250px;
        }

        .cart-type-column {
            width: 26%;
            min-width: 120px;
        }

        .cart-price-column {
            width: 16%;
            min-width: 82px;
            white-space: nowrap;
        }

        .cart-action-column {
            width: 12%;
            min-width: 58px;
            white-space: nowrap;
        }

        .boton-degradado-trash {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            padding: 0;
            line-height: 1;
            text-align: center;
        }

        .boton-degradado-trash i {
            display: block;
            margin: 0;
            line-height: 1;
        }

        .cart-product-name,
        .cart-type-text {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
            white-space: normal;
            line-height: 1.35;
        }

        .cart-product-name {
            -webkit-line-clamp: 3;
            line-clamp: 3;
            max-width: 100%;
        }

        .cart-type-text {
            -webkit-line-clamp: 3;
            line-clamp: 3;
        }

        .checkout-payment-card {
            position: sticky;
            top: 96px;
            overflow: hidden;
            background: #fff;
        }

        .checkout-payment-card::before {
            content: "";
            display: block;
            height: 4px;
            margin: -24px -24px 20px;
            background: #dc2626;
        }

        #step-payment .checkout-payment-card {
            position: static;
            padding: 0 !important;
        }

        #step-payment .checkout-payment-card::before {
            display: none;
        }

        #step-payment .checkout-payment-header .secure-payment-icon,
        #step-payment .checkout-total-row {
            display: none !important;
        }

        #step-payment .checkout-payment-header {
            margin-top: 2px;
            margin-bottom: 14px;
        }

        #step-payment .checkout-payment-card.is-free-checkout .checkout-payment-header {
            display: none;
        }

        #step-payment .checkout-wide.is-free-checkout .cart-table-header span {
            color: #e11d48;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: .5px;
        }

        #step-payment .checkout-wide.is-free-checkout .cart-table-header h2 {
            color: #0f172a;
            font-size: 32px;
            font-weight: 800;
            line-height: 40px;
        }

        #step-payment .checkout-wide.is-free-checkout .cart-table-header p {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            line-height: 20px;
        }

        #step-payment .secure-payment-copy strong {
            font-size: 18px;
        }

        #step-payment .secure-payment-copy small {
            font-size: 13px;
            font-weight: 600;
        }

        .checkout-payment-header {
            margin-bottom: 14px;
        }

        .checkout-payment-header h2 {
            font-size: 24px;
        }

        .payment-warning-backdrop {
            padding: 20px;
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(5px);
        }

        .payment-warning-dialog {
            width: min(430px, 100%);
            padding: 28px;
            border: 2px solid #fca5a5;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.34);
            text-align: center;
        }

        .payment-warning-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 68px;
            height: 68px;
            margin-bottom: 16px;
            border-radius: 50%;
            background: #dc2626;
            color: #fff;
            font-size: 42px;
            font-weight: 800;
            line-height: 1;
            box-shadow: 0 10px 24px rgba(220, 38, 38, 0.28);
        }

        .payment-warning-dialog h3 {
            margin: 0 0 10px;
            color: #991b1b;
            font-size: 22px;
            font-weight: 800;
        }

        .payment-warning-dialog p {
            margin: 0 0 22px;
            color: #334155;
            font-size: 16px;
            font-weight: 600;
        }

        .payment-warning-actions {
            display: flex;
            justify-content: center;
        }

        .payment-warning-button {
            min-width: 150px;
            padding: 11px 22px;
            border: 0;
            border-radius: 8px;
            background: #dc2626;
            color: #fff;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 10px 22px rgba(220, 38, 38, 0.22);
        }

        .payment-warning-button:hover {
            background: #b91c1c;
        }

        .payment-warning-note {
            display: block;
            margin-top: 16px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.4;
        }

        .phone-required-backdrop {
            padding: 20px;
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(5px);
        }

        .phone-required-dialog {
            position: relative;
            width: min(500px, 100%);
            padding: 34px;
            border: 2px solid #bfdbfe;
            border-top: 6px solid #2563eb;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 26px 80px rgba(15, 23, 42, 0.35);
            text-align: center;
        }

        .phone-required-close {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 50%;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 800;
            cursor: pointer;
        }

        .phone-required-icon {
            display: inline-flex;
            width: 58px;
            height: 58px;
            margin-bottom: 14px;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 30px;
            font-weight: 900;
        }

        .phone-required-kicker {
            display: block;
            margin-bottom: 8px;
            color: #2563eb;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .phone-required-dialog h3 {
            margin: 0 28px 12px;
            color: #0f172a;
            font-size: 24px;
            font-weight: 800;
        }

        .phone-required-dialog p {
            margin: 0 0 24px;
            color: #334155;
            font-size: 16px;
            line-height: 1.7;
        }

        .phone-required-primary {
            width: 100%;
            border: 0;
            border-radius: 8px;
            padding: 13px 18px;
            background: #2563eb;
            color: #fff;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 12px 28px rgba(37, 99, 235, 0.24);
        }

        .account-conflict-backdrop {
            padding: 20px;
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(5px);
        }

        .account-conflict-dialog {
            position: relative;
            width: min(540px, 100%);
            padding: 34px;
            border: 2px solid #fecaca;
            border-top: 6px solid #dc2626;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 28px 76px rgba(15, 23, 42, 0.36);
            text-align: center;
        }

        .account-conflict-close {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 32px;
            height: 32px;
            border: 0;
            border-radius: 50%;
            background: #f1f5f9;
            color: #475569;
            font-size: 20px;
            line-height: 1;
            cursor: pointer;
        }

        .account-conflict-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 68px;
            height: 68px;
            margin-bottom: 16px;
            border-radius: 50%;
            background: #dc2626;
            color: #fff;
            font-size: 36px;
            font-weight: 900;
            box-shadow: 0 0 0 9px #fee2e2;
        }

        .account-conflict-kicker {
            display: block;
            margin-bottom: 8px;
            color: #15803d;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .account-conflict-dialog h3 {
            margin: 0 28px 12px;
            color: #0f172a;
            font-size: 24px;
            font-weight: 800;
        }

        .account-conflict-dialog p {
            margin: 0;
            color: #334155;
            font-size: 16px;
            line-height: 1.7;
        }

        .account-conflict-support {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .account-conflict-support a {
            display: inline-flex;
            align-items: center;
            min-height: 36px;
            padding: 8px 12px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            background: #fff7f7;
            color: #b91c1c;
            font-size: 14px;
            font-weight: 800;
        }

        .account-conflict-actions {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.35fr);
            gap: 12px;
            margin-top: 24px;
        }

        .account-conflict-secondary,
        .account-conflict-primary {
            min-height: 46px;
            border-radius: 8px;
            font-weight: 800;
            cursor: pointer;
        }

        .account-conflict-secondary {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
        }

        .account-conflict-primary {
            border: 0;
            background: #dc2626;
            color: #fff;
        }

        .account-conflict-primary:hover {
            background: #b91c1c;
        }

        .pending-paid-backdrop {
            padding: 20px;
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(5px);
        }

        .pending-paid-dialog {
            position: relative;
            width: min(620px, 100%);
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            padding: 34px;
            border: 2px solid #bbf7d0;
            border-top: 6px solid #16a34a;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 28px 76px rgba(15, 23, 42, 0.36);
            text-align: center;
        }

        .pending-paid-close {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 32px;
            height: 32px;
            border: 0;
            border-radius: 50%;
            background: #f1f5f9;
            color: #475569;
            font-size: 20px;
            line-height: 1;
            cursor: pointer;
        }

        .pending-paid-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 68px;
            height: 68px;
            margin-bottom: 16px;
            border-radius: 50%;
            background: #16a34a;
            color: #fff;
            font-size: 34px;
            font-weight: 900;
            box-shadow: 0 0 0 9px #dcfce7;
        }

        .pending-paid-kicker {
            display: block;
            margin-bottom: 8px;
            color: #15803d;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .pending-paid-dialog h3 {
            margin: 0 28px 12px;
            color: #0f172a;
            font-size: 24px;
            font-weight: 900;
        }

        .pending-paid-dialog p {
            margin: 0 auto;
            max-width: 500px;
            color: #334155;
            font-size: 15px;
            line-height: 1.7;
        }

        .pending-paid-courses {
            display: grid;
            gap: 10px;
            margin-top: 22px;
            text-align: left;
        }

        .pending-paid-course {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
        }

        .pending-paid-course strong {
            display: block;
            color: #0f172a;
            font-size: 14px;
            line-height: 1.35;
        }

        .pending-paid-course small {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 12px;
        }

        .pending-paid-course-price {
            color: #16a34a;
            font-size: 13px;
            font-weight: 900;
            white-space: nowrap;
        }

        .pending-paid-primary {
            width: 100%;
            min-height: 46px;
            margin-top: 24px;
            border: 0;
            border-radius: 8px;
            background: #dc2626;
            color: #fff;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 10px 22px rgba(220, 38, 38, 0.22);
        }

        .pending-paid-primary:hover {
            background: #b91c1c;
        }

        .mercadopago-shell {
            max-width: 480px;
            margin: 0 auto;
        }

        .checkout-payment-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .checkout-payment-header .secure-payment-icon,
        .secure-payment-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            margin-bottom: 0;
            color: inherit;
            letter-spacing: 0;
            text-transform: none;
        }

        .secure-payment-icon svg {
            display: block;
            width: 34px;
            height: 34px;
        }

        .checkout-payment-header .secure-payment-copy,
        .secure-payment-copy {
            display: block;
            margin-bottom: 0;
            color: inherit;
            font-size: inherit;
            font-weight: inherit;
            letter-spacing: 0;
            text-transform: none;
        }

        .secure-payment-copy strong,
        .secure-payment-copy small {
            display: block;
        }

        .secure-payment-copy strong {
            color: #0f172a;
            font-size: 24px;
            font-weight: 900;
            letter-spacing: 0;
            line-height: 1.15;
            text-transform: none;
        }

        .secure-payment-copy small {
            margin-top: 3px;
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0;
            line-height: 1.35;
            text-transform: none;
        }

        .secure-payment-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 14px;
            color: #475569;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.45;
        }

        .checkout-payment-card.is-free-checkout .secure-payment-note {
            margin-top: 28px;
        }

        .secure-payment-note .secure-payment-icon {
            position: relative;
            width: 26px;
            height: 26px;
            flex-basis: 26px;
            margin-top: 4px;
            z-index: 999999;
        }

        .secure-payment-note .secure-payment-icon svg {
            width: 26px;
            height: 26px;
        }

        .secure-payment-note .secure-payment-icon svg path {
            fill: #60a5fa;
        }

        .secure-payment-note a {
            color: #2563eb;
            font-weight: 800;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        #cardPaymentBrick_container {
            margin-bottom: -18px;
            overflow: hidden;
        }

        #cardPaymentBrick_container > div,
        #cardPaymentBrick_container form {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }

        #cardPaymentBrick_container .mp-checkout-container,
        #cardPaymentBrick_container .mp-card-form,
        #cardPaymentBrick_container .mp-form,
        #cardPaymentBrick_container .mp-brick-container {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }

        #cardPaymentBrick_container button[type="submit"] {
            margin-bottom: 0 !important;
        }

        .checkout-total-row {
            padding: 16px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            background: #fff7f7;
            color: #0f172a;
            font-size: 18px;
            font-weight: 800;
        }

        .checkout-total-row strong {
            color: #dc2626;
            font-size: 24px;
            font-weight: 900;
            line-height: 1;
        }

        .account-shell {
            max-width: 860px;
            margin: 0 auto;
            padding: 38px;
            background: #fff;
        }

        .account-header {
            margin-bottom: 18px;
        }

        .final-step-kicker {
            display: block;
            margin-bottom: 10px;
            color: #0676ff;
            font-size: 13px;
            font-weight: 900;
        }

        .account-header h2 {
            margin: 0;
            color: #14235f;
            font-size: 28px;
            font-weight: 900;
            line-height: 1.2;
        }

        .account-header p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .final-success-card {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 22px 0 30px;
            padding: 18px;
            border: 1px solid #d8f3e5;
            border-radius: 8px;
            background: #f0fbf5;
        }

        .final-success-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            border-radius: 50%;
            background: #19a35b;
            color: #fff;
            font-size: 18px;
            font-weight: 900;
        }

        .final-success-card strong,
        .final-success-card small {
            display: block;
        }

        .final-success-card strong {
            color: #087043;
            font-size: 16px;
            font-weight: 900;
            line-height: 1.25;
        }

        .final-success-card small {
            margin-top: 4px;
            color: #168057;
            font-size: 13px;
            font-weight: 600;
        }

        .final-section-heading {
            display: grid;
            grid-template-columns: 28px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            margin: 0 0 16px;
        }

        .final-section-heading h3 {
            margin: 0;
            color: #14235f;
            font-size: 18px;
            font-weight: 900;
            line-height: 1.25;
        }

        .final-section-heading p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
        }

        .final-section-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            color: #0676ff;
            font-size: 20px;
        }

        .account-choice-grid {
            display: grid;
            grid-template-columns: repeat(2, 150px);
            justify-content: end;
            gap: 10px;
            margin: -48px 0 16px auto;
            max-width: 320px;
        }

        .account-tab {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            min-height: 38px;
            padding: 8px 10px;
            background: #fff;
            border: 1px solid transparent;
            border-radius: 8px;
            color: #0676ff;
            text-align: center;
            transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
        }

        .account-tab strong,
        .account-tab small {
            display: block;
        }

        .account-tab strong {
            color: #0676ff;
            font-size: 13px;
            font-weight: 900;
        }

        .account-tab small {
            display: none;
        }

        .account-tab.active {
            background: #eff6ff;
            border-color: #bfdbfe;
            box-shadow: none;
        }

        .account-choice-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 0;
            background: transparent;
            color: #0676ff;
            font-weight: 900;
            flex: 0 0 auto;
        }

        .account-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            padding: 20px;
            background: #fff;
            border: 1px solid #e5eaf2;
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.035);
        }

        .account-form-grid.compact {
            max-width: 720px;
        }

        .invoice-block {
            margin-top: 34px;
            padding-top: 22px;
            border-top: 0;
        }

        .invoice-block h3 {
            margin: 0 0 14px;
            color: #0f172a;
            font-size: 18px;
            font-weight: 700;
        }

        .invoice-tab-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 28px;
            margin-bottom: 24px;
        }

        .invoice-tab-grid .invoice-tab {
            position: relative;
            display: grid;
            grid-template-columns: 18px 22px minmax(0, 1fr);
            gap: 12px;
            align-items: center;
            min-height: 86px;
            padding: 18px 22px;
            border: 1px solid #d9e1ec;
            border-radius: 8px;
            background: #fff;
            color: #14235f;
            text-align: left;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.03);
        }

        .invoice-tab-grid .invoice-tab.active {
            border-color: #80bfff;
            background: #f8fbff;
            box-shadow: 0 0 0 1px rgba(6, 118, 255, 0.08);
        }

        .invoice-radio-dot {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            border: 1px solid #a9b7cc;
            border-radius: 50%;
        }

        .invoice-tab.active .invoice-radio-dot {
            border-color: #0676ff;
            box-shadow: inset 0 0 0 4px #fff;
            background: #0676ff;
        }

        .invoice-tab-icon {
            color: #7c8aa4;
            font-size: 18px;
        }

        .invoice-tab-grid .invoice-tab strong,
        .invoice-tab-grid .invoice-tab small {
            display: block;
        }

        .invoice-tab-grid .invoice-tab strong {
            color: #14235f;
            font-size: 15px;
            font-weight: 900;
        }

        .invoice-tab-grid .invoice-tab small {
            margin-top: 5px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .checkout-field label {
            display: block;
            margin-bottom: 7px;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
        }

        .checkout-field input {
            width: 100%;
            height: 44px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            color: #0f172a;
            outline: none;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .checkout-field input:focus {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.12);
        }

        .checkout-select {
            width: 100%;
            height: 44px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            color: #0f172a;
            outline: none;
            font-weight: 600;
            transition: border-color .2s ease, box-shadow .2s ease;
            cursor: pointer;
        }

        .checkout-select:focus {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.12);
        }

        .doc-type-toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            padding: 12px 24px;
            border-radius: 8px;
            background: #0f172a;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.25);
            transition: opacity 0.3s ease, transform 0.3s ease;
            opacity: 0;
            transform: translateX(-50%) translateY(12px);
            pointer-events: none;
        }

        .doc-type-toast-visible {
            opacity: 1 !important;
            transform: translateX(-50%) translateY(0) !important;
        }

        .phone-input-group {
            display: grid;
            grid-template-columns: minmax(142px, 170px) minmax(0, 1fr);
            gap: 8px;
        }

        .phone-input-group select {
            width: 100%;
            height: 44px;
            padding: 10px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            color: #0f172a;
            outline: none;
            font-weight: 700;
        }

        .phone-input-group select:focus {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.12);
        }

        .free-checkout-message {
            margin-bottom: 8px;
            padding: 24px 28px 30px;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            background: #f0fdf4;
            text-align: center;
        }

        .free-checkout-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            margin-bottom: 18px;
            border-radius: 50%;
            background: #16a34a;
            color: #fff;
            font-size: 28px;
            font-weight: 900;
            box-shadow: 0 0 0 8px #dcfce7;
        }

        .free-checkout-message h3 {
            margin: 0 0 16px;
            color: #065f46;
            font-size: 22px;
            font-weight: 800;
            line-height: 30px;
        }

        .free-checkout-message p {
            margin: 0 auto 10px;
            max-width: 560px;
            color: #334155;
            font-size: 15px;
            font-weight: 400;
            line-height: 24px;
        }

        .free-checkout-message p.free-checkout-main-copy {
            margin-bottom: 12px;
        }

        .free-checkout-message p.free-checkout-action-copy {
            margin-bottom: 18px;
        }

        .free-checkout-message .free-checkout-emphasis {
            font-weight: 800;
        }

        .free-checkout-email-note {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 0 0 20px;
            color: #065f46;
            font-size: 14px;
            font-weight: 500;
            line-height: 22px;
        }

        .free-checkout-email-note i {
            color: #16a34a;
            font-size: 18px;
        }

        .free-checkout-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            margin-bottom: 8px;
            min-height: 44px;
            padding: 12px 28px;
            border: 0;
            border-radius: 8px;
            background: #e11d48;
            color: #fff;
            font-size: 16px;
            font-weight: 800;
            line-height: 24px;
            cursor: pointer;
            box-shadow: 0 10px 22px rgba(225, 29, 72, 0.22);
        }

        .free-checkout-button:hover {
            background: #be123c;
        }

        .account-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .checkout-invoice-card {
            max-width: 860px;
            margin: 0 auto;
            border: 1px solid #dce5ef;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }

        .invoice-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 18px;
            padding: 18px;
            background: #fbfdff;
            border: 1px solid #e5eaf2;
            border-radius: 8px;
        }

        .invoice-form-grid .checkout-field:first-child,
        #factura-panel .checkout-field:nth-child(2) {
            grid-column: span 2;
        }

        .invoice-actions {
            display: block;
            margin-top: 22px;
        }

        .final-info-note {
            display: flex;
            align-items: center;
            gap: 14px;
            min-height: 78px;
            margin-bottom: 26px;
            padding: 18px 22px;
            border: 1px solid #e0e8f4;
            border-radius: 8px;
            background: #f8fbff;
            color: #475569;
            font-size: 14px;
            font-weight: 700;
        }

        .final-info-note .final-section-icon {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            background: #eff6ff;
        }

        #step-final #btn-finalize {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 64px;
            border: 0;
            border-radius: 8px;
            background: #dc2626 !important;
            background-image: none !important;
            font-size: 18px;
            box-shadow: 0 14px 28px rgba(220, 38, 38, 0.22);
        }

        #step-final #btn-finalize b {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 0;
        }

        #step-final #btn-finalize b::before {
            content: none;
        }

        .final-secure-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 18px;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
        }

        .final-secure-note i {
            color: #60a5fa;
        }

        @media (max-width: 1199px) {
            #step-payment .checkout-wide {
                max-width: 860px;
            }

            .security-check-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .checkout-payment-card {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .checkout-page-body {
                padding-top: 64px;
            }

            #step-payment .checkout-wide {
                padding: 22px;
            }

            .payment-checkout-topbar {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .checkout-page-heading h1 {
                font-size: 23px;
            }

            .checkout-page-heading p {
                font-size: 13px;
            }

            .checkout-steps {
                gap: 48px;
                max-width: 420px;
            }

            .checkout-steps::before {
                left: 64px;
                right: 64px;
            }

            .security-check-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .security-check-card {
                min-height: 68px;
            }

            .cart-table-header {
                align-items: flex-start;
                flex-direction: column;
                padding: 20px;
            }

            #step-payment .cart-table-header {
                padding: 0 0 14px;
            }

            #step-payment .checkout-wide.is-free-checkout .cart-table-header h2 {
                font-size: 26px;
                line-height: 34px;
            }

            .free-checkout-message {
                padding: 22px 18px 28px;
            }

            .cart-summary-item {
                grid-template-columns: 136px minmax(0, 1fr);
            }

            .cart-summary-media {
                width: 136px;
            }

            .cart-summary-price {
                grid-column: 1 / -1;
                align-items: flex-start;
                min-width: 0;
            }

            .cart-total-summary {
                justify-content: space-between;
            }

            .cart-product-column {
                min-width: 210px;
            }

            .cart-type-column {
                min-width: 105px;
            }

            .cart-price-column {
                min-width: 78px;
            }

            .cart-action-column {
                min-width: 54px;
            }

            .account-shell {
                padding: 18px;
            }

            .account-header h2 {
                font-size: 24px;
            }

            .account-choice-grid {
                grid-template-columns: 1fr;
                margin: 0 0 16px;
                max-width: none;
            }

            .account-tab {
                justify-content: flex-start;
            }

            .invoice-tab-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .account-choice-grid,
            .account-form-grid,
            .invoice-form-grid {
                grid-template-columns: 1fr;
            }

            .invoice-form-grid .checkout-field:first-child,
            #factura-panel .checkout-field:nth-child(2) {
                grid-column: span 1;
            }

            .mercadopago-shell {
                max-width: 100%;
            }

            .phone-input-group {
                grid-template-columns: 1fr;
            }

            .account-conflict-dialog {
                padding: 28px 20px;
            }

            .phone-required-dialog {
                padding: 28px 20px;
            }

            .account-conflict-actions {
                grid-template-columns: 1fr;
            }

            .pending-paid-dialog {
                padding: 28px 20px;
            }

            .pending-paid-course {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575px) {
            .checkout-container {
                padding-left: 14px;
                padding-right: 14px;
            }

            #step-payment .checkout-wide {
                padding: 18px;
            }

            .payment-brand strong {
                font-size: 16px;
            }

            .checkout-progress-card {
                padding: 10px 12px !important;
            }

            .checkout-steps {
                gap: 24px;
            }

            .checkout-steps::before {
                top: 17px;
                left: 52px;
                right: 52px;
            }

            .checkout-step {
                width: 112px;
            }

            .checkout-step-number {
                width: 36px;
                height: 36px;
                font-size: 15px;
            }

            .security-check-grid {
                grid-template-columns: 1fr;
                margin-top: 8px;
            }

            .security-check-card {
                min-height: 64px;
                padding: 9px 10px;
                border-right: 0;
            }

            .checkout-payment-card {
                padding: 20px !important;
            }

            #step-payment .checkout-payment-card {
                padding: 0 !important;
            }

            .cart-summary-item {
                grid-template-columns: 1fr;
                padding: 12px;
            }

            .cart-summary-media {
                width: 100%;
            }

            .cart-summary-name {
                font-size: 16px;
            }

            .cart-summary-price strong {
                font-size: 24px;
            }

            .cart-total-summary {
                align-items: flex-start;
                flex-direction: column;
                gap: 8px;
            }

            .cart-total-summary strong {
                font-size: 24px;
            }

            #step-payment .checkout-wide.is-free-checkout .cart-table-header h2 {
                font-size: 24px;
                line-height: 31px;
            }

            .free-checkout-button {
                width: 100%;
            }

            .checkout-payment-card::before {
                margin: -20px -20px 18px;
            }

            .checkout-total-row {
                align-items: flex-start !important;
                flex-direction: column;
                gap: 6px;
            }

            .invoice-actions {
                justify-content: stretch;
            }

            .invoice-actions .boton-degradado-courses {
                width: 100%;
            }
        }

        /* ===== Dark Mode ===== */
        body.dark-only .checkout-page-body {
            background: #111827;
        }

        body.dark-only #step-payment .checkout-wide,
        body.dark-only .cart-table-card,
        body.dark-only .checkout-payment-card,
        body.dark-only .account-shell,
        body.dark-only .checkout-progress-card {
            background: #1e293b;
            border-color: #334155;
            color: #e2e8f0;
        }

        body.dark-only .cart-table-header h2,
        body.dark-only .cart-table-header p,
        body.dark-only .secure-payment-copy strong,
        body.dark-only .secure-payment-copy small,
        body.dark-only .checkout-payment-header h2 {
            color: #f1f5f9;
        }

        body.dark-only .cart-table-header p,
        body.dark-only .secure-payment-copy small {
            color: #94a3b8;
        }

        body.dark-only .cart-table-header small {
            background: rgba(220, 38, 38, 0.15);
            color: #fca5a5;
        }

        body.dark-only .cart-summary-item {
            background: #0f172a;
            border-color: #334155;
        }

        body.dark-only .cart-summary-name {
            color: #f1f5f9;
        }

        body.dark-only .cart-summary-name a {
            color: #f1f5f9;
        }

        body.dark-only .cart-summary-meta {
            color: #94a3b8;
        }

        body.dark-only .cart-summary-meta i {
            color: #64748b;
        }

        body.dark-only .cart-summary-price span {
            color: #94a3b8;
        }

        body.dark-only .cart-summary-price strong {
            color: #60a5fa;
        }

        body.dark-only .cart-total-summary {
            background: #0f172a;
            border-color: #334155;
        }

        body.dark-only .cart-total-summary span {
            color: #e2e8f0;
        }

        body.dark_only .cart-total-summary strong,
        body.dark-only .cart-total-summary strong {
            color: #60a5fa;
        }

        body.dark-only .checkout-total-row {
            background: rgba(220, 38, 38, 0.1);
            border-color: rgba(220, 38, 38, 0.3);
            color: #f1f5f9;
        }

        body.dark-only .checkout-total-row strong {
            color: #f87171;
        }

        body.dark-only .checkout-field label {
            color: #cbd5e1;
        }

        body.dark-only .checkout-field input,
        body.dark-only .checkout-select,
        body.dark-only .phone-input-group select {
            background: #0f172a;
            border-color: #475569;
            color: #f1f5f9;
        }

        body.dark-only .checkout-field input::placeholder {
            color: #64748b;
        }

        body.dark-only .checkout-field input:focus,
        body.dark-only .checkout-select:focus,
        body.dark-only .phone-input-group select:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.15);
        }

        body.dark-only .secure-payment-note {
            color: #94a3b8;
        }

        body.dark-only .secure-payment-note a {
            color: #60a5fa;
        }

        body.dark-only .secure-payment-note .secure-payment-icon svg path {
            fill: #60a5fa;
        }

        body.dark-only .checkout-step-text {
            color: #94a3b8;
        }

        body.dark-only .checkout-step.active .checkout-step-text {
            color: #f87171;
        }

        body.dark-only .checkout-steps::before {
            background: #334155;
        }

        body.dark-only .checkout-step-number {
            background: #1e293b;
            border-color: #1e293b;
            color: #e2e8f0;
            box-shadow: 0 0 0 2px #475569;
        }

        body.dark-only .checkout-step.active .checkout-step-number {
            background: #dc2626;
            border-color: #dc2626;
            color: #fff;
            box-shadow: 0 0 0 2px #dc2626;
        }

        body.dark-only .checkout-step.done .checkout-step-number {
            background: #1e293b;
            border-color: #1e293b;
            color: #e2e8f0;
            box-shadow: 0 0 0 2px #475569;
        }

        body.dark-only .payment-brand {
            color: #e2e8f0;
        }

        body.dark-only .payment-brand strong {
            color: #f1f5f9;
        }

        body.dark-only .payment-brand small {
            color: #94a3b8;
        }

        body.dark-only .security-check-grid {
            background: #0f172a;
            border-color: #334155;
        }

        body.dark-only .security-check-card {
            border-color: #334155;
            color: #e2e8f0;
        }

        body.dark-only .security-check-card strong {
            color: #f1f5f9;
        }

        body.dark-only .security-check-card small {
            color: #94a3b8;
        }

        body.dark-only .security-check-card-static:hover {
            color: #e2e8f0;
            border-color: #475569;
        }

        body.dark-only .boton-degradado-trash {
            background: rgba(220, 38, 38, 0.15);
            color: #f87171;
        }

        body.dark-only .boton-degradado-trash:hover {
            background: rgba(220, 38, 38, 0.25);
        }

        /* MercadoPago Brick dark mode overrides */
        body.dark-only #cardPaymentBrick_container {
            background: #1e293b;
            border-radius: 8px;
        }

        body.dark-only #cardPaymentBrick_container .mp-checkout-container,
        body.dark-only #cardPaymentBrick_container .mp-card-form,
        body.dark-only #cardPaymentBrick_container .mp-form,
        body.dark-only #cardPaymentBrick_container .mp-brick-container {
            background: #1e293b !important;
            color: #e2e8f0 !important;
        }

        body.dark-only #cardPaymentBrick_container input,
        body.dark-only #cardPaymentBrick_container select {
            background: #0f172a !important;
            border-color: #475569 !important;
            color: #f1f5f9 !important;
        }

        body.dark-only #cardPaymentBrick_container label,
        body.dark-only #cardPaymentBrick_container .mp-field-label {
            color: #cbd5e1 !important;
        }

        body.dark-only #cardPaymentBrick_container .mp-input-wrapper {
            background: #0f172a !important;
            border-color: #475569 !important;
        }

        body.dark-only #cardPaymentBrick_container .mp-input {
            background: #0f172a !important;
            color: #f1f5f9 !important;
        }

        /* MercadoPago iframe / shadow DOM dark overrides */
        body.dark-only #cardPaymentBrick_container iframe {
            background: #1e293b !important;
        }

        body.dark-only #cardPaymentBrick_container [class*="form"], 
        body.dark-only #cardPaymentBrick_container [class*="field"],
        body.dark-only #cardPaymentBrick_container [class*="input"] {
            background-color: #0f172a !important;
            border-color: #475569 !important;
            color: #f1f5f9 !important;
        }

        body.dark-only #cardPaymentBrick_container [class*="label"],
        body.dark-only #cardPaymentBrick_container [class*="title"],
        body.dark-only #cardPaymentBrick_container [class*="text"] {
            color: #e2e8f0 !important;
        }

        body.dark-only #cardPaymentBrick_container [class*="secondary"],
        body.dark-only #cardPaymentBrick_container [class*="subtitle"] {
            color: #94a3b8 !important;
        }

        body.dark-only #cardPaymentBrick_container [class*="card"],
        body.dark-only #cardPaymentBrick_container [class*="section"],
        body.dark-only #cardPaymentBrick_container [class*="group"] {
            background-color: #1e293b !important;
        }

        body.dark-only #cardPaymentBrick_container [class*="border"] {
            border-color: #475569 !important;
        }

        body.dark-only #cardPaymentBrick_container [class*="divider"] {
            border-color: #334155 !important;
        }

        /* Account & Invoice dark mode */
        body.dark-only .account-tab {
            background: #0f172a;
            border-color: #334155;
            color: #60a5fa;
        }

        body.dark-only .account-tab.active {
            background: rgba(96, 165, 250, 0.1);
            border-color: #60a5fa;
        }

        body.dark-only .account-tab strong {
            color: #60a5fa;
        }

        body.dark-only .account-form-grid {
            background: #0f172a;
            border-color: #334155;
        }

        body.dark-only .invoice-tab {
            background: #0f172a;
            border-color: #334155;
            color: #e2e8f0;
        }

        body.dark-only .invoice-tab.active {
            background: rgba(96, 165, 250, 0.1);
            border-color: #60a5fa;
        }

        body.dark-only .invoice-tab-grid .invoice-tab {
            background: #0f172a;
            border-color: #334155;
            color: #e2e8f0;
        }

        body.dark-only .invoice-tab-grid .invoice-tab.active {
            background: rgba(96, 165, 250, 0.1);
            border-color: #60a5fa;
        }

        body.dark-only .invoice-tab-grid .invoice-tab strong {
            color: #f1f5f9;
        }

        body.dark-only .invoice-tab-grid .invoice-tab small {
            color: #94a3b8;
        }

        body.dark-only .invoice-radio-dot {
            border-color: #64748b;
        }

        body.dark-only .invoice-tab.active .invoice-radio-dot {
            background: #60a5fa;
            border-color: #60a5fa;
        }

        body.dark-only .invoice-form-grid {
            background: #0f172a;
            border-color: #334155;
        }

        body.dark-only .final-section-heading h3 {
            color: #f1f5f9;
        }

        body.dark-only .final-section-heading p {
            color: #94a3b8;
        }

        body.dark-only .final-section-icon {
            color: #60a5fa;
        }

        body.dark-only .final-info-note {
            background: #0f172a;
            border-color: #334155;
            color: #94a3b8;
        }

        body.dark-only .final-info-note .final-section-icon {
            background: rgba(96, 165, 250, 0.1);
            border-color: #334155;
        }

        body.dark-only .final-success-card {
            background: rgba(22, 163, 74, 0.1);
            border-color: rgba(22, 163, 74, 0.3);
        }

        body.dark-only .final-success-card strong {
            color: #4ade80;
        }

        body.dark-only .final-success-card small {
            color: #86efac;
        }

        body.dark-only .final-secure-note {
            color: #64748b;
        }

        body.dark-only .account-header h2 {
            color: #f1f5f9;
        }

        body.dark-only .account-header p {
            color: #94a3b8;
        }

        body.dark-only .final-step-kicker {
            color: #60a5fa;
        }

        /* Modals dark mode */
        body.dark-only .payment-warning-dialog,
        body.dark-only .phone-required-dialog,
        body.dark-only .account-conflict-dialog,
        body.dark-only .pending-paid-dialog {
            background: #1e293b;
            color: #e2e8f0;
        }

        body.dark-only .payment-warning-dialog h3,
        body.dark-only .phone-required-dialog h3,
        body.dark-only .account-conflict-dialog h3,
        body.dark-only .pending-paid-dialog h3 {
            color: #f1f5f9;
        }

        body.dark-only .payment-warning-dialog p,
        body.dark-only .phone-required-dialog p,
        body.dark-only .account-conflict-dialog p,
        body.dark-only .pending-paid-dialog p {
            color: #cbd5e1;
        }

        body.dark-only .phone-required-dialog {
            border-color: #334155;
            border-top-color: #3b82f6;
        }

        body.dark-only .account-conflict-dialog {
            border-color: #334155;
            border-top-color: #dc2626;
        }

        body.dark-only .pending-paid-dialog {
            border-color: #334155;
            border-top-color: #16a34a;
        }

        body.dark-only .phone-required-close,
        body.dark-only .account-conflict-close,
        body.dark-only .pending-paid-close {
            background: #334155;
            color: #e2e8f0;
        }

        body.dark-only .account-conflict-secondary {
            background: #334155;
            border-color: #475569;
            color: #e2e8f0;
        }

        body.dark-only .account-conflict-support a {
            background: rgba(220, 38, 38, 0.1);
            border-color: rgba(220, 38, 38, 0.3);
            color: #f87171;
        }

        body.dark-only .pending-paid-course {
            background: #0f172a;
            border-color: #334155;
        }

        body.dark-only .pending-paid-course strong {
            color: #f1f5f9;
        }

        body.dark-only .pending-paid-course small {
            color: #94a3b8;
        }

        body.dark-only .phone-required-icon {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
        }

        body.dark-only .phone-required-kicker {
            color: #60a5fa;
        }

        body.dark-only .account-conflict-icon {
            background: #dc2626;
            box-shadow: 0 0 0 9px rgba(220, 38, 38, 0.2);
        }

        body.dark-only .account-conflict-kicker {
            color: #4ade80;
        }

        body.dark-only .pending-paid-icon {
            background: #16a34a;
            box-shadow: 0 0 0 9px rgba(22, 163, 74, 0.2);
        }

        body.dark-only .pending-paid-kicker {
            color: #4ade80;
        }

        body.dark-only .payment-brand-logo {
            filter: brightness(0.9);
        }

        body.dark-only .mp-loading-message {
            color: #94a3b8;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const csrfToken = "{{ csrf_token() }}";
        const routes = {
            items: "{{ route('onlineshop_get_item_carrito') }}",
            preference: "{{ route('web_cart_preference') }}",
            payment: "{{ route('web_cart_process_payment') }}",
            finalize: "{{ route('web_cart_finalize') }}",
            abandoned: "{{ route('web_cart_abandoned') }}",
            searchPerson: "{{ route('sales_search_person_apies') }}",
            description: "{{ url('curso-descripcion') }}",
            slug: "{{ url('curso') }}"
        };
        const pendingPaidCartKey = 'pending_paid_cart_checkout';

        let cartIds = [];
        let cartItems = [];
        let checkoutTotal = 0;
        let preferenceId = null;
        let mercadoPublicKey = null;
        let paidSaleId = null;
        let paymentInitializing = false;
        let paymentVersion = 0;
        let freeCheckout = false;
        let lastCardholderName = '';
        let phoneGuardAttached = false;
        let accountLookupTimer = null;
        let invoiceLookupTimer = null;
        let lastAccountLookupKey = '';
        let lastInvoiceLookupKey = '';

        document.addEventListener('DOMContentLoaded', () => {
            loadCart();
            document.getElementById('btn-finalize').addEventListener('click', finalizeCheckout);
            attachInvoiceLookupEvents();

            document.querySelectorAll('.account-tab').forEach(button => {
                button.addEventListener('click', () => selectAccountMode(button.dataset.accountMode));
            });

            document.querySelectorAll('.invoice-tab').forEach(button => {
                button.addEventListener('click', () => selectInvoiceType(button.dataset.invoiceType));
            });

            const docTypeSelect = document.getElementById('create_document_type');
            if (docTypeSelect) {
                docTypeSelect.addEventListener('change', () => {
                    const selectedText = docTypeSelect.options[docTypeSelect.selectedIndex].text;
                    showDocTypeToast('Tipo de documento: ' + selectedText);
                });
            }
        });

        function loadCart() {
            const carrito = JSON.parse(localStorage.getItem('carrito')) || [];
            cartIds = carrito.map(item => parseInt(item.id)).filter(Boolean);

            if (!cartIds.length) {
                renderEmptyCart();
                return;
            }

            fetch(routes.items, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ ids: cartIds })
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.items) throw new Error(data.error || 'No se pudo cargar el carrito.');
                    cartItems = data.items;
                    checkoutTotal = cartItems.reduce((sum, item) => sum + Number(item.price), 0);
                    renderCart();
                    resetPaymentState();
                    if (checkoutTotal <= 0) {
                        startFreeCheckout();
                    } else if (restorePendingPaidCheckout()) {
                        return;
                    } else {
                        startCardPayment();
                    }
                })
                .catch(error => showAlert(error.message));
        }

        function renderCart() {
            const tbody = document.getElementById('cart');
            tbody.innerHTML = '';

            cartItems.forEach(item => {
                const row = document.createElement('tr');
                row.className = 'border-y border-transparent border-b-slate-200';
                row.id = `${item.id}_pc`;
                row.innerHTML = `
                    <td colspan="4" class="cart-summary-cell">
                        <div class="cart-summary-item">
                            <div class="cart-summary-media">
                                <img src="${item.image}" alt="curso">
                            </div>
                            <div class="cart-summary-content">
                                <strong class="cart-summary-name">
                                    <a href="${item.url_slug && item.landing_published == 1 ? routes.slug + '/' + item.url_slug : routes.description + '/' + item.id}" target="_blank">${item.name}</a>
                                </strong>
                                <div class="cart-summary-meta">
                                    <span><i class="fa fa-calendar" aria-hidden="true"></i> Modalidad: ${item.additional || 'Online'}</span>
                                </div>
                            </div>
                            <div class="cart-summary-price">
                                <span>Inversi&oacute;n</span>
                                <strong>${priceLabel(item.price)}</strong>
                                <button class="boton-degradado-trash" type="button" onclick="removeProduct(${item.id})" aria-label="Quitar curso">
                                    <i class="fa fa-trash" aria-hidden="true" style="font-size: 16px;"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });

            const totalRow = document.createElement('tr');
            totalRow.className = 'cart-total-summary-row';
            totalRow.innerHTML = `
                <td colspan="4">
                    <div class="cart-total-summary">
                        <span>Total a pagar</span>
                        <strong>S/ ${money(checkoutTotal)}</strong>
                    </div>
                </td>
            `;
            tbody.appendChild(totalRow);

            document.getElementById('totalid').textContent = `S/ ${money(checkoutTotal)}`;
            document.getElementById('total_productos').textContent = `${cartItems.length} ${cartItems.length === 1 ? 'curso seleccionado' : 'cursos seleccionados'}`;
            updateFreeCheckoutView();
        }

        function renderEmptyCart() {
            resetPaymentState();
            document.getElementById('cart').innerHTML = '<tr><td colspan="4" class="px-4 py-5 text-center">No has elegido ningun curso.</td></tr>';
            document.getElementById('totalid').textContent = 'S/ 0.00';
            document.getElementById('total_productos').textContent = 'Sin cursos seleccionados';
            parkPaymentPhoneField();
            document.getElementById('cardPaymentBrick_container').innerHTML = '<div class="mp-loading-message p-4 text-center">Agrega cursos para cargar el pago.</div>';
            updateFreeCheckoutView();
        }

        function removeProduct(id) {
            if (paidSaleId) {
                showAlert('El pago ya fue aprobado. Continua con cuenta y comprobante para finalizar.');
                return;
            }

            const carrito = (JSON.parse(localStorage.getItem('carrito')) || []).filter(item => parseInt(item.id) !== id);
            localStorage.setItem('carrito', JSON.stringify(carrito));
            loadCart();
        }

        function startCardPayment() {
            hideAlert();
            if (paymentInitializing) {
                return;
            }

            if (!cartIds.length) {
                showAlert('No has elegido ningun curso.');
                return;
            }

            paymentInitializing = true;
            const currentPaymentVersion = paymentVersion;
            parkPaymentPhoneField();
            document.getElementById('cardPaymentBrick_container').innerHTML = '<div class="mp-loading-message p-4 text-center">Preparando pago seguro...</div>';

            requestJson(routes.preference, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ item_id: cartIds })
            }, 20000)
                .then(async (data) => {
                    if (currentPaymentVersion !== paymentVersion) {
                        return;
                    }

                    preferenceId = data.preference;
                    mercadoPublicKey = data.public_key;
                    checkoutTotal = Number(data.total);
                    cartItems = data.products;
                    document.getElementById('totalid').textContent = `S/ ${money(checkoutTotal)}`;
                    await renderMercadoPago(currentPaymentVersion);
                })
                .catch(error => {
                    if (currentPaymentVersion === paymentVersion) {
                        showAlert(error.message);
                    }
                })
                .finally(() => {
                    if (currentPaymentVersion !== paymentVersion) {
                        return;
                    }

                    paymentInitializing = false;
                });
        }

        function startFreeCheckout() {
            hideAlert();
            freeCheckout = true;
            paidSaleId = null;
            preferenceId = null;
            mercadoPublicKey = null;
            paymentInitializing = false;
            unmountMercadoPago();
            parkPaymentPhoneField();
            document.getElementById('cardPaymentBrick_container').innerHTML = `
                <div class="free-checkout-message">
                    <div class="free-checkout-icon"><i class="fa fa-shopping-cart" aria-hidden="true"></i></div>
                    <h3>¡Estás a un paso de registrarte!</h3>
                    <p class="free-checkout-main-copy">Has agregado tu curso al carrito correctamente.</p>
                    <p class="free-checkout-action-copy">Para continuar con tu registro gratuito, haz clic en el botón <span class="free-checkout-emphasis">"Confirmar mi acceso"</span> y crea tu cuenta en nuestra plataforma.</p>
                    <div class="free-checkout-email-note">
                        <i class="fa fa-envelope-o" aria-hidden="true"></i>
                        <span>Te enviaremos los accesos a tu correo electrónico.</span>
                    </div>
                    <button type="button" class="free-checkout-button" onclick="continueFreeCheckout()">
                        <span>Confirmar mi acceso</span>
                        <i class="fa fa-angle-right" aria-hidden="true"></i>
                    </button>
                </div>
            `;
            updateFreeCheckoutView();
        }

        function continueFreeCheckout() {
            fillPayerData({});
            showStep('final');
        }

        function injectDarkStylesIntoShadowDOM() {
            if (!document.body.classList.contains('dark-only')) return;

            const darkCSS = `
                :host, :root { background-color: #1e293b !important; color: #e2e8f0 !important; }
                .mp-header-title, .mp-title, [class*="header-title"], [class*="HeaderTitle"] {
                    color: #ffffff !important; font-weight: 700 !important;
                }
                .mp-brick-container, .mp-checkout-container, .mp-card-form, .mp-form {
                    background-color: #1e293b !important; color: #e2e8f0 !important;
                }
                .mp-field-label, label, [class*="label"], [class*="Label"] {
                    color: #cbd5e1 !important;
                }
                .mp-input, input, select, [class*="input"], [class*="Input"] {
                    background-color: #0f172a !important; color: #f1f5f9 !important;
                    border-color: #475569 !important;
                }
                .mp-input:focus, input:focus, select:focus {
                    border-color: #60a5fa !important;
                    box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.25) !important;
                }
                .mp-input::placeholder, input::placeholder { color: #64748b !important; }
                [class*="secondary"], [class*="Secondary"], [class*="subtitle"], [class*="Subtitle"] {
                    color: #94a3b8 !important;
                }
                [class*="divider"], [class*="Divider"], hr { border-color: #334155 !important; }
                [class*="card"], [class*="Card"], [class*="section"], [class*="Section"] {
                    background-color: #1e293b !important;
                }
                [class*="border"], [class*="Border"] { border-color: #475569 !important; }
                [class*="row"], [class*="Row"] { border-color: #334155 !important; }
                [class*="option"], [class*="Option"] {
                    background-color: #0f172a !important; border-color: #475569 !important;
                }
                [class*="option"]:hover, [class*="Option"]:hover {
                    background-color: #1e293b !important;
                }
                [class*="text"], [class*="Text"], [class*="body"], [class*="Body"] {
                    color: #e2e8f0 !important;
                }
                [class*="helper"], [class*="Helper"], [class*="error"], [class*="Error"] {
                    color: #f87171 !important;
                }
                [class*="success"], [class*="Success"] { color: #4ade80 !important; }
                button[type="submit"], [class*="button"], [class*="Button"],
                [class*="submit"], [class*="Submit"] {
                    background-color: #dc2626 !important; color: #ffffff !important;
                    border-color: #dc2626 !important;
                }
                button[type="submit"]:hover, [class*="button"]:hover, [class*="Button"]:hover {
                    background-color: #b91c1c !important;
                }
            `;

            const container = document.getElementById('cardPaymentBrick_container');
            if (!container) return;

            function injectIntoShadowRoots(root) {
                root.querySelectorAll('*').forEach(el => {
                    if (el.shadowRoot) {
                        let styleEl = el.shadowRoot.querySelector('.mp-dark-inject');
                        if (!styleEl) {
                            styleEl = document.createElement('style');
                            styleEl.className = 'mp-dark-inject';
                            styleEl.textContent = darkCSS;
                            el.shadowRoot.prepend(styleEl);
                        }
                        injectIntoShadowRoots(el.shadowRoot);
                    }
                });
            }

            injectIntoShadowRoots(container);
        }

        async function renderMercadoPago(currentPaymentVersion) {
            const container = document.getElementById('cardPaymentBrick_container');
            parkPaymentPhoneField();
            container.innerHTML = '<div class="mp-loading-message p-4 text-center">Cargando formulario seguro de MercadoPago...</div>';

            if (!window.MercadoPago) {
                throw new Error('No se pudo cargar el SDK de MercadoPago. Revisa la conexion o bloqueadores del navegador.');
            }

            try {
                const mp = new MercadoPago(mercadoPublicKey, { locale: 'es-PE' });
                const bricksBuilder = mp.bricks();

                const isDarkMode = document.body.classList.contains('dark-only') || document.body.classList.contains('dark');
                const brickTheme = isDarkMode ? 'dark' : 'bootstrap';

                const brickPromise = bricksBuilder.create('cardPayment', 'cardPaymentBrick_container', {
                    initialization: {
                        preferenceId,
                        amount: checkoutTotal,
                    },
                    customization: {
                        visual: {
                            style: {
                                theme: brickTheme,
                                customVariables: {
                                    baseColor: '#dc2626',
                                    baseColorFirstVariant: '#b91c1c',
                                    baseColorSecondVariant: '#991b1b',
                                    buttonTextColor: '#ffffff',
                                    outlinePrimaryColor: '#dc2626',
                                }
                            }
                        },
                        paymentMethods: { maxInstallments: {{ config('services.mercadopago.max_installments') }} }
                    },
                    callbacks: {
                        onReady: () => {
                            container.querySelectorAll('.mp-loading-message').forEach(message => message.remove());
                            parkPaymentPhoneField();
                            attachMercadoPagoPhoneGuard();
                            hideAlert();
                            // Inyectar estilos dark en shadow DOM de MercadoPago
                            injectDarkStylesIntoShadowDOM();
                        },
                        onSubmit: (cardFormData) => {
                            hideAlert();
                            cardFormData = normalizeCardFormData(cardFormData);
                            lastCardholderName = getCardholderName(cardFormData);

                            return requestJson(routes.payment, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: JSON.stringify({
                                    item_id: cartIds,
                                    cardFormData: Object.assign({}, cardFormData, JSON.parse(localStorage.getItem('traffic_tracking') || '{}'))
                                })
                            }, 30000)
                                .then((data) => {
                                    paidSaleId = data.sale_id;
                                    const payer = data.payer || {};
                                    if (!payer.names && lastCardholderName) {
                                        payer.names = lastCardholderName;
                                    }
                                    savePendingPaidCheckout(data.sale_id, payer);
                                    fillPayerData(payer);
                                    showStep('final');
                                })
                                .catch(error => {
                                    const paymentWarning = paymentWarningFromMercadoPagoError(error.message);
                                    if (paymentWarning) {
                                        showPaymentWarningModal(paymentWarning);
                                    } else {
                                        showAlert(error.message);
                                    }
                                    throw error;
                                });
                        },
                        onError: (error) => {
                            console.log(error);
                            const paymentWarning = paymentWarningFromMercadoPagoError(error);
                            if (paymentWarning) {
                                showPaymentWarningModal(paymentWarning);
                            } else {
                                showAlert('MercadoPago no pudo renderizar el formulario. Intenta recargar la pagina.');
                            }
                        },
                    },
                });

                const controller = await withTimeout(
                    brickPromise,
                    15000,
                    'MercadoPago esta tardando demasiado en cargar. Intenta nuevamente o recarga la pagina.'
                );

                if (currentPaymentVersion !== paymentVersion) {
                    if (controller && typeof controller.unmount === 'function') {
                        controller.unmount();
                    }
                    return;
                }

                window.cardPaymentBrickController = controller;
            } catch (error) {
                container.innerHTML = '';
                throw error;
            }
        }

        function withTimeout(promise, milliseconds, message) {
            let timer;
            const timeout = new Promise((_, reject) => {
                timer = setTimeout(() => reject(new Error(message)), milliseconds);
            });

            return Promise.race([promise, timeout]).finally(() => clearTimeout(timer));
        }

        function normalizeCardFormData(cardFormData) {
            const payer = cardFormData.payer || {};
            const phoneState = getPaymentPhoneState();
            const cardholderName = getCardholderName(cardFormData);
            payer.email = (payer.email || '').trim().toLowerCase();

            if (!isValidEmail(payer.email)) {
                throw new Error('Ingresa un correo válido en el formulario de MercadoPago.');
            }

            if (!phoneState.isComplete) {
                notifyPaymentPhoneRequired(phoneState, true);
                throw new Error('Ingresa tu código de país y número de teléfono.');
            }

            payer.phone = {
                ...(payer.phone || {}),
                area_code: phoneState.areaCode,
                number: phoneState.phone
            };

            if (cardholderName) {
                const nameParts = cardholderName.split(/\s+/);
                payer.first_name = payer.first_name || nameParts.shift() || '';
                payer.last_name = payer.last_name || nameParts.join(' ');
                cardFormData.cardholderName = cardholderName;
            }

            cardFormData.payer = payer;
            return cardFormData;
        }

        function getCardholderName(cardFormData) {
            const possibleNames = [
                cardFormData.cardholderName,
                cardFormData.cardholder_name,
                cardFormData.cardholder?.name,
                cardFormData.cardholder?.cardholderName,
                cardFormData.payer?.names,
                `${cardFormData.payer?.first_name || ''} ${cardFormData.payer?.last_name || ''}`,
                getCardholderNameFromBrick()
            ];

            return (possibleNames.find(name => String(name || '').trim()) || '').trim();
        }

        function getCardholderNameFromBrick() {
            const container = document.getElementById('cardPaymentBrick_container');
            if (!container) {
                return '';
            }

            const inputs = Array.from(container.querySelectorAll('input'));
            const holderInput = inputs.find(input => {
                const attributes = [
                    input.id,
                    input.name,
                    input.getAttribute('aria-label'),
                    input.getAttribute('placeholder'),
                    input.closest('label')?.textContent,
                    input.parentElement?.textContent
                ].join(' ').toLowerCase();

                return attributes.includes('titular') ||
                    attributes.includes('cardholder') ||
                    attributes.includes('nombre') ||
                    attributes.includes('name');
            });

            return holderInput ? holderInput.value.trim() : '';
        }

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        function fillPayerData(payer) {
            const names = payer.names || '';
            const email = payer.email || '';
            const dni = payer.document_number || '';

            document.getElementById('create_names').value = names;
            document.getElementById('create_email').value = email;
            document.getElementById('create_dni').value = dni;
            document.getElementById('login_email').value = email;
        }

        function selectAccountMode(mode) {
            document.getElementById('account_mode').value = mode;
            document.querySelectorAll('.account-tab').forEach(button => button.classList.toggle('active', button.dataset.accountMode === mode));
            document.getElementById('create-account-panel').classList.toggle('d-none', mode !== 'create');
            document.getElementById('login-account-panel').classList.toggle('d-none', mode !== 'login');
        }

        function selectInvoiceType(type) {
            document.getElementById('invoice_type').value = type;
            document.querySelectorAll('.invoice-tab').forEach(button => button.classList.toggle('active', button.dataset.invoiceType === type));
            document.getElementById('boleta-panel').classList.toggle('d-none', type !== 'boleta');
            document.getElementById('factura-panel').classList.toggle('d-none', type !== 'factura');

            if (type === 'boleta') {
                lookupInvoicePerson('boleta', value('invoice_dni'), false);
            } else {
                document.getElementById('invoice_ruc').focus();
                lookupInvoicePerson('factura', value('invoice_ruc'), false);
            }
        }

        function updateCreateDniMaxLength() {
            const el = document.getElementById("create_document_type");
            const dni = document.getElementById("create_dni");
            if (!el || !dni) return;
            const val = parseInt(el.value, 10);
            if (val === 1) dni.maxLength = 8;
            else if (val === 6) dni.maxLength = 11;
            else dni.maxLength = 20;
            dni.value = dni.value.slice(0, dni.maxLength);
        }
        function attachInvoiceLookupEvents() {
            const createDocType = document.getElementById("create_document_type");
            const createDni = document.getElementById("create_dni");
            const invoiceDni = document.getElementById("invoice_dni");
            const invoiceRuc = document.getElementById("invoice_ruc");

            createDni.maxLength = 20;
            createDni.addEventListener("input", () => {
                queueAccountLookup(createDni.value);
            });
            createDocType.addEventListener("change", () => {
                updateCreateDniMaxLength();
                const sel = createDocType.options[createDocType.selectedIndex];
                showDocTypeToast("Documento: " + sel.text);
            });

            invoiceDni.maxLength = 20;
            invoiceDni.addEventListener("input", () => {
                queueInvoiceLookup("boleta", invoiceDni.value);
            });

            invoiceRuc.maxLength = 11;
            invoiceRuc.addEventListener("input", () => {
                invoiceRuc.value = onlyDigits(invoiceRuc.value).slice(0, 11);
                queueInvoiceLookup("factura", invoiceRuc.value);
            });

            updateCreateDniMaxLength();
        }
        function queueAccountLookup(number) {
            clearTimeout(accountLookupTimer);
            accountLookupTimer = setTimeout(() => lookupAccountPerson(number, true), 450);
        }

        function queueInvoiceLookup(type, number) {
            clearTimeout(invoiceLookupTimer);
            invoiceLookupTimer = setTimeout(() => lookupInvoicePerson(type, number, true), 450);
        }

        function lookupAccountPerson(number, showErrors = true) {
            const docType = document.getElementById("create_document_type").value || "1";
            const lookupKey = `account:${docType}:${number}`;

            if (!number.trim() || number.trim().length < 3 || lookupKey === lastAccountLookupKey) {
                return;
            }

            lastAccountLookupKey = lookupKey;

            requestJson(routes.searchPerson, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify({
                    document_type: docType,
                    number: number
                })
            }, 15000)
                .then((data) => {
                    if (!data.success) {
                        throw new Error(data.error || "No se encontraron datos para el documento ingresado.");
                    }
                    fillAccountPerson(data.person || {}, number);
                })
                .catch(error => {
                    lastAccountLookupKey = "";
                    if (showErrors) {
                        showAlert(error.message);
                    }
                });
        }
        function lookupInvoicePerson(type, number, showErrors = true) {
            const cleanNumber = onlyDigits(number);
            const expectedLength = type === 'factura' ? 11 : 8;
            const lookupKey = `${type}:${cleanNumber}`;

            if (cleanNumber.length !== expectedLength || lookupKey === lastInvoiceLookupKey) {
                return;
            }

            lastInvoiceLookupKey = lookupKey;

            requestJson(routes.searchPerson, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    document_type: type === 'factura' ? 6 : 1,
                    number: cleanNumber
                })
            }, 15000)
                .then((data) => {
                    if (!data.success) {
                        throw new Error(data.error || 'No se encontraron datos para el documento ingresado.');
                    }

                    fillInvoicePerson(type, data.person || {}, cleanNumber);
                })
                .catch(error => {
                    lastInvoiceLookupKey = '';
                    if (showErrors) {
                        showAlert(error.message);
                    }
                });
        }

        function fillInvoicePerson(type, person, number) {
            hideAlert();

            if (type === 'factura') {
                document.getElementById('invoice_ruc').value = person.numero_documento || number;
                document.getElementById('invoice_business_name').value = person.razon_social || '';
                document.getElementById('invoice_address').value = person.direccion === '-' ? '' : (person.direccion || '');
                return;
            }

            const fullName = person.razon_social || [
                person.names,
                person.father_lastname,
                person.mother_lastname
            ].filter(Boolean).join(' ');
            const dni = person.document_number || number;

            document.getElementById('invoice_dni').value = dni;
            document.getElementById('invoice_name').value = fullName;
        }

        function fillAccountPerson(person, number) {
            hideAlert();

            const fullName = person.razon_social || [
                person.names,
                person.father_lastname,
                person.mother_lastname
            ].filter(Boolean).join(" ");

            document.getElementById("create_dni").value = person.document_number || number;
            document.getElementById("create_names").value = fullName;

            if (person.document_type_id) {
                document.getElementById("create_document_type").value = person.document_type_id;
                updateCreateDniMaxLength();

                if (person.document_type_id == 1 || person.document_type_id == 6) {
                    const docSelect = document.getElementById("create_document_type");
                    const docText = docSelect.options[docSelect.selectedIndex].text;
                    showDocTypeToast("Documento autocompletado: " + docText);
                }
            }
        }
        function onlyDigits(value) {
            return String(value || '').replace(/\D/g, '');
        }

        function finalizeCheckout() {
            hideAlert();
            if (!freeCheckout && !paidSaleId) {
                showAlert('Primero debes completar el pago con tarjeta.');
                return;
            }

            setBusy("btn-finalize", true);
            const accountMode = document.getElementById("account_mode").value;
            const payload = {
                account_mode: accountMode,
                sale_id: freeCheckout ? null : paidSaleId,
                item_id: freeCheckout ? cartIds : undefined,
                email: accountMode === "login" ? value("login_email") : value("create_email"),
                password: accountMode === "login" ? value("login_password") : value("create_password"),
                names: value("create_names"),
                create_document_type: accountMode === "create" ? parseInt(value("create_document_type"), 10) : undefined,
                number: value("create_dni"),
                invoice_type: value("invoice_type"),
                invoice_name: value("invoice_name"),
                invoice_dni: value("invoice_dni"),
                invoice_email: value("invoice_email"),
                invoice_ruc: value("invoice_ruc"),
                invoice_business_name: value("invoice_business_name"),
                invoice_address: value("invoice_address"),
                ...JSON.parse(localStorage.getItem('traffic_tracking') || '{}')
            };

            requestJson(routes.finalize, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            }, 30000)
                .then((data) => {
                    // Refresh CSRF token after login/register
                    refreshCsrfToken();

                    clearPendingPaidCheckout();
                    localStorage.removeItem('carrito');
                    window.location.href = data.url;
                })
                .catch(error => {
                    if (error.conflictType === 'dni' || error.conflictType === 'email') {
                        showAccountConflictModal(error.message, error.conflictType);
                        return;
                    }

                    showAlert(error.message);
                })
                .finally(() => setBusy('btn-finalize', false));
        }

        function showStep(step) {
            document.querySelectorAll('.checkout-panel').forEach(panel => panel.classList.add('d-none'));
            document.getElementById(`step-${step}`).classList.remove('d-none');

            const order = ['payment', 'final'];
            const index = order.indexOf(step);
            document.querySelectorAll('.checkout-step').forEach(button => {
                const buttonIndex = order.indexOf(button.dataset.stepLabel);
                button.classList.toggle('active', buttonIndex === index);
                button.classList.toggle('done', buttonIndex < index);
            });
        }

        function showAlert(message) {
            const alert = document.getElementById('checkout-alert');
            alert.textContent = message;
            alert.classList.remove('d-none');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function hideAlert() {
            const alert = document.getElementById('checkout-alert');
            alert.textContent = '';
            alert.classList.add('d-none');
        }

        function showDocTypeToast(message) {
            const toast = document.getElementById("doc-type-toast");
            if (!toast) return;
            toast.textContent = message;
            toast.classList.remove("d-none");
            toast.classList.add("doc-type-toast-visible");
            clearTimeout(toast._hideTimer);
            toast._hideTimer = setTimeout(() => {
                toast.classList.remove("doc-type-toast-visible");
                toast.classList.add("d-none");
            }, 2200);
        }
        function showAccountConflictModal(message, conflictType) {
            const title = conflictType === 'email'
                ? 'Este email ya esta registrado'
                : 'Este número de identificación ya esta registrado';

            document.getElementById('account-conflict-title').textContent = title;
            document.getElementById('account-conflict-message').textContent = message;
            document.getElementById('account-conflict-modal').classList.remove('hidden');
        }

        function hideAccountConflictModal() {
            document.getElementById('account-conflict-modal').classList.add('hidden');
            document.getElementById('account-conflict-message').textContent = '';
        }

        function goToLoginFromConflictModal() {
            hideAccountConflictModal();
            selectAccountMode('login');
            document.getElementById('login_email').focus();
        }

        function showPaymentWarningModal(message) {
            document.getElementById('payment-warning-message').textContent = message;
            document.getElementById('payment-warning-modal').classList.remove('hidden');
        }

        function hidePaymentWarningModal() {
            document.getElementById('payment-warning-modal').classList.add('hidden');
            document.getElementById('payment-warning-message').textContent = '';
        }

        function attachMercadoPagoPhoneGuard() {
            const container = document.getElementById('cardPaymentBrick_container');

            if (phoneGuardAttached || !container) {
                return;
            }

            container.addEventListener('click', (event) => {
                if (freeCheckout) {
                    return;
                }

                const submitControl = event.target?.closest?.('button, [role="button"], input[type="submit"]');

                if (!submitControl) {
                    return;
                }

                const phoneState = getPaymentPhoneState();

                if (phoneState.isComplete) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                notifyPaymentPhoneRequired(phoneState, false);
            }, true);

            phoneGuardAttached = true;
        }

        function parkPaymentPhoneField() {
            const phoneField = document.getElementById('payment-phone-field');
            const phoneHome = document.getElementById('payment-phone-field-home');

            if (phoneField && phoneHome && phoneField.parentElement !== phoneHome) {
                phoneHome.appendChild(phoneField);
            }
        }

        function movePaymentPhoneFieldBeforePayButton() {
            const container = document.getElementById('cardPaymentBrick_container');
            const phoneField = document.getElementById('payment-phone-field');

            if (!container || !phoneField || freeCheckout) {
                return;
            }

            const buttons = Array.from(container.querySelectorAll('button[type="submit"], input[type="submit"], button'))
                .filter(button => button.offsetParent !== null);
            const submitButton = buttons[buttons.length - 1];

            if (!submitButton) {
                return;
            }

            const buttonRow = submitButton.closest('div, fieldset, section, form') || submitButton;
            buttonRow.parentNode.insertBefore(phoneField, buttonRow);
        }

        function getPaymentPhoneState() {
            const phoneCountry = document.getElementById('payment_phone_country');
            const phone = value('payment_phone');
            const areaCode = phoneCountry?.selectedOptions[0]?.dataset.areaCode || phoneCountry?.value.replace(/\D/g, '') || '';

            return {
                areaCode,
                phone,
                isComplete: Boolean(areaCode && phone),
                focusTargetId: !areaCode ? 'payment_phone_country' : 'payment_phone'
            };
        }

        function notifyPaymentPhoneRequired(phoneState, reloadOnClose = false) {
            const phoneMessage = !phoneState.areaCode && !phoneState.phone
                ? 'Por seguridad necesitamos validar tu teléfono antes de procesar el pago. Selecciona el código de país e ingresa tu número de teléfono para continuar con tranquilidad.'
                : (!phoneState.areaCode
                    ? 'Por seguridad necesitamos que selecciones el código de país de tu teléfono antes de continuar con el pago.'
                    : 'Por seguridad necesitamos que ingreses tu número de teléfono antes de continuar con el pago.');

            showPhoneRequiredModal(phoneMessage, phoneState.focusTargetId, reloadOnClose);
        }

        function showPhoneRequiredModal(message, focusTargetId = 'payment_phone_country', reloadOnClose = false) {
            const messageElement = document.getElementById('phone-required-message');
            messageElement.textContent = message || 'Para continuar con el pago necesitamos que selecciones el código de país e ingreses tu número de teléfono. Esto nos ayuda a proteger tu compra y contactarte si hubiera algún inconveniente.';
            messageElement.dataset.focusTarget = focusTargetId;
            messageElement.dataset.reloadOnClose = reloadOnClose ? '1' : '0';
            document.getElementById('phone-required-modal').classList.remove('hidden');
        }

        function hidePhoneRequiredModal() {
            const messageElement = document.getElementById('phone-required-message');
            const focusTarget = document.getElementById(messageElement.dataset.focusTarget || 'payment_phone_country');
            const shouldReload = messageElement.dataset.reloadOnClose === '1';
            document.getElementById('phone-required-modal').classList.add('hidden');

            if (shouldReload) {
                window.location.reload();
                return;
            }

            if (focusTarget) {
                focusTarget.focus();
            }
        }

        function paymentWarningFromMercadoPagoError(error) {
            let detail = '';

            try {
                detail = typeof error === 'string' ? error : JSON.stringify(error || {});
            } catch (exception) {
                detail = String(error || '');
            }

            const normalized = detail.toLowerCase();

            if (normalized.includes('cc_rejected_insufficient_amount')) {
                return 'Tu Tarjeta no cuenta con saldo suficiente';
            }

            if (normalized.includes('cc_rejected_bad_filled_date')) {
                return 'Rechazado debido a un problema de fecha de vencimiento';
            }

            if (normalized.includes('cc_rejected_card_disabled')) {
                return 'Pago rechazado por Tarjeta Bloqueada/Apagada';
            }

            if (
                normalized.includes('cc_rejected_bad_filled_security_code') ||
                normalized.includes('cc_rejected_bad_filled_date') ||
                normalized.includes('expiration') ||
                normalized.includes('security_code') ||
                normalized.includes('cvv')
            ) {
                return 'Algun dato es erroneo en su tarjeta';
            }

            if (
                normalized.includes('cc_rejected_bad_filled_card_number') ||
                normalized.includes('invalid_card') ||
                normalized.includes('invalid card') ||
                normalized.includes('invalid_card_number') ||
                normalized.includes('invalid card_token_id')
            ) {
                return 'Tarjeta invalida';
            }

            return null;
        }

        function setBusy(id, busy) {
            const button = document.getElementById(id);
            button.disabled = busy;
            button.classList.toggle('opacity-50', busy);
        }

        function resetPaymentState() {
            paymentVersion += 1;
            paymentInitializing = false;
            paidSaleId = null;
            preferenceId = null;
            mercadoPublicKey = null;
            freeCheckout = false;
            showStep('payment');
            updateFreeCheckoutView();

            unmountMercadoPago();
        }

        function savePendingPaidCheckout(saleId, payer) {
            localStorage.setItem(pendingPaidCartKey, JSON.stringify({
                sale_id: saleId,
                payer: payer || {},
                cart_ids: cartIds,
                total: checkoutTotal,
                saved_at: Date.now()
            }));
        }

        function getPendingPaidCheckout() {
            try {
                return JSON.parse(localStorage.getItem(pendingPaidCartKey)) || null;
            } catch (error) {
                clearPendingPaidCheckout();
                return null;
            }
        }

        function clearPendingPaidCheckout() {
            localStorage.removeItem(pendingPaidCartKey);
        }

        function restorePendingPaidCheckout() {
            const pending = getPendingPaidCheckout();

            if (!pending || !pending.sale_id || !Array.isArray(pending.cart_ids)) {
                return false;
            }

            const currentIds = cartIds.map(Number).sort((a, b) => a - b).join(',');
            const pendingIds = pending.cart_ids.map(Number).sort((a, b) => a - b).join(',');

            if (currentIds !== pendingIds) {
                clearPendingPaidCheckout();
                return false;
            }

            hideAlert();
            freeCheckout = false;
            paidSaleId = pending.sale_id;
            preferenceId = null;
            mercadoPublicKey = null;
            paymentInitializing = false;
            unmountMercadoPago();
            updateFreeCheckoutView();
            parkPaymentPhoneField();
            document.getElementById('cardPaymentBrick_container').innerHTML = '<div class="mp-loading-message p-4 text-center">Tu pago ya fue aprobado. Completa tu cuenta o inicia sesion para finalizar tu compra.</div>';
            fillPayerData(pending.payer || {});
            showStep('final');
            showPendingPaidModal();

            return true;
        }

        function showPendingPaidModal() {
            const coursesContainer = document.getElementById('pending-paid-courses');
            coursesContainer.innerHTML = cartItems.map(item => `
                <div class="pending-paid-course">
                    <div>
                        <strong>${item.name}</strong>
                        <small>${item.additional || 'Curso seleccionado'}</small>
                    </div>
                    <span class="pending-paid-course-price">${priceLabel(item.price)}</span>
                </div>
            `).join('');

            document.getElementById('pending-paid-modal').classList.remove('hidden');
        }

        function hidePendingPaidModal() {
            document.getElementById('pending-paid-modal').classList.add('hidden');
        }

        function unmountMercadoPago() {
            if (window.cardPaymentBrickController && typeof window.cardPaymentBrickController.unmount === 'function') {
                try {
                    window.cardPaymentBrickController.unmount();
                } catch (error) {
                    console.log(error);
                }
            }

            window.cardPaymentBrickController = null;
        }

        function updateFreeCheckoutView() {
            document.querySelector('#step-payment .checkout-payment-card')?.classList.toggle('is-free-checkout', freeCheckout);
            document.querySelector('#step-payment .checkout-wide')?.classList.toggle('is-free-checkout', freeCheckout);
            document.getElementById('payment-step-text').textContent = freeCheckout ? 'Registro' : 'Comprar';
            document.getElementById('final-step-text').textContent = freeCheckout ? 'Acceso al webinar' : 'Crear cuenta y comprobante';
            document.querySelector('.cart-table-header span').textContent = 'PASO 1 DE 2';
            document.querySelector('.cart-table-header h2').textContent = 'Finaliza tu inscripción';
            document.querySelector('.cart-table-header p').textContent = freeCheckout ? '100% seguro con Mercado Pago' : 'Pago 100% seguro con Mercado Pago';
            document.getElementById('account-title').textContent = freeCheckout ? 'Completa tus datos para acceder' : 'Crea tu cuenta y elige tu comprobante';
            document.getElementById('account-subtitle').textContent = freeCheckout
                ? 'Crea una cuenta o inicia sesion para registrar el curso en tu dashboard.'
                : 'Completa tus datos para asociar la compra y emitir el comprobante.';
            document.getElementById("final-success-card").classList.toggle("d-none", freeCheckout);
            document.getElementById('invoice-block').classList.toggle('d-none', freeCheckout);
            document.getElementById('payment-phone-field').classList.toggle('d-none', freeCheckout);
            document.getElementById('btn-finalize-text').textContent = freeCheckout ? 'ACCEDER A TU CUENTA' : 'FINALIZAR COMPRA';
        }

        function requestJson(url, options, timeoutMs) {
            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), timeoutMs);
            const headers = {
                'Accept': 'application/json',
                ...(options.headers || {})
            };

            return fetch(url, { ...options, headers, signal: controller.signal })
                .then(async response => {
                    const text = await response.text();
                    let data = {};

                    try {
                        data = text ? JSON.parse(text) : {};
                    } catch (error) {
                        console.log(text);
                        throw new Error('El servidor no devolvio una respuesta valida.');
                    }

                    if (!response.ok) {
                        const requestError = new Error(data.error || data.message || 'No se pudo completar la solicitud.');
                        requestError.conflictType = data.conflict_type || null;
                        throw requestError;
                    }

                    return data;
                })
                .catch(error => {
                    if (error.name === 'AbortError') {
                        throw new Error('La solicitud esta tardando demasiado. Intenta nuevamente.');
                    }

                    throw error;
                })
                .finally(() => clearTimeout(timer));
        }

        function value(id) {
            return document.getElementById(id).value.trim();
        }

        function money(value) {
            return Number(value || 0).toFixed(2);
        }

        function priceLabel(value) {
            return Number(value || 0) <=0 ? 'Gratis' : `S/ ${money(value)}`;
        }

        // Refresh CSRF token after login/register
        function refreshCsrfToken() {
            fetch("{{ url('/get-csrf-token') }}", {
                method: "GET",
                headers: { "X-Requested-With": "XMLHttpRequest" }
            })
            .then(res => res.json())
            .then(data => {
                if (data.token) {
                    const meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) {
                        meta.content = data.token;
                        console.log('CSRF token refreshed');
                    }
                }
            })
            .catch(err => console.error('Failed to refresh CSRF token:', err));
        }

        // ==================== ABANDONED CART TRACKING ====================
        const ABANDONED_CLIENT_ID_KEY = 'abandoned_client_id';
        let abandonedClientId = localStorage.getItem(ABANDONED_CLIENT_ID_KEY);
        if (!abandonedClientId) {
            abandonedClientId = 'cid_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
            localStorage.setItem(ABANDONED_CLIENT_ID_KEY, abandonedClientId);
        }

        let abandonedTimer = null;

        function trackAbandonedCart() {
            const phoneState = getPaymentPhoneState();

            if (!phoneState.isComplete) {
                return;
            }

            let tracking = {};
            try { tracking = JSON.parse(localStorage.getItem('traffic_tracking') || '{}'); } catch (e) {}

            const payload = {
                client_id: abandonedClientId,
                phone_country: phoneState.areaCode,
                phone: phoneState.phone,
                cart_items: cartItems.length ? cartItems.map(item => ({ id: item.id, name: item.name, image: item.image, price: item.price, additional: item.additional })) : (cartIds.length ? cartIds.map(id => ({ id })) : undefined),
                cart_total: checkoutTotal || undefined,
                ...tracking,
            };

            const names = document.getElementById('create_names')?.value?.trim();
            const email = document.getElementById('create_email')?.value?.trim();
            if (names) payload.name = names;
            if (email) payload.email = email;

            fetch(routes.abandoned, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(payload),
            }).catch(() => {});
        }

        function scheduleAbandonedTracking() {
            clearTimeout(abandonedTimer);
            abandonedTimer = setTimeout(trackAbandonedCart, 800);
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.addEventListener('input', (e) => {
                if (e.target.id === 'payment_phone' || e.target.id === 'payment_phone_country') {
                    scheduleAbandonedTracking();
                }
            });
            document.getElementById('create_names')?.addEventListener('input', scheduleAbandonedTracking);
            document.getElementById('create_email')?.addEventListener('input', scheduleAbandonedTracking);
        });

        // ==================== PASSWORD RECOVERY ====================
        function showPasswordRecovery() {
            document.getElementById('password-recovery-modal').classList.remove('hidden');
            document.getElementById('recovery-message').innerHTML = '';
        }

        function hidePasswordRecovery() {
            document.getElementById('password-recovery-modal').classList.add('hidden');
        }

        function sendPasswordRecovery() {
            const email = document.getElementById('recovery-email').value;
            const msgDiv = document.getElementById('recovery-message');

            if (!email) {
                msgDiv.innerHTML = '<span class="text-red-600">Ingresa tu correo</span>';
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            fetch("{{ url('/send-password-recovery') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify({email: email})
            })
            .then(response => {
                if (response.status === 419) {
                    msgDiv.innerHTML = '<span class="text-red-600">Sesión expirada. Recargando página...</span>';
                    setTimeout(() => location.reload(), 2000);
                    throw new Error('Session expired');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    msgDiv.innerHTML = '<span class="text-green-600">Correo enviado. Revisa tu bandeja de entrada.</span>';
                    setTimeout(() => hidePasswordRecovery(), 3000);
                } else {
                    msgDiv.innerHTML = '<span class="text-red-600">' + (data.error || 'Error') + '</span>';
                }
            })
            .catch(err => {
                if (err.message !== 'Session expired') {
                    msgDiv.innerHTML = '<span class="text-red-600">Error al enviar correo</span>';
                }
            });
        }
    </script>
@stop
