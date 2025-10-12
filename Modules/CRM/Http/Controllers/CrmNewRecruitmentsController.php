<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExcelExportJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaArrivalSource;
use Modules\Academic\Entities\AcaStudent;
use DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Modules\CRM\Jobs\ExportCrmContactsExcel;

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
                'coursesInterest.course',
                'registrations' => function ($query) {
                    $query->whereNotNull('arrival_source_id')
                        ->with('course');
                },
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

        return DataTables::eloquent($members)
            ->filter(function ($query) use ($request) {
                if ($request->custom_search) {
                    $search = $request->custom_search;
                    $query->whereHas('person', function($q) use ($search) {
                        $q->where('full_name', 'like', "%{$search}%");
                    });
                }

                $filters = $request->filters;

                if ($filters['arrival_source']) {
                    $query->where('arrival_source_id', $filters['arrival_source']);
                }
            })->toJson();
    }

    public function exportCrmContacts(Request $request): JsonResponse
    {
        // $this->validate($request, [
        //     'search' => 'nullable|string|max:255',
        //     'status' => 'nullable|string|max:50',
        //     'created_at' => [
        //         'nullable',
        //         'regex:/^\d{4}-\d{2}-\d{2}( a \d{4}-\d{2}-\d{2})?$/'
        //     ],
        // ]);

        $filters = [
            'search' => $request->get('search'),
            'arrival_source' => $request->get('arrival_source'),
        ];

        $excelExportJob = ExcelExportJob::create([
            'user_id' => Auth::id(),
            'report_type' => 'CRM_CONTACTS',
            'status' => 'pending',
            'filters' => json_encode($filters),
        ]);

        ExportCrmContactsExcel::dispatch(
            $filters,
            $excelExportJob->id,
            Auth::id()
        )->onQueue('exports');

        return response()->json([
            'message' => 'La exportación de contactos CRM ha comenzado. Se le notificará cuando esté lista.',
            'job_id' => $excelExportJob->id,
        ], 202);
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
