<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\WebPageController;
use App\Mail\StudentRegistrationMailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\BlogController;
use Modules\Sales\Http\Controllers\SalesController;

/*
|--------------------------------------------------------------------------
| ARACODE Smart Solutions — Website
|--------------------------------------------------------------------------
| Rutas públicas del sitio corporativo. Este archivo se monta en
| RouteServiceProvider con el middleware "web" y SIN prefijo, exactamente
| igual que antes desde routes/web.php, por lo que las URLs y los nombres
| de ruta se mantienen intactos.
|
| Las rutas del panel/app (dashboard, usuarios, kardex, parametros, etc.)
| siguen en routes/web.php.
*/

// Homepage — respeta el parametro PW00001 (1 = Aracode Principal, 2 = Aracode Torneos)
Route::get('/', [WebPageController::class, 'index'])->name('index_main');
Route::get('/home', fn () => redirect()->route('index_main'));

// Soluciones
Route::get('/soluciones', [WebPageController::class, 'soluciones'])->name('soluciones');

// Productos del ecosistema, cada uno con su URL corta propia.
// Las rutas mantienen el nombre solucion_* para el estado activo del navbar.
Route::get('/kapta', [WebPageController::class, 'solucionKapta'])->name('solucion_kapta');
Route::get('/kirafact', [WebPageController::class, 'solucionFacturacion'])->name('solucion_facturacion');
Route::get('/pichanguero', [WebPageController::class, 'solucionPichanguero'])->name('solucion_pichanguero');

// Servicio y redirecciones desde las URLs anteriores bajo /soluciones
Route::get('/soluciones/desarrollo', [WebPageController::class, 'solucionDesarrollo'])->name('solucion_desarrollo');
Route::get('/soluciones/kapta', fn () => redirect()->route('solucion_kapta', [], 301));
Route::get('/soluciones/facturacion', fn () => redirect()->route('solucion_facturacion', [], 301));

// Empresa y Contacto
Route::get('/empresa', [WebPageController::class, 'empresa'])->name('empresa');
Route::get('/contacto', [WebPageController::class, 'contacto'])->name('contacto');
Route::post('/contacto', [WebPageController::class, 'contactoStore'])->name('contacto_store');
Route::post('/blog/subscribe', [WebPageController::class, 'blogSubscriberStore'])->name('blog.subscribe');

// Blog
Route::get('/blog', [WebPageController::class, 'blog_index'])->name('blog_principal');
// El wildcard {url} captura cualquier slug de artículo público. Se marca como
// fallback para que NO tape las rutas del admin del módulo Blog
// (/blog/blog-article, /blog/blog-category, /blog/dashboard), que se registran
// después. Sin fallback, /blog/blog-article caía aquí y devolvía 404 porque
// no existe ningún artículo con ese slug.
Route::get('/blog/{url}', [WebPageController::class, 'blog_article'])
    ->name('blog_article')
    ->fallback();

// Registro de la vista del articulo. Va aparte del render para que el navegador
// decida con localStorage si corresponde contarla (una vez por dia por articulo).
Route::post('/blog/{url}/vista', [WebPageController::class, 'blog_article_view'])
    ->name('blog_article_view');

// Páginas adicionales
Route::get('/casos-exito', [WebPageController::class, 'casosExito'])->name('casos_exito');
Route::get('/faq', [WebPageController::class, 'faq'])->name('faq');
Route::get('/trabaja-con-nosotros', [WebPageController::class, 'trabajaNosotros'])->name('trabaja_nosotros');
Route::get('/politica-privacidad', [WebPageController::class, 'politicaPrivacidad'])->name('politica_privacidad');
Route::get('/terminos-condiciones', [WebPageController::class, 'terminosCondiciones'])->name('terminos_condiciones');
Route::get('/libro-reclamaciones', [WebPageController::class, 'libroReclamaciones'])->name('libro_reclamaciones');
Route::get('/politica-cookies', [WebPageController::class, 'politicaCookies'])->name('politica_cookies');
Route::get('/portafolio', [WebPageController::class, 'portafolio'])->name('portafolio');
Route::get('/precios', [WebPageController::class, 'precios'])->name('precios');
Route::get('/equipo', [WebPageController::class, 'equipo'])->name('equipo');

