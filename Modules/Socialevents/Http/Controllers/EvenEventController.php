<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaTeacher;
use Modules\Socialevents\Entities\EvenCategory;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EvenLocal;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Socialevents\Entities\EvenEventExhibitor;
use Modules\Socialevents\Entities\EvenEventLocal;
use Modules\Socialevents\Entities\EvenTicketType;
use Modules\Socialevents\Services\SportsOrganizationService;

class EvenEventController extends Controller
{
    use ValidatesRequests;

    protected $P000010; ///token Tiny

    public function __construct()
    {
        $this->P000010  = Parameter::where('parameter_code', 'P000010')->value('value_default');
    }
    public function index()
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanRead(request()->user());
        $events = (new EvenEvent())->newQuery()
            ->when(
                $sports->sportsMode() && ! $sports->isSuperAdmin(request()->user()),
                fn ($query) => $query->where('organization_id', $sports->organizationId(request()->user()))
            );

        if (request()->has('search')) {
            $events->where('title', 'Like', '%' . request()->input('search') . '%');
        }
        $events = $events->with('exhibitors.exhibitor');
        $events = $events->with('locales.local');
        $events = $events->with('prices');
        $events = $events->orderBy('id', 'DESC');
        $events = $events->paginate(10);

        $types = EvenTicketType::all();

