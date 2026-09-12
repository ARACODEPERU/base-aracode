<?php

namespace Modules\Security\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Security\Services\StorageMetricsService;

class SecurityController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('security::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('security::create');
    }

    /**
     * Indicador de almacenamiento del dashboard. Devuelve la medición
     * cacheada (24 h) del servicio de métricas.
     */
    public function storageIndicador(StorageMetricsService $metrics)
    {
        return response()->json($metrics->get());
    }

    /**
     * Recalcula la medición de almacenamiento al instante (botón "Recalcular"
     * del dashboard) y devuelve el resultado fresco.
     */
    public function storageRecalculate(StorageMetricsService $metrics)
    {
        return response()->json($metrics->measure());
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('security::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('security::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
