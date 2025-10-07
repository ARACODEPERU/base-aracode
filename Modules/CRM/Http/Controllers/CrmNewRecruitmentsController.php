<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaArrivalSource;
use Modules\Academic\Entities\AcaStudent;
use DataTables;
use Illuminate\Support\Facades\DB;

class CrmNewRecruitmentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $arrival_sources = AcaArrivalSource::get();
        return Inertia::render('CRM::Recruitments/List',[
            'arrivalSources' => $arrival_sources
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function getCatchments(Request $request)
    {
        $members = AcaStudent::query()
            ->with([
                'person' => function ($query) {
                    $query->with([
                            'country',
                            'identityDocumentType',
                        ]);
                },
                'arrivalSource:id,name',
            ])
            ->whereNotNull('arrival_source_id')
            ->select([
                'aca_students.*',
                DB::raw("(
                        SELECT
                            title
                        FROM cms_landings
                        WHERE menu_id = '01'
                        LIMIT 1
                    ) AS arrival_source_text
                ")
            ]);

        return DataTables::eloquent($members)->filter(function ($query) use ($request) {
                if ($request->custom_search) {
                    $search = $request->custom_search;
                    $query->whereHas('person', function($q) use ($search) {
                        $q->where('full_name', 'like', "%{$search}%");
                    });
                }

                // $filters = $request->filters;
                // if ($filters['nivel']) {
                //     $query->where('type_id', $filters['nivel']);
                // }
                // if ($filters['sede']) {
                //     $query->where('sede_id', $filters['sede']);
                // }
                // if ($filters['ubigeo']) {
                //     $query->whereHas('person', function($q) use ($filters) {
                //         $q->where('ubigeo', $filters['ubigeo']);
                //     });
                // }
                // if($filters['evangelization'] && $hasFullAccess){
                //     $query->whereHas('believers', function($q) use ($filters) {
                //         $q->where('evangelization_id', $filters['evangelization']);
                //     });
                // }
            })
            ->toJson();
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
        return view('crm::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('crm::edit');
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
