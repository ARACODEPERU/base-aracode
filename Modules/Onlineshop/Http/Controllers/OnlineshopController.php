<?php

namespace Modules\Onlineshop\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Onlineshop\Entities\OnliSale;

class OnlineshopController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return Inertia::render('Onlineshop::dashboard');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function getTotalSales()
    {
        // 1. Obtener la cantidad total de ventas desde el inicio de los tiempos
        $totalSalesCount = OnliSale::count();

        // 2. Obtener la fecha de inicio del año actual
        $startOfYear = Carbon::now()->startOfYear();
        // Obtener la fecha de fin del año actual
        $endOfYear = Carbon::now()->endOfYear();

        // 3. Obtener la cantidad de ventas de este año
        // Se asume que la columna de fecha relevante para la venta es 'created_at'.
        // Si tienes una columna diferente para la fecha de la venta (ej. 'response_date_approved'), úsala.
        $thisYearSalesCount = OnliSale::whereBetween('created_at', [$startOfYear, $endOfYear])
                                       ->count();

        // Puedes formatear el mensaje como lo solicitaste
        $summaryMessage = "Ventas de este año: {$thisYearSalesCount} de {$totalSalesCount} ventas totales";

        return response()->json([
            'sales' => [
                'total_sales_count' => $totalSalesCount,
                'this_year_sales_count' => $thisYearSalesCount,
                'summary_message' => $summaryMessage,
            ]
        ]);
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
        return view('onlineshop::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('onlineshop::edit');
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