// Redirecciones de rutas antiguas
//
// 301 permanente, no 302 temporal: son URLs retiradas que no van a volver, y
// solo el 301 le dice a Google que la autoridad de la vieja URL pasa a la
// nueva (un 302 la conserva en la URL antigua indefinidamente). Además estas
// rutas apuntan directo al destino final: si algún día un producto cambia de
// URL, hay que actualizarlas aquí para no encadenar redirecciones.
Route::get('/nosotros', fn () => redirect()->route('empresa', [], 301));
Route::get('/v2', fn () => redirect()->route('index_main', [], 301));
Route::get('/sitios-webs', fn () => redirect()->route('solucion_kapta', [], 301));
Route::get('/tienda-online', fn () => redirect()->route('soluciones', [], 301));
Route::get('/e-learning', fn () => redirect()->route('solucion_kapta', [], 301));
Route::get('/facturador', fn () => redirect()->route('solucion_facturacion', [], 301));
Route::get('/contacto-v2', fn () => redirect()->route('contacto', [], 301));

// Route::get('/', [LandingController::class, 'index'])->name('index_main');
// Route::get('/facturador', [LandingController::class, 'biller'])->name('biller_main');
Route::get('/news', [LandingController::class, 'blog'])->name('blog_main');
Route::get('/terms', [LandingController::class, 'terms'])->name('terms_main');
Route::get('/computer/store', [LandingController::class, 'computerStore'])->name('index_computer_store');
Route::get('/prices/academic', [LandingController::class, 'academicPrices'])->name('academic_prices');
// Ruta amigable por slug; si llega un id numerico antiguo redirige a su slug.
Route::get('/curso-descripcion/{slug}', [WebPageController::class, 'cursodescripcion'])->name('web_curso_descripcion');

// Landing publica por slug del curso (usada por catalogo, carrito y panel del alumno).
Route::get('/curso/{slug}', [WebPageController::class, 'course_url_slug'])->name('course_url_slug');

// Carrito de compras publico (la landing y la descripcion redirigen aqui).
Route::get('/carrito', [WebPageController::class, 'shopcart'])->name('web_carrito');

// Flujo de compra del carrito (checkout de MercadoPago).
Route::post('/carrito/preferencia', [WebPageController::class, 'cartPreference'])->name('web_cart_preference');
Route::post('/carrito/pago', [WebPageController::class, 'cartProcessPayment'])->name('web_cart_process_payment');
Route::post('/carrito/finalizar', [WebPageController::class, 'cartFinalize'])->name('web_cart_finalize');
Route::post('/carrito/abandonado', [WebPageController::class, 'cartAbandonedStore'])->name('web_cart_abandoned');

// Pagina de pago de una venta online (back_url de MercadoPago y retorno tras crear la venta).
Route::get('/pagar/{sale}', [WebPageController::class, 'pay'])->name('web_pagar');

// Gracias por la compra de cursos (venta online).
Route::get('/gracias-cursos/{id}', [WebPageController::class, 'thanks'])->name('web_gracias_por_cursos');

// Procesamiento del pago con tarjeta (checkout de la vista pagar).
Route::put('/pagar-proceso/{sale}/{student}', [WebPageController::class, 'processPayment'])->name('web_process_payment');

// Alias para plantillas de email antiguas (evita duplicar el path /).
Route::get('/inicio', function () { return redirect()->route('index_main'); })->name('web_inicio');

Route::get('/academy/{slug}', [Modules\Academic\Http\Controllers\AcaCourseLandingController::class, 'show'])
    ->name('academy_landing');

Route::get('/api-docs', function() {
    return view('aracode.pages.api-docs');
})->name('api_docs');

// ////mensajes de whatsapp///////
Route::get('/ask/product/{id}', [LandingController::class, 'redirectToWhatsApp'])->name('whatsapp_send');

// ///cunsulta comprobante electronico ///////////
Route::get('/find/invoice', [SalesController::class, 'findInvoice'])->name('find_electronic_invoice');
Route::post('/find/invoice', [SalesController::class, 'clientSearchDocument'])->name('client_search_electronic_invoice');

// Route::get('/blog/home', [BlogController::class, 'index'])->name('blog_principal');
// Route::get('/article/{url}', [BlogController::class, 'article'])->name('blog_article_by_url');
// Route::get('/category/{id}', [BlogController::class, 'category'])->name('blog_category');
// Route::get('/policies', [BlogController::class, 'policies'])->name('blog_policies');
// Route::get('/contact-us', [BlogController::class, 'contactUs'])->name('blog_contact_us');

Route::get('/stories/article/{url}', [BlogController::class, 'storiesArticle'])->name('blog_stories_article_by_url');
Route::get('/stories/policies', [BlogController::class, 'storiesPolicies'])->name('blog_stories_policies');
Route::get('/stories/contact-us', [BlogController::class, 'storiesContactUs'])->name('blog_stories_contact_us');

// Route::get('/email', function () {
//     Mail::to('elrodriguez2423@gmail.com')
//         ->send(new StudentRegistrationMailable('data'));
//     return 'mensaje enviado';
// });
