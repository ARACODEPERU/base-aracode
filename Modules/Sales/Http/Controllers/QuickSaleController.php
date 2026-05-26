<?php

namespace Modules\Sales\Http\Controllers;

use App\Models\District;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class QuickSaleController extends Controller
{
    public function index()
    {
        $products = Product::where('is_product', 1)->get();
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
