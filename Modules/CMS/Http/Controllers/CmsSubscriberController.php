<?php

namespace Modules\CMS\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Validator;
use Modules\CMS\Entities\CmsSubscriber;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificacionDescarga_brochure;
use Inertia\Inertia;
use App\Models\ExcelExportJob;
use Modules\CMS\Jobs\ExportCmsSubscribersExcel;
use Carbon\Carbon;

class CmsSubscriberController extends Controller
{
    use ValidatesRequests;
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('cms::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('cms::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function apiStore(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email|max:255',
                'full_name' => 'required',
                'phone' => 'required',
            ],
            [
                //'email.unique' => 'El correo electrónico ya existe',
                'email.required' => 'El correo electrónico es obligatorio',
                'email.email' => 'Por favor, ingrese una dirección de correo electrónico válida.',
                'email.max' => 'Limita la longitud máxima del campo de correo electrónico a 255 caracteres',
                'full_name' => 'Agrega tu nombre por favor.',
                'phone' => 'Ponga un numero de teléfono valido por favor.'
            ]
        );

        // Verificar si las validaciones fallaron
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $Subscriber = CmsSubscriber::create([
            'full_name'     => $request->get('full_name') ?? null,
            'email'         => $request->get('email'),
            'phone'         => $request->get('phone') ?? null,
            'client_ip'     => $request->ip(),
            'read'          => 0,
            'subject'       => $request->get('subject') ?? null,
            'message'       => $request->get('message') ?? null,
        ]);

        try {
            //Correo a Ronald
        Mail::to(env("MAIL_ADMIN"))
        ->send(new NotificacionDescarga_brochure($Subscriber));

        } catch (\Throwable $th) {
            //throw $th;
        }

        return response()->json([
            'success' => true,
            'message' => 'Datos registrados con exito'
        ]);
    }

    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'full_name' => 'required|max:255',
                'email' => 'required|max:255',
                'phone' => 'required|max:255',
                'message' => 'required',
            ],
            [
                'full_name.required' => 'El nombre completo es requerido',
                'email.required' => 'El email es requerido',
                'phone.required' => 'El teléfono es requerido',
                'message.required' => 'El mensaje es requerido',
                'full_name.max' => 'La cantidad maxima es de 255 caracteres',
                'email.max' => 'La cantidad maxima es de 255 caracteres',
                'phone.max' => 'La cantidad maxima es de 255 caracteres',
            ]
        );

        CmsSubscriber::create([
            'full_name'     => $request->get('full_name') ?? null,
            'email'         => $request->get('email'),
            'phone'         => $request->get('phone') ?? null,
            'client_ip'     => $request->ip(),
            'read'          => 0,
            'subject'       => $request->get('subject') ?? null,
            'message'       => $request->get('message') ?? null,
        ]);

        return to_route('index_main');
    }
    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('cms::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    public function list_subscribers()
    {
        //dd(request()->input('dates'));
        $subscribers = (new CmsSubscriber())->newQuery();
        $subscribers->orderBy('created_at', 'desc'); // Ordenar por la columna "created_at" de forma descendente

        if (request()->has('search')) {
            $subscribers->where('email', 'like', '%' . request()->input('search') . '%');
        }

        if (request()->has('dates')) {
            $dates = request()->input('dates');
            if ($dates) {
                if (str_contains($dates, ' to ') || str_contains($dates, ' a ')) {
                    $separator = str_contains($dates, ' to ') ? ' to ' : ' a ';
                    [$startDate, $endDate] = explode($separator, $dates);
                    $subscribers->whereDate('created_at', '>=', Carbon::parse($startDate)->startOfDay())
                                ->whereDate('created_at', '<=', Carbon::parse($endDate)->endOfDay());
                } else {
                    $subscribers->whereDate('created_at', Carbon::parse($dates)->toDateString());
                }
            }
        }

        $subscribers = $subscribers->paginate(20)
            ->onEachSide(2)
            ->appends(request()->query());

        return Inertia::render('CMS::Subscribers/List', [
            'subscribers' => $subscribers,
            'filters' => request()->all(['search', 'dates'])
        ]);
    }

    public function exportSubscribersExcel(Request $request)
    {
        $filters = [
            'dates' => $request->input('dates'),
            'search' => $request->input('search'),
        ];

        $excelExportJob = ExcelExportJob::create([
            'user_id' => auth()->id(),
            'report_type' => 'cms_subscribers',
            'status' => 'pending',
            'filters' => json_encode($filters),
        ]);

        ExportCmsSubscribersExcel::dispatch(auth()->id(), $excelExportJob->id, $request->input('dates'), $request->input('search'));

        return response()->json([
            'message' => 'La exportación de Excel ha sido iniciada. Por favor, espere un momento.',
            'job_id' => $excelExportJob->id
        ], 202);
    }

    public function exportSubscribersExcelStatus($jobId)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $excelExportJob = ExcelExportJob::where('id', $jobId)
                            ->where('user_id', auth()->id())
                            ->first();

        if (!$excelExportJob) {
            return response()->json(['message' => 'Estado de exportación no encontrado o no autorizado.'], 404);
        }

        return response()->json($excelExportJob);
    }
}
