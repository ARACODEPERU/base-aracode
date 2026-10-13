<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BrandingController extends Controller
{
    /**
     * Configuraciones → Branding: los colores de marca de la instalación.
     *
     * Sin colores guardados la vista muestra la paleta de fábrica; al guardar,
     * el layout del ERP inyecta las variables `--brand-*` en todo el sistema.
     */
    public function show()
    {
        $company = Company::first();

        return Inertia::render('Company/Branding', [
            'colores' => [
                'primary' => $company?->brand_primary,
                'secondary' => $company?->brand_secondary,
            ],
        ]);
    }

    /**
     * Guarda los colores de marca. Un valor vacío (o el de fábrica enviado como
     * nulo) devuelve la instalación a la paleta original del ERP.
     */
    public function update(Request $request)
    {
        $request->validate([
            'brand_primary' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand_secondary' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        Company::first()?->update([
            'brand_primary' => $request->get('brand_primary') ?: null,
            'brand_secondary' => $request->get('brand_secondary') ?: null,
        ]);

        $sinMarca = ! $request->get('brand_primary') && ! $request->get('brand_secondary');

        return redirect()->route('branding_show')->with(
            'message',
            $sinMarca
                ? 'La instalación volvió a los colores de fábrica.'
                : 'Colores de marca guardados: ya se aplican a todo el ERP.'
        );
    }
}
