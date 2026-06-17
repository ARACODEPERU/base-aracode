<?php

namespace Modules\Sales\Http\Controllers;

use App\Models\District;
use App\Models\PaymentMethod;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class QuickSaleController extends Controller
{
    public function index()
    {
        $localId = Auth::user()->local_id;

        $products = DB::table('products as t1')
            ->select(
                't1.id',
                't1.usine',
                't1.interne',
                't1.description',
                't1.image',
                't1.purchase_prices',
                't1.sale_prices',
                't1.sizes',
                't1.stock_min',
                't1.stock',
                't1.presentations',
                't1.is_product',
                't1.type_sale_affectation_id',
                't1.type_purchase_affectation_id',
                't1.type_unit_measure_id',
                't1.status',
                't1.category_id',
                't1.brand_id',
                't1.icbper',
                DB::raw('(
                    SELECT JSON_ARRAYAGG(JSON_OBJECT("size", size, "quantity", quantity_sum))
                    FROM (
                        SELECT size, SUM(quantity) AS quantity_sum
                        FROM kardex_sizes
                        WHERE kardex_sizes.product_id = t1.id AND local_id = '.$localId.'
                        GROUP BY size
                    ) AS aggregated_sizes
                ) AS local_sizes')
            )
            ->where('t1.is_product', 1)
            ->get();
        $client = Person::find(1);
        $paymentMethods = PaymentMethod::all();
        $saleDocumentTypes = DB::table('sale_document_types')
            ->whereIn('sunat_id', ['01', '03', '80'])
            ->get();

        $documentTypes = DB::table('identity_document_type')->get();

        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                'districts.name AS district_name',
                'provinces.name AS province_name',
                'departments.name AS department_name',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS city_name")
            )
            ->get();

        return Inertia::render('Sales::Sales/QuickSale/Index', [
            'products' => $products,
            'clientDefault' => $client,
            'paymentMethods' => $paymentMethods,
            'saleDocumentTypes' => $saleDocumentTypes,
            'documentTypes' => $documentTypes,
            'departments' => $ubigeo,
        ]);
    }
}
