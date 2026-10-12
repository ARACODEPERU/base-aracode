<?php

namespace Modules\Commercial\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Commercial\Support\CommercialLegalLinks;

class CommercialSettingController extends Controller
{
    /**
     * Pagina de configuracion del modulo Comercial. Por ahora tiene una pestana con
     * los enlaces legales que el formulario publico de una negociacion muestra como
     * checks obligatorios.
     */
    public function index(): Response
    {
        return Inertia::render('Commercial::Settings/Index', [
            'legalLinks' => CommercialLegalLinks::stored(),
            'legalLinkDefaults' => CommercialLegalLinks::DEFAULTS,
            'legalLinkLabels' => CommercialLegalLinks::labels(),
        ]);
    }

    /**
     * Guarda los tres enlaces. Se acepta una URL externa (https://...) o una ruta del
     * propio sitio que empiece con /, que es lo que el formulario publico completa con
     * el dominio actual.
     */
    public function updateLegal(Request $request)
    {
        $keys = array_keys(CommercialLegalLinks::KEYS);

        $rules = [];
        $messages = [];

        foreach ($keys as $key) {
            $rules[$key] = ['required', 'string', 'max:500', 'regex:#^(https?://|/)#i'];
            $messages[$key.'.required'] = 'El enlace es obligatorio.';
            $messages[$key.'.regex'] = 'Ingresa una URL completa (https://...) o una ruta del sitio que empiece con /.';
        }

        $request->validate($rules, $messages);

        CommercialLegalLinks::save($request->only($keys));

        return redirect()->back()->with('success', 'Enlaces legales actualizados correctamente.');
    }
}
