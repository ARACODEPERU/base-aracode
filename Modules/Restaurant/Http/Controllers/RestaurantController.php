<?php

namespace Modules\Restaurant\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Inertia\Inertia;
use Modules\Restaurant\Entities\ResComanda;
use Modules\Restaurant\Entities\ResMenu;
use Modules\Restaurant\Entities\ResSale;
use Modules\Restaurant\Services\ResRecipeConsumptionService;
use Modules\Restaurant\Services\ResSupplyStockService;

class RestaurantController extends Controller
{
    public function __construct(
        protected ResSupplyStockService $stockService,
        protected ResRecipeConsumptionService $consumptionService
    ) {}

    public function index()
    {
        $today = Carbon::today()->toDateString();

        $salesToday = ResSale::whereDate('sale_date', $today)
            ->whereNotIn('queue_status', ['99'])
            ->get(['id', 'total']);

        $pendingKitchen = ResSale::whereIn('queue_status', ['01', '02'])->count();

        $activeComandas = ResComanda::where('status', true)->count();

        $activeMenus = ResMenu::where('status', true)->count();

        return Inertia::render('Restaurant::Dashboard', [
            'metrics' => [
                'sales_today_count' => $salesToday->count(),
                'sales_today_total' => round($salesToday->sum('total'), 2),
                'pending_kitchen' => $pendingKitchen,
                'active_comandas' => $activeComandas,
                'active_menus' => $activeMenus,
                'low_stock_count' => $this->stockService->getLowStockCount(),
                'out_of_stock_count' => $this->stockService->getOutOfStockCount(),
                'comandas_without_recipe' => $this->consumptionService->countComandasWithoutRecipe(),
            ],
            'critical_supplies' => $this->consumptionService->getTopCriticalSupplies(5),
        ]);
    }
}
