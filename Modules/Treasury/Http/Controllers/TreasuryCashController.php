<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Treasury\Services\TreasuryCashService;

class TreasuryCashController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(private TreasuryCashService $cash)
    {
    }

    /**
     * Panel de efectivo: ventas y abonos cobrados en efectivo, egresos de caja
     * chica y saldo por sesión. No escribe en el libro de bancos.
     */
    public function index(Request $request)
    {
        $this->authorizePermission('treasury_efectivo');

        return Inertia::render('Treasury::Cash/Index', $this->cash->build(
            $request->get('from'),
            $request->get('to')
        ));
    }
}
