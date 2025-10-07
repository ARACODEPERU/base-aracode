<?php

namespace Modules\CMS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCourse;
use Modules\CMS\Entities\CmsLanding;
use Modules\CMS\Http\Requests\CmsLandingRequest;

class OneFreeCourseController extends Controller
{
    protected $P000010; ///token Tiny

    public function __construct()
    {
        $this->P000010  = Parameter::where('parameter_code', 'P000010')->value('value_default');
    }

    public function index($id)
    {
        $courses = AcaCourse::where('type_description','<>','Programas de Especialización')->get();

        $landingPage = CmsLanding::where('menu_id', $id)->first();

        return Inertia::render('CMS::Landings/CourseFree',[
            'courses' => $courses,
            'tinykey' => $this->P000010,
            'landingPage' => $landingPage
        ]);
    }

    public function store(CmsLandingRequest $request): RedirectResponse
    {
        $menu = $request->get('menu_id');
        // $menu = '01';
        try {
            // Verificamos si viene una imagen
            $mainImagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $filename = date('YmdHis') . '.' . $extension;

                // lo guarda en storage/app/public/uploads/cmsLanding con el nombre personalizado
                $mainImagePath = $file->storeAs('uploads/cmsLanding', $filename, 'public');
            }
            //dd($request->get('landing_id'));
            if (!$request->filled('landing_id')) {

                // === CREAR NUEVO ===
                CmsLanding::create([
                    'title'             => $request->get('title'),
                    'slug'              => $request->get('slug'),
                    'description_short' => $request->get('description_short'),
                    'description_long'  => $request->get('description_long'),
                    'main_image'        => $mainImagePath,
                    'data_related'      => $request->get('data_related') ?? null,
                    'data_related2'     => $request->get('data_related2') ?? null,
                    'additional_field1' => $request->get('additional_field1'),
                    'additional_field2' => $request->get('additional_field2'),
                    'additional_field3' => $request->get('additional_field3'),
                    'date_start'        => $request->get('date_start') ?? null,
                    'date_end'          => $request->get('date_end') ?? null,
                    'status'            => $request->get('status', 1),
                    'menu_id'           => $menu
                ]);

                return to_route('cms_landing_course_free', $menu)
                    ->with('success', 'Landing creada correctamente.');
            } else {
                // === ACTUALIZAR ===
                $landing = CmsLanding::findOrFail($request->get('landing_id'));

                if ($mainImagePath && $landing->main_image) {
                    Storage::disk('public')->delete($landing->main_image);
                }

                $landing->update([
                    'title'             => $request->get('title'),
                    'slug'              => $request->get('slug'),
                    'description_short' => $request->get('description_short'),
                    'description_long'  => $request->get('description_long'),
                    'main_image'        => $mainImagePath ?? $landing->main_image,
                    'data_related'      => $request->get('data_related') ?? null,
                    'data_related2'     => $request->get('data_related2') ?? null,
                    'additional_field1' => $request->get('additional_field1'),
                    'additional_field2' => $request->get('additional_field2'),
                    'additional_field3' => $request->get('additional_field3'),
                    'date_start'        => $request->get('date_start') ?? null,
                    'date_end'          => $request->get('date_end') ?? null,
                    'status'            => $request->get('status', 1),
                ]);

                return to_route('cms_landing_course_free', $menu)
                    ->with('success', 'Landing actualizada correctamente.');
            }

        } catch (\Exception $e) {
            return to_route('cms_landing_course_free', $menu)
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('cms::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('cms::edit');
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
