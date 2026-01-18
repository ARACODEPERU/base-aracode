<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Socialevents\Entities\EventTeam;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EventTeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $teams = EventTeam::with(['manager', 'editions', 'players'])
            ->orderBy('name')
            ->paginate(20);
        //dd($teams);
        return Inertia::render('Socialevents::Teams/List',[
            'teams' => $teams
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();

        $documentTypes = DB::table('identity_document_type')->whereNotIn('id',['6'])->get();

        return Inertia::render('Socialevents::Teams/Create',[
            'ubigeo' => $ubigeo,
            'documentTypes' => $documentTypes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->validate(
            $request,
            [
                'name'   => 'required|max:255|string',
                'short_name'   => 'required|max:10|string'
            ]
        );

        $logoPath = null;

        $team = EventTeam::create([
            'name' => $request->get('name'),
            'short_name' => $request->get('short_name'),
            'ubigeo' => $request->get('ubigeo'),
            'ubigeo_description' => $request->get('ubigeo_description'),
            'manager_id' => $request->get('manager_id'),
            'champion' => false,
            'status' => true
        ]);

        $destination = 'uploads/eventos/equipos';
        $base64Image = $request->get('logo_path');

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
                    $file_name = $team->id . '.' . $extension;
                    $logoPath = Storage::disk('public')->putFileAs($destination, $file, $file_name);
                }
            } catch (\Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException $e) {
                //dd($e->getMessage());
                throw new \Exception("Error en campo 'Imagen': '{$e->getMessage()}'");
            }
        }

        $team->logo_path = $logoPath;
        $team->save();

        return to_route('even_equipos_listado');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();

        $documentTypes = DB::table('identity_document_type')->whereNotIn('id',['6'])->get();

        $team = EventTeam::with('manager')->where('id', $id)->first();
        return Inertia::render('Socialevents::Teams/Edit',[
            'ubigeo' => $ubigeo,
            'documentTypes' => $documentTypes,
            'team' => $team
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $id = $request->get('id');

        $this->validate(
            $request,
            [
                'name'   => 'required|max:255|string',
                'short_name'   => 'required|max:10|string'
            ]
        );

        $logoPath = null;

        $team = EventTeam::find($id);

        $team->update([
            'name' => $request->get('name'),
            'short_name' => $request->get('short_name'),
            'ubigeo' => $request->get('ubigeo'),
            'ubigeo_description' => $request->get('ubigeo_description'),
            'manager_id' => $request->get('manager_id'),
            'champion' => $request->get('champion'),
            'status' => $request->get('status') ? true : false
        ]);

        $destination = 'uploads/eventos/equipos';
        $base64Image = $request->get('logo_path');

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
                    $file_name = $team->id . '.' . $extension;
                    $logoPath = Storage::disk('public')->putFileAs($destination, $file, $file_name);
                }
            } catch (\Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException $e) {
                //dd($e->getMessage());
                throw new \Exception("Error en campo 'Imagen': '{$e->getMessage()}'");
            }
        }

        $team->logo_path = $logoPath;
        $team->save();
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
            $item = EventTeam::findOrFail($id);

            // Si no hay detalles asociados, eliminamos.
            $item->delete();

            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();

            $message =  'Eliminado correctamente';
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
