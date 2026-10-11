<?php

namespace App\Http\Controllers;

use App\Support\CatalogoPlantillas;
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
