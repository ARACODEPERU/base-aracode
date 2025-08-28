<?php

namespace Modules\Churchcommunity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Sede;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CigleMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('churchcommunity::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function believerCreate()
    {
        $sedes = Sede::get();
        $identityDocumentTypes = DB::table('identity_document_type')->whereNotIn('id',[6])->get();

        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();

        return Inertia::render('Churchcommunity::Members/BelieversCard',[
            'sedes' => $sedes,
            'identityDocumentTypes' => $identityDocumentTypes,
            'ubigeo' => $ubigeo,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('churchcommunity::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('churchcommunity::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
