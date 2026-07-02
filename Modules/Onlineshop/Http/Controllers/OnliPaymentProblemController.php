<?php

namespace Modules\Onlineshop\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Onlineshop\Entities\OnliCarritoAbandonado;
use Modules\Onlineshop\Entities\OnliPaymentProblem;

class OnliPaymentProblemController extends Controller
{
    /**
     * Display a listing of payment problems and abandoned carts.
     * @return Renderable
     */
    public function index()
    {
        // 1. Payment problems: pagos aprobados pero no completados
        $paymentProblems = OnliPaymentProblem::where('status', 'pending')
            ->orderBy('created_at', 'DESC')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => 'payment_problem',
                    'type_label' => 'Pagado/cuenta incompleta',
                    'sale_id' => $item->sale_id,
                    'phone_country' => $item->phone_country,
                    'phone' => $item->phone,
                    'email' => $item->email,
                    'clie_full_name' => $item->clie_full_name,
                    'amount' => $item->amount,
                    'courses_info' => $item->courses_info,
                    'payment_data' => $item->payment_data,
                    'payment_method' => $item->payment_method,
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                ];
            });

        // 2. Abandoned carts: carritos abandonados sin pago
        $abandonedCarts = OnliCarritoAbandonado::where('paid', false)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => 'abandoned_cart',
                    'type_label' => 'Carrito abandonado',
                    'sale_id' => null,
                    'phone_country' => $item->phone_country,
                    'phone' => $item->phone,
                    'email' => $item->email,
                    'clie_full_name' => $item->name,
                    'amount' => $item->cart_total,
                    'courses_info' => $item->cart_items,
                    'payment_data' => null,
                    'payment_method' => null,
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                ];
            });

        // 3. Unir ambas colecciones y ordenar por fecha descendente
        $items = $paymentProblems->concat($abandonedCarts)->sortByDesc('created_at')->values();

        return Inertia::render('Onlineshop::Sales/PaymentProblems', [
            'items' => $items,
        ]);
    }

    /**
     * Clean records older than 30 days.
     */
    public function cleanOldRecords(Request $request)
    {
        $days = $request->input('days', 30);
        $cutoff = now()->subDays($days);

        $deletedProblems = OnliPaymentProblem::where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $deletedCarts = OnliCarritoAbandonado::where('paid', false)
            ->where('created_at', '<', $cutoff)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "Se eliminaron {$deletedProblems} registros de pagos sin completar y {$deletedCarts} carritos abandonados mayores a {$days} días.",
            'deleted_problems' => $deletedProblems,
            'deleted_carts' => $deletedCarts,
        ]);
    }
}