        return Inertia::render('Socialevents::Events/List', [
            'sportsMode' => $sports->sportsMode(),
            'socialevents' => $events,
            'filters' => request()->all('search'),
            'types' => $types
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite(request()->user());
        $categories = $sports->sportsMode()
            ? collect()
            : EvenCategory::with('subcategories.subcategories')->where('status', true)->whereNull('main_category_id')->get();
        //$instructors = AcaTeacher::with('person')->get();
        $instructors = Person::all();

        $locales = EvenLocal::where('status', true)->get();

        return Inertia::render('Socialevents::Events/Create', [
            'sportsMode' => $sports->sportsMode(),
            'categories' => $categories,
            'instructors'   => $instructors,
            'locales' => $locales
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
        $this->validate(
            $request,
            [
                'category_id' => $sports->sportsMode() ? 'nullable' : 'required',
                'title' => 'required',
                'description' => 'required',
                'advertising_video' => 'required',
                'tickets_quantity' => 'required',
                'date' => 'required'
            ]
        );
        //dd($request->all());
        $ds = $request->get('date')[0]['$d'];
        $de = $request->get('date')[1]['$d'];
        $days = Carbon::parse($ds)->diffInDays(Carbon::parse($de));

        $event = EvenEvent::create([
            'organization_id' => $sports->sportsMode() ? ($sports->isSuperAdmin($request->user()) ? $sports->legacyOrganizationId() : $sports->organizationId($request->user())) : null,
            'category_id' => $sports->sportsMode() ? $sports->sportsCategoryId() : $request->get('category_id'),
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'iframe_transmission' => $request->get('iframe_transmission'),
            'advertising_video' => $request->get('advertising_video'),
            'date_start' => Carbon::parse($ds)->format('Y-m-d'),
            'date_end'  => Carbon::parse($de)->format('Y-m-d'),
            'number_days'   => $days + 1,
            'tickets_quantity' => $request->get('tickets_quantity'),
            'status' => 'PE',
            'broadcast' => $request->get('broadcast') ? true : false,
            'tickets_available' => $request->get('tickets_quantity')
        ]);


        $locales = $request->get('locales') ?? [];
        $exhibitors = $request->get('exhibitors') ?? [];

        if (count($locales) > 0) {
            foreach ($locales as $local) {
                EvenEventLocal::create([
                    'event_id' => $event->id,
                    'local_id' => $local,
                ]);
            }
        }

        if ($exhibitors && count($exhibitors) > 0) {
            foreach ($exhibitors as $exhibitor) {
                EvenEventExhibitor::create([
                    'event_id' => $event->id,
                    'exhibitor_id' => $exhibitor
                ]);
            }
        }

        $path = null;
        $destination = 'uploads/eventos/events';
        $base64Image = $request->get('image1');
        if ($base64Image) {
            if (!Storage::exists($destination)) {
                Storage::makeDirectory($destination, 0755, true);
            }
            try {
                $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
                if (PHP_OS == 'WINNT') {
                    $tempFile = tempnam(sys_get_temp_dir(), 'img');
                } else {
                    $tempFile = tempnam('/var/www/html/img_temp', 'img');
                }
                //dd($tempFile);
                file_put_contents($tempFile, $fileData);
                $mime = mime_content_type($tempFile);

                $name = uniqid('', true) . '.' . str_replace('image/', '', $mime);
                $file = new UploadedFile(realpath($tempFile), $name, $mime, null, true);

                if ($file) {
                    $original_name = strtolower(trim($file->getClientOriginalName()));
                    $original_name = str_replace(" ", "_", $original_name);
                    $extension = $file->getClientOriginalExtension();
                    $file_name = date('YmdHis') . '.' . $extension;
                    $path = Storage::disk('public')->putFileAs($destination, $file, $file_name);
                }
            } catch (\Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException $e) {
                dd($e->getMessage());
            }

            $event->image1 = $path;
            $event->save();
        }

        return to_route('even_eventos_list');
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('socialevents::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite(request()->user());
        $sports->assertOwnedEvent(request()->user(), (int) $id);
        $event = EvenEvent::query()->findOrFail($id);
        $categories = $sports->sportsMode() ? collect() : EvenCategory::where('status', true)->get();
        //$instructors = AcaTeacher::with('person')->get();
        $instructors = Person::all();
        $locales = EvenLocal::where('status', true)->get();
        $eventLocales = EvenEventLocal::where('event_id', $id)->pluck('local_id');
        $eventExhibitors = EvenEventExhibitor::where('event_id', $id)->pluck('exhibitor_id');

        return Inertia::render('Socialevents::Events/Edit', [
            'sportsMode' => $sports->sportsMode(),
            'categories' => $categories,
            'instructors'   => $instructors,
            'locales' => $locales,
            'socialevent' => $event,
            'eventLocales' => $eventLocales,
            'eventExhibitors' => $eventExhibitors
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request): RedirectResponse
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
        $this->validate(
            $request,
            [
                'category_id' => $sports->sportsMode() ? 'nullable' : 'required',
                'title' => 'required',
                'description' => 'required',
                'advertising_video' => 'required',
                'tickets_quantity' => 'required',
                'date' => 'required'
            ]
        );

        $sports->assertOwnedEvent($request->user(), (int) $request->get('id'));
        $event = EvenEvent::query()->findOrFail($request->get('id'));
        [$ds, $de] = $this->parseEventDateRange($request);
        $days = Carbon::parse($ds)->diffInDays(Carbon::parse($de));

        $event->update([
            'category_id' => $sports->sportsMode() ? $sports->sportsCategoryId() : $request->get('category_id'),
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'iframe_transmission' => $request->get('iframe_transmission'),
            'advertising_video' => $request->get('advertising_video'),
            'date_start' => Carbon::parse($ds)->format('Y-m-d'),
            'date_end'  => Carbon::parse($de)->format('Y-m-d'),
            'number_days'   => $days + 1,
            'tickets_quantity' => $request->get('tickets_quantity'),
            'broadcast' => $request->get('broadcast') ? true : false,
            'tickets_available' => $request->get('tickets_quantity')
        ]);

        EvenEventLocal::where('event_id', $event->id)->delete();
        EvenEventExhibitor::where('event_id', $event->id)->delete();

        $locales = $request->get('locales') ?? [];
        $exhibitors = $request->get('exhibitors') ?? [];

        if (count($locales) > 0) {
            foreach ($locales as $local) {
                EvenEventLocal::create([
                    'event_id' => $event->id,
                    'local_id' => $local,
                ]);
            }
        }

        if (count($exhibitors) > 0) {
            foreach ($exhibitors as $exhibitor) {
                EvenEventExhibitor::create([
                    'event_id' => $event->id,
                    'exhibitor_id' => $exhibitor
                ]);
            }
        }

        $path = $request->get('image1_preview');
        $destination = 'uploads/eventos/events';
        $base64Image = $request->get('image1');
        if ($base64Image) {
            if (!Storage::exists($destination)) {
                Storage::makeDirectory($destination, 0755, true);
            }
            try {
                $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
                if (PHP_OS == 'WINNT') {
                    $tempFile = tempnam(sys_get_temp_dir(), 'img');
                } else {
                    $tempFile = tempnam('/var/www/html/img_temp', 'img');
                }

                file_put_contents($tempFile, $fileData);
                $mime = mime_content_type($tempFile);

                $name = uniqid('', true) . '.' . str_replace('image/', '', $mime);
                $file = new UploadedFile(realpath($tempFile), $name, $mime, null, true);

                if ($file) {
                    $original_name = strtolower(trim($file->getClientOriginalName()));
                    $original_name = str_replace(" ", "_", $original_name);
                    $extension = $file->getClientOriginalExtension();
                    $file_name = date('YmdHis') . '.' . $extension;
                    $path = Storage::disk('public')->putFileAs($destination, $file, $file_name);
                }
                $event->image1 = $path;
                $event->save();
            } catch (\Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException $e) {
                dd($e->getMessage());
            }
        }

        return to_route('even_eventos_list');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseEventDateRange(Request $request): array
    {
        $date = $request->get('date');

        if (is_array($date) && isset($date[0]['$d'], $date[1]['$d'])) {
            return [$date[0]['$d'], $date[1]['$d']];
        }

        if (is_array($date) && count($date) >= 2) {
            $start = $date[0];
            $end = $date[1];
            $ds = is_array($start) ? ($start['$d'] ?? $start[0] ?? null) : $start;
            $de = is_array($end) ? ($end['$d'] ?? $end[0] ?? null) : $end;

            if ($ds && $de) {
                return [(string) $ds, (string) $de];
            }
        }

        abort(422, 'Formato de fechas inválido.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $message = null;
        $success = false;
        try {
            // Usamos una transacción para asegurarnos de que la operación se realice de manera segura.
            DB::beginTransaction();

            // Verificamos si existe.
            $item = EvenEvent::findOrFail($id);
            $sports = app(SportsOrganizationService::class);
            $sports->assertCanWrite(request()->user());
            $sports->assertOwnedEvent(request()->user(), (int) $item->id);

            // Si no hay detalles asociados, eliminamos.
            $item->delete();

            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();

            $message =  'Evento eliminada correctamente';
            $success = true;
        } catch (\Exception $e) {
            // Si ocurre alguna excepción durante la transacción, hacemos rollback para deshacer cualquier cambio.
            DB::rollback();
            $success = false;
            $message = $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'message' => $message
        ]);
    }
}
