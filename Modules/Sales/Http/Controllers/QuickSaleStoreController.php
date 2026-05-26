<?php

namespace Modules\Sales\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Http\Requests\StoreQuickSaleRequest;
use Modules\Sales\Services\QuickSaleService;
use RuntimeException;
use Throwable;

class QuickSaleStoreController extends Controller
{
    public function store(StoreQuickSaleRequest $request, QuickSaleService $quickSaleService)
    {
        try {
            $result = DB::transaction(function () use ($request, $quickSaleService) {
                return $quickSaleService->register($request->validated());
            });

            return response()->json(array_merge(['success' => true], $result));
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró un registro requerido para completar la venta.',
            ], 422);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
