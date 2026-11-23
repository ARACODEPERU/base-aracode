<?php

namespace Modules\CMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Academic\Entities\AcaCourse;
use Modules\CMS\Entities\CmsLanding;
use Modules\CMS\Entities\CmsSubscriber;
use Modules\CMS\Support\FreeCourseTopics;

/**
 * Landing pública de cursos gratis: capta los datos del visitante junto con el
 * curso que quiere y los temas que le interesan.
 *
 * El contenido y los cursos que se ofrecen los configura el administrador en
 * /cms/one/free/course/{menu_id} (OneFreeCourseController).
 */
class OneFreeCoursePublicController extends Controller
{
    /**
     * Paso 1 (datos y curso gratis) y paso 2 (temas de interés).
     */
    public function show(Request $request, string $menuId): View
    {
        return $this->render($this->landingOrFail($menuId), $request->boolean('enviado'), false);
    }

    /**
     * Vista previa para el panel: exactamente el mismo landing, pero el
     * formulario no envia nada. Sirve para revisar el diseno antes de publicar.
     */
    public function preview(string $menuId): View
    {
        // En la vista previa se permite un landing guardado pero todavia
        // desactivado: el administrador quiere verlo antes de publicarlo.
        return $this->render($this->landingOrFail($menuId, false), false, true);
    }

    /**
     * Renderiza el landing con o sin vista previa.
     */
    private function render(CmsLanding $landing, bool $enviado, bool $preview): View
    {
        return view('aracode.pages.free-course-landing', [
            'landing' => $landing,
            'cursos' => $this->cursosGratis($landing),
            'cupo' => $this->cupoDeCursos($landing),
            'temas' => FreeCourseTopics::paraVista(),
            'enviado' => $enviado,
            'preview' => $preview,
        ]);
    }

    /**
     * Guarda al visitante con el curso elegido en el paso 1 y los temas del
     * paso 2. El registro se ve luego en la pestaña Registros del panel.
     */
    public function store(Request $request, string $menuId): RedirectResponse
    {
        $landing = $this->landingOrFail($menuId);

        $cursos = $this->cursosGratis($landing);

        if ($cursos->isEmpty()) {
            return back()->withInput()->withErrors([
                'course_id' => 'Este landing todavía no tiene cursos gratis configurados.',
            ]);
        }

        // Cupo configurado por el administrador: cuantos cursos gratis puede
        // elegir como maximo cada persona de todos los que se muestran.
        $cupo = $this->cupoDeCursos($landing);

        $validado = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+()\s-]{6,20}$/'],
            'courses' => ['required', 'array', 'min:1', 'max:' . $cupo],
            'courses.*' => ['integer', Rule::in($cursos->pluck('id')->all())],
            'interests' => ['required', 'array', 'min:1'],
            'interests.*' => ['string', Rule::in(FreeCourseTopics::claves())],
        ], [
            'full_name.required' => 'Escribe tu nombre completo.',
            'email.required' => 'Necesitamos tu correo para enviarte el acceso.',
            'email.email' => 'Escribe un correo electrónico válido.',
            'phone.required' => 'Necesitamos tu teléfono para contactarte.',
            'phone.regex' => 'El teléfono solo debe tener números, con un mínimo de 6 dígitos.',
            'courses.required' => 'Elige al menos un curso gratis.',
            'courses.min' => 'Elige al menos un curso gratis.',
            'courses.max' => 'En este landing puedes elegir hasta ' . $cupo . ' curso(s) gratis.',
            'courses.*.in' => 'Uno de los cursos elegidos no está disponible en este landing.',
            'interests.required' => 'Marca al menos un tema de tu interés.',
            'interests.min' => 'Marca al menos un tema de tu interés.',
            'interests.*.in' => 'Uno de los temas elegidos no es válido.',
        ]);

        // Cursos elegidos, en el orden en que se muestran en la landing.
        $elegidos = $cursos
            ->whereIn('id', array_map('intval', $validado['courses']))
            ->pluck('description')
            ->all();

        CmsSubscriber::create([
            'full_name' => $validado['full_name'],
            'email' => $validado['email'],
            'phone' => preg_replace('/[^0-9]/', '', $validado['phone']),
            'client_ip' => $request->ip(),
            'read' => 0,
            // El asunto guarda el o los cursos gratis elegidos en el paso 1.
            'subject' => implode(', ', $elegidos),
            'interests' => array_values(array_unique($validado['interests'])),
            'utm_source' => $request->get('utm_source'),
            'utm_medium' => $request->get('utm_medium'),
            // Sin campaña en la URL se guarda la del landing (la misma que usa
            // el panel en defaultUtmCampaign) para que el registro aparezca en
            // Registros y en sus estadísticas, venga o no con UTM.
            'utm_campaign' => $request->get('utm_campaign') ?: $this->campaignForLanding($landing),
            'utm_term' => $request->get('utm_term'),
            'utm_content' => $request->get('utm_content'),
            'utm_id' => $request->get('utm_id'),
            'fbclid' => $request->get('fbclid'),
            'gclid' => $request->get('gclid'),
            'referer' => $request->get('referer') ?: $request->headers->get('referer'),
            'landing_url' => $request->get('landing_url') ?: $request->fullUrl(),
            'traffic_source' => $request->get('traffic_source'),
        ]);

        return redirect()
            ->route('cms_free_course_landing', ['menu_id' => $landing->menu_id, 'enviado' => 1]);
    }

    /**
     * Maximo de cursos gratis que puede elegir una persona (1 a 3).
     */
    private function cupoDeCursos(CmsLanding $landing): int
    {
        return max(1, min(3, (int) ($landing->free_courses_count ?: 1)));
    }

    /**
     * Campaña del landing cuando el visitante no llega con utm_campaign: el
     * panel agrupa los leads por esta campaña.
     */
    private function campaignForLanding(CmsLanding $landing): string
    {
        $campaign = trim((string) ($landing->utm_config['campaign'] ?? ''));

        if ($campaign !== '') {
            return $campaign;
        }

        $slug = trim((string) ($landing->slug ?? ''));

        return 'landing_' . str_replace('-', '_', $slug !== '' ? $slug : 'curso_gratis');
    }

    /**
     * Landing publicada para ese menú. Si no existe o está desactivada, 404.
     */
    private function landingOrFail(string $menuId, bool $exigirActiva = true): CmsLanding
    {
        $landing = CmsLanding::where('menu_id', $menuId)->first();

        $disponible = $landing && (! $exigirActiva || $landing->status);

        abort_if(! $disponible, 404, 'Esta landing no está disponible.');

        return $landing;
    }

    /**
     * Cursos gratis configurados por el administrador, sin repetir y en el
     * orden en que los eligió.
     */
    private function cursosGratis(CmsLanding $landing)
    {
        $ids = collect($landing->data_related['items'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return AcaCourse::whereIn('id', $ids->all())
            ->get(['id', 'description'])
            ->sortBy(fn ($curso) => $ids->search((int) $curso->id))
            ->values();
    }
}
