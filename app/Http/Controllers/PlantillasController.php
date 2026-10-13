<?php

namespace App\Http\Controllers;

use App\Support\CatalogoPlantillas;
use App\Support\ServicioWeb;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| Banco de Plantillas Web
|--------------------------------------------------------------------------
|
| Tres acciones, sin base de datos: el catálogo sale de config/plantillas.php a
| través de App\Support\CatalogoPlantillas (ver docs/BANCO_PLANTILLAS_FASE1.md).
|
| Va en su propio controlador, y no en WebPageController, para no añadir nada al
| archivo compartido del sitio corporativo: esta sección se puede quitar
| borrando este archivo, su vista, sus rutas y su config.
|
*/

class PlantillasController extends Controller
{
    /**
     * Catálogo: categorías, buscador y rejilla de plantillas.
     *
     * Los filtros van por query string (`?categoria=salud&q=dental`) y se
     * resuelven en el servidor: funcionan sin JavaScript y cada combinación
     * comparte la canónica del layout, que no arrastra la query string, así que
     * Google indexa una sola URL del catálogo.
     */
    public function index(Request $request): View
    {
        $categoria = $this->texto($request->query('categoria'));
        $busqueda = mb_substr($this->texto($request->query('q')), 0, 80);

        return view('plantillas.catalogo', [
            'plantillas' => CatalogoPlantillas::filtrar($categoria, $busqueda),
            'categorias' => CatalogoPlantillas::categorias(),
            'categoria' => $categoria,
            'busqueda' => $busqueda,
            'total' => count(CatalogoPlantillas::todas()),
            // Los planes del servicio, para el bloque que cierra el catálogo:
            // el visitante que ya vio la plantilla que le gusta encuentra ahí
            // cómo publicarla sin salir de la página.
            'planesServicio' => ServicioWeb::planes(),
            'urlServicio' => ServicioWeb::url(),
        ]);
    }

    /**
     * Página del servicio: los planes con que se publica una plantilla.
     *
     * Vive aquí y no en WebPageController porque es la otra mitad de la misma
     * sección: el catálogo muestra lo que se puede publicar y esta página, cómo
     * y por cuánto. Los planes salen de config/servicio_web.php a través de
     * App\Support\ServicioWeb; los formatos (`#Landing`, `#SitioWeb`), del
     * catálogo, para enlazar cada plan con las plantillas de ese formato.
     */
    public function paginasWeb(): View
    {
        return view('aracode.pages.paginas-web', [
            'planes' => ServicioWeb::planes(),
            'precioDesde' => ServicioWeb::precioDesde(),
            'incluyeSiempre' => ServicioWeb::incluyeSiempre(),
            'notaPago' => ServicioWeb::notaPago(),
            'tipos' => CatalogoPlantillas::tipos(),
        ]);
    }

    /**
     * Ficha de la plantilla. Un slug que no existe —o una plantilla sin
     * publicar— responde 404, nunca una ficha vacía.
     */
    public function ficha(string $slug): View
    {
        $plantilla = CatalogoPlantillas::porSlug($slug);

        abort_if($plantilla === null, 404);

        return view('plantillas.ficha', [
            'plantilla' => $plantilla,
            'urlDemo' => CatalogoPlantillas::urlDemo($plantilla),
            'relacionadas' => CatalogoPlantillas::deLaMismaCategoria($slug),
            // El plan con que se publica esta plantilla, según su formato: es el
            // dato que convierte la visita en venta, así que se ve en la ficha y
            // no solo en la página del servicio.
            'planServicio' => ServicioWeb::plan($plantilla['tipo']),
            'urlServicio' => ServicioWeb::url(),
        ]);
    }

    /**
     * Abre la demostración.
     *
     * 302 y no 301 a propósito: la demostración es su propio proyecto web y
     * puede mudarse a un subdominio, así que su URL no es un destino definitivo.
     * Si la plantilla no tiene demo —o la carpeta todavía no está— responde 404
     * en vez de mandar al visitante a una página inexistente.
     */
    public function demo(string $slug): RedirectResponse
    {
        $plantilla = CatalogoPlantillas::porSlug($slug);
        $urlDemo = $plantilla === null ? null : CatalogoPlantillas::urlDemo($plantilla);

        abort_if($urlDemo === null, 404);

        return redirect()->away($urlDemo);
    }

    /**
     * Valor de query string como texto: un `?categoria[]=x` llega como array y
     * no debe romper la página ni llegar crudo a la vista.
     */
    private function texto(mixed $valor): string
    {
        return is_string($valor) ? trim($valor) : '';
    }
}
