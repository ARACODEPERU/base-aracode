<?php

namespace App\Http\Controllers;

use App\Models\Kardex;
use App\Models\KardexSize;
use App\Models\LocalSale;
use App\Models\Product;
use App\Models\SaleProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class KardexController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $establishments = LocalSale::all();
        $local_id = request()->input('local_id');
        $search = request()->input('search');

        $query = Kardex::join('products', 'kardexes.product_id', 'products.id')
            ->join('local_sales', 'kardexes.local_id', 'local_sales.id')
            ->select(
                'products.*',
                'local_sales.id AS local_id',
                'local_sales.description AS local_names'
            )
            ->selectRaw('CONVERT(SUM(kardexes.quantity), SIGNED INTEGER) AS kardex_stock')
            ->groupBy(
                'products.id',
                'local_sales.description',
                'local_sales.id'
            )
            // El inventario y el kardex son solo para productos fisicos: los
            // servicios (is_product = false) no manejan stock ni movimientos.
            ->where('products.is_product', true)
            ->orderBy('local_sales.description')
            ->orderBy('products.description');

        if ($search !== null && $search !== '') {
            // El criterio de busqueda va agrupado para que el filtro del local
            // siga aplicandose (un orWhere suelto se saltaba ese filtro).
            $query->where(function ($subquery) use ($search) {
                $subquery->where('products.interne', '=', $search)
                    ->orWhere('products.description', 'Like', '%' . $search . '%');
            });
        }

        if ($local_id != null && $local_id != 0) {
            $query->where('kardexes.local_id', $local_id);
        }

        $kardexes = $query->paginate(20)->withQueryString();

        return Inertia::render('Kardex/List', [
            'establishments' => $establishments,
            'kardexes' => $kardexes,
            'local_id' => $local_id,
            'filters' => request()->all('search')
        ]);
    }

    public function kardexDeailsSises(Request $request)
    {
        $kardex_sizes = KardexSize::select(
            'kardex_sizes.size',
            DB::raw('SUM(kardex_sizes.quantity) AS total')
        )
            ->where('local_id', $request->get('local_id'))
            ->where('product_id', $request->get('product_id'))
            ->groupBy('kardex_sizes.size')
            ->get();

        return response()->json($kardex_sizes);
    }

    public function generalStock()
    {
        $previous_date = Carbon::now()->subDay()->format('Y-m-d');
        $current_date = Carbon::now()->format('Y-m-d');
        $local_id = Auth::user()->local_id;
        $local = LocalSale::find($local_id);

        $stock_old = Kardex::whereDate('created_at', '<=', $previous_date)
            ->where('local_id', $local_id)
            ->sum('quantity');

        $stock_sales = SaleProduct::join('sales', 'sale_id', 'sales.id')
            ->join('products', 'sale_products.product_id', 'products.id')
            ->whereDate('sale_products.created_at', $current_date)
            ->where('sales.local_id', $local_id)
            ->where('sales.status', true)
            ->where('products.is_product', true)
            ->sum('quantity');

        $stock_input = Kardex::whereDate('created_at', '=', $current_date)
            ->where('motion', '=', 'purchase')
            ->where('local_id', $local_id)
            ->sum('quantity');

        $total = Product::where('is_product', true)->sum('stock');

        $stock_today = Kardex::where('local_id', $local_id)->sum('quantity');

        return response()->json([
            'stock_old' => (int) $stock_old,
            'stock_sales' => (int) $stock_sales,
            'stock_today' => (int) $stock_today,
            'stock_input' => (int) $stock_input,
            'local' => $local,
            'total' => $total
        ]);
    }
}
