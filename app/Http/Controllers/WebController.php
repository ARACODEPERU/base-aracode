<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\District;
use App\Models\Province;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Onlineshop\Entities\OnliItem;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;

use Intervention\Image\Facades\Image;
use Intervention\Image\Font;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Modules\CMS\Entities\CmsItem;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EvenEventTicketClient;
use Modules\CMS\Entities\CmsSection;
use Modules\CMS\Entities\CmsSectionItem;
use Modules\Socialevents\Entities\EvenEventDonation;
use App\Mail\ConfirmTicketEventMailable;
use Illuminate\Support\Facades\Mail;

class WebController extends Controller
{

    public function index()
    {
        $home = CmsSection::where('component_id', 'intro_home_18')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $sliders = CmsSection::where('component_id', 'slider_home_2')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $reuniones = CmsSectionItem::with('item.items')->where('section_id', 19)  //cambiar el id de la seccion cuando la seccion se forma en grupo
            ->orderBy('position')
            ->get();

        $bible = CmsSection::where('component_id', 'home_texto_biblico_20')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $ministerios = CmsSectionItem::with('item.items')->where('section_id', 21)  //cambiar el id de la seccion cuando la seccion se forma en grupo
            ->orderBy('position')
            ->get();

        $gods_meeting = CmsSection::where('component_id', 'home_un_encuentro_con_dios_22')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $subs = CmsSection::where('component_id', 'home_suscripcion_23')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $difusion = CmsSection::where('component_id', 'canales_de_difusion_sede_principal_43')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        return view(('jrrss/index'), [
            'home' => $home,
            'sliders' => $sliders,
            'reuniones' => $reuniones,
            'bible' => $bible,
            'ministerios' => $ministerios,
            'gods_meeting' => $gods_meeting,
            'subs' => $subs,
            'difusion' => $difusion
        ]);
    }

    public function quienessomos()
    {
        $resenas = CmsSectionItem::with('item.items')->where('section_id', 17)  //cambiar el id de la seccion
            ->orderBy('position')
            ->get();

        $parallax = CmsSection::where('component_id', 'quienes_somos_parallax_16')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $visions = CmsSection::where('component_id', 'quienes_somos_la_vision_en_accion_15')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $presentacion = CmsSection::where('component_id', 'quienes_somos_la_vision_39')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $banner = CmsSection::where('component_id', 'banner_quienes_somos_4')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        //dd($presentacion);
        return view('jrrss/quienes-somos', [
            'resenas' => $resenas,
            'parallax' => $parallax,
            'visions' => $visions,
            'presentacion' => $presentacion,
            'banner' => $banner
        ]);
    }

    public function predicas()
    {
        $banner = CmsSection::where('component_id', 'banner_predicas_79')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $group_video = CmsSection::where('component_id', 'predicas_videoteca_80')->first();

        $videos = CmsSectionItem::with(['group' => function ($query) {
            $query->where('type_id', 5);
        }, 'group.items'])
            ->where('section_id', $group_video->id)
            ->paginate(25);
        //dd($videos);

        return view('jrrss/predicas', [
            'banner' => $banner,
            'videos' => $videos
        ]);
    }

    public function sedes()
    {
        $banner = CmsSection::where('component_id', 'banner_sedes_5')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $sedes = CmsSectionItem::with('item.items')->where('section_id', 24)  //cambiar el id de la seccion ->sedes ubicacion 24
            ->orderBy('position')
            ->get();


        return view('jrrss/sedes', [
            'banner' => $banner,
            'sedes' => $sedes,
        ]);
    }

    public function sedesperu()
    {
        $banner = CmsSection::where('component_id', 'sedes_banner_peru_45')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $sedes = CmsSectionItem::with('item.items')->where('section_id', 44)  //cambiar el id de la seccion ->sedes ubicacion 24
            ->orderBy('position')
            ->get();


        return view('jrrss/sedes-peru', [
            'banner' => $banner,
            'sedes' => $sedes,
        ]);
    }

    public function sedesestadosunidos()
    {
        $banner = CmsSection::where('component_id', 'sedes_banner_estados_unidos_46')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $sedes = CmsSectionItem::with('item.items')->where('section_id', 47)  //cambiar el id de la seccion ->sedes ubicacion 24
            ->orderBy('position')
            ->get();


        return view('jrrss/sedes-estados-unidos', [
            'banner' => $banner,
            'sedes' => $sedes,
        ]);
    }

    public function sedesespaña()
    {
        $banner = CmsSection::where('component_id', 'sedes_banner_espana_48')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $sedes = CmsSectionItem::with('item.items')->where('section_id', 49)  //cambiar el id de la seccion ->sedes ubicacion 24
            ->orderBy('position')
            ->get();


        return view('jrrss/sedes-estados-unidos', [
            'banner' => $banner,
            'sedes' => $sedes,
        ]);
    }

    public function coberturas()
    {
        $banner = CmsSection::where('component_id', 'banner_cobertura_6')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $coberturas = CmsSectionItem::with('item.items')->where('section_id', 25)  //cambiar el id de la seccioc
            ->orderBy('position')
            ->get();

        return view('jrrss/cobertura', [
            'banner' => $banner,
            'coberturas' => $coberturas,
        ]);
    }

    public function coberturasperu()
    {
        $banner = CmsSection::where('component_id', 'banner_cobertura_peru_51')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $coberturas = CmsSectionItem::with('item.items')->where('section_id', 52)  //cambiar el id de la seccion ->coberturas ubicacion 24
            ->orderBy('position')
            ->get();


        return view('jrrss/coberturas-peru', [
            'banner' => $banner,
            'coberturas' => $coberturas,
        ]);
    }

    public function eventos()
    {
        $events = EvenEvent::with('exhibitors.exhibitor')
            ->with('category')
            ->with('prices.type')
            ->where('status', 'PE')
            ->orderBy('date_start', 'DESC')
            ->take(3) // Limitar la consulta a los últimos 3 eventos
            ->get();

        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name, ' - ',provinces.name,' - ',districts.name) AS city_name")
            )
            ->get();
        //el banner es extraido del contenido de EVENTOS y ya no del banner asi que imagino que este codigo ya no es necesario
        // $banner = CmsSection::where('component_id', 'banner_eventos_7')  //siempre cambiar el id del componente
        // ->join('cms_section_items', 'section_id', 'cms_sections.id')
        // ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
        // ->select(
        //     'cms_items.content',
        //     'cms_section_items.position'
        // )
        // ->orderBy('cms_section_items.position')
        // ->first();

        return view('jrrss/eventos', [
            'events' => $events,
            'ubigeo' => $ubigeo,
        ]);
    }

    public function eventospagar($id)
    {

        $ticket = EvenEventTicketClient::with('event')
            ->with('type')
            ->where('id', $id)
            ->where('status', false)
            ->first();

        $preference_id = null;

        if ($ticket) {
            try {
                MercadoPagoConfig::setAccessToken(env('MERCADOPAGO_TOKEN'));
                $client = new PreferenceClient();
                //dd($mp_items);
                $preference = $client->create([
                    "items" => array(
                        array(
                            'id' => $ticket->event->id,
                            'category_id' => $ticket->type->type_id,
                            'title' => $ticket->event->title,
                            'quantity'      => $ticket->quantity,
                            'currency_id'   => 'PEN',
                            'unit_price'    => floatval($ticket->type->price)
                        )
                    ),
                    "back_urls" =>  array(
                        'success' => route('web_gracias', $ticket->id),
                        // 'failure' => route('onlineshop_response_mercadopago'),
                        // 'pending' => route('onlineshop_response_mercadopago')
                    )
                ]);
                $preference_id =  $preference->id;
            } catch (\MercadoPago\Exceptions\MPApiException $e) {
                // Manejar la excepción
                $response = $e->getApiResponse();
                dd($response); // Mostrar la respuesta para obtener más detalles
            }
        }
        return view('jrrss/eventos-pagar', [
            'ticket' => $ticket,
            'preference_id' => $preference_id
        ]);
    }


    public function entrada($id)
    {
        $preference_id  =  null;
        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name, ' - ',provinces.name,' - ',districts.name) AS city_name")
            )
            ->get();
        return view('jrrss/comprar-entrada', [
            'preference_id' => $preference_id,
            'ubigeo' => $ubigeo,
            'event'  => EvenEvent::find($id)
        ]);
    }

    public function processPayment(Request $request, $id)
    {
        MercadoPagoConfig::setAccessToken(env('MERCADOPAGO_TOKEN'));

        $client = new PaymentClient();
        try {
            $payment = $client->create([
                "token" => $request->get('token'),
                "issuer_id" => $request->get('issuer_id'),
                "payment_method_id" => $request->get('payment_method_id'),
                "transaction_amount" => (float) $request->get('transaction_amount'),
                "installments" => $request->get('installments'),
                "payer" => $request->get('payer')
            ]);

            if ($payment->status == 'approved') {
                $ticket = EvenEventTicketClient::where('id', $id)
                    ->where('status', false)
                    ->first();

                $ticket->status = true;
                $ticket->response_status = $request->get('collection_status');
                $ticket->response_id = $request->get('collection_id');
                $ticket->response_date_approved = Carbon::now()->format('Y-m-d');
                $ticket->response_payer = json_encode($request->all());
                $ticket->response_payment_method_id = $request->get('payment_type');
                $ticket->save();

                //envio de correo
                try {
                    Mail::to($ticket->email)->send(new ConfirmTicketEventMailable($ticket));
                } catch (\Throwable $th) {
                    \Log::error('Error al encolar el correo de confirmación en WebController.php : ' . $th->getMessage(), [
                        'exception' => $th,
                        'ticket_id' => $ticket->id,
                        'email' => $ticket->email,
                    ]);
                }

                return response()->json([
                    'status' => $payment->status,
                    'message' => $payment->status_detail,
                    'url' => route('web_gracias_por_comprar_tu_entrada', $ticket->id)
                ]);
            } else {

                return response()->json([
                    'status' => $payment->status,
                    'message' => $payment->status_detail,
                    'url' => route('web_eventos_pagar', $id)
                ]);
            }
        } catch (\MercadoPago\Exceptions\MPApiException $e) {
            // Manejar la excepción
            $response = $e->getApiResponse();
            $content  = $response->getContent();

            $message = $content['message'];
            return response()->json(['error' => 'Error al procesar el pago: ' . $message], 412);
        }
    }

    public function gracias(Request $request, $id)
    {
        $ticket = EvenEventTicketClient::where('id', $id)
            ->where('status', false)
            ->first();

        $ticket->status = true;
        $ticket->response_status = $request->get('collection_status');
        $ticket->response_id = $request->get('collection_id');
        $ticket->response_date_approved = Carbon::now()->format('Y-m-d');
        $ticket->response_payer = json_encode($request->all());
        $ticket->response_payment_method_id = $request->get('payment_type');
        $ticket->save();
        // $ticket = $ticket->with('event')->with('type');
    }


    public function promiembro()
    {
        $banner = CmsSection::where('component_id', 'banner_proceso_de_miembro_60')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $presentacion = CmsSection::where('component_id', 'ism_proceso_de_miembro_presentacion_61')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $group_galery = CmsSection::where('component_id', 'ism_proceso_de_miembro_galeria_62')->first();

        $galery = CmsSectionItem::with(['group' => function ($query)
            {
                $query->where('type_id', 5);
            }, 'group.items'])
            ->where('section_id', $group_galery->id)
            ->paginate(6);

        //dd($presentacion);
        return view('jrrss/ism-proceso-miembro', [
            'banner' => $banner,
            'presentacion' => $presentacion,
            'galery' => $galery
        ]);
    }

    public function prodiscipulo()
    {
        $banner = CmsSection::where('component_id', 'banner_proceso_del_discipulo_63')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $presentacion = CmsSection::where('component_id', 'ism_proceso_del_discipulo_presentacion_64')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $group_galery = CmsSection::where('component_id', 'ism_proceso_del_discipulo_galeria_65')->first();

        $galery = CmsSectionItem::with(['group' => function ($query)
            {
                $query->where('type_id', 5);
            }, 'group.items'])
            ->where('section_id', $group_galery->id)
            ->paginate(6);

        //dd($presentacion);
        return view('jrrss/ism-proceso-del-discipulo', [
            'banner' => $banner,
            'presentacion' => $presentacion,
            'galery' => $galery
        ]);
    }

    public function prolideres()
    {
        $banner = CmsSection::where('component_id', 'banner_proceso_de_lideres_66')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $presentacion = CmsSection::where('component_id', 'ism_proceso_de_lideres_presentacion_67')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $group_galery = CmsSection::where('component_id', 'ism_proceso_de_lideres_galeria_68')->first();

        $galery = CmsSectionItem::with(['group' => function ($query)
            {
                $query->where('type_id', 5);
            }, 'group.items'])
            ->where('section_id', $group_galery->id)
            ->paginate(6);

        //dd($presentacion);
        return view('jrrss/ism-proceso-de-lideres', [
            'banner' => $banner,
            'presentacion' => $presentacion,
            'galery' => $galery
        ]);
    }

    public function ism()
    {
        $banner = CmsSection::where('component_id', 'banner_instituto_sobrenatural_al_mundo_69')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $presentacion = CmsSection::where('component_id', 'instituto_sobrenatural_al_mundo_presentacion_70')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $group_galery = CmsSection::where('component_id', 'instituto_sobrenatural_al_mundo_galeria_71')->first();

        $galery = CmsSectionItem::with(['group' => function ($query)
            {
                $query->where('type_id', 5);
            }, 'group.items'])
            ->where('section_id', $group_galery->id)
            ->paginate(6);

        //dd($presentacion);
        return view('jrrss/ism-proceso-de-lideres', [
            'banner' => $banner,
            'presentacion' => $presentacion,
            'galery' => $galery
        ]);
    }

    public function oracion()
    {
        $banner = CmsSection::where('component_id', 'banner_oracion_72')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $presentacion = CmsSection::where('component_id', 'oracion_presentacion_73')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();


        //dd($presentacion);
        return view('jrrss/oracion', [
            'banner' => $banner,
            'presentacion' => $presentacion
        ]);
    }

    public function empleo()
    {
        $banner = CmsSection::where('component_id', 'banner_empleo_74')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $presentacion = CmsSection::where('component_id', 'empleo_presentacion_75')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();


        //dd($presentacion);
        return view('jrrss/empleo', [
            'banner' => $banner,
            'presentacion' => $presentacion
        ]);
    }

    public function benefactora()
    {
        $banner = CmsSection::where('component_id', 'benefactora_banner_50')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $sedes = CmsSectionItem::with('item.items')->where('section_id', 49)  //cambiar el id de la seccion ->sedes ubicacion 24
            ->orderBy('position')
            ->get();


        return view('jrrss/benefactora', [
            'banner' => $banner,
            'sedes' => $sedes,
        ]);
    }

    public function panes()
    {
        $banner = CmsSection::where('component_id', 'banner_panes_y_peces_53')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $presentacion = CmsSection::where('component_id', 'panes_y_peces_presentacion_54')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $group_galery = CmsSection::where('component_id', 'panes_y_peces_galeria_59')->first();

        $galery = CmsSectionItem::with(['group' => function ($query)
            {
                $query->where('type_id', 5);
            }, 'group.items'])
            ->where('section_id', $group_galery->id)
            ->paginate(6);

        //dd($presentacion);
        return view('jrrss/5-panes-y-2-peces', [
            'banner' => $banner,
            'presentacion' => $presentacion,
            'galery' => $galery
        ]);
    }

    public function samaritano()
    {
        $banner = CmsSection::where('component_id', 'banner_el_buen_samaritano_55')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $presentacion = CmsSection::where('component_id', 'el_buen_samaritano_presentacion_56')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        //dd($presentacion);
        return view('jrrss/el-buen-samaritano', [
            'banner' => $banner,
            'presentacion' => $presentacion
        ]);
    }


    public function sembrando()
    {
        $banner = CmsSection::where('component_id', 'banner_sembrando_sonrisas_57')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $presentacion = CmsSection::where('component_id', 'sembrando_sonrisas_presentacion_58')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        //dd($presentacion);
        return view('jrrss/sembrando-sonrisas', [
            'banner' => $banner,
            'presentacion' => $presentacion
        ]);
    }

    public function escuelasobrenatural()
    {
        $banner = CmsSection::where('component_id', 'banner_escuela_sobrenatural_8')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        return view('jrrss/escuela-sobrenatural', [
            'banner' => $banner,
        ]);
    }

    public function ecelt()
    {
        $banner = CmsSection::where('component_id', 'banner_ecelt_9')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $presentation = CmsSection::where('component_id', 'ecelt_presentacion_26')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $group_galery = CmsSection::where('component_id', 'ecelt_galeria_27')->first();

        $galery = CmsSectionItem::with(['group' => function ($query) {
            $query->where('type_id', 5);
        }, 'group.items'])
            ->where('section_id', $group_galery->id)
            ->paginate(6);

        $biblico = CmsSection::where('component_id', 'ecelt_texto_biblico_28')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $group_video = CmsSection::where('component_id', 'ecelt_videoteca_29')->first();

        $videos = CmsSectionItem::with(['group' => function ($query) {
            $query->where('type_id', 5);
        }, 'group.items'])
            ->where('section_id', $group_video->id)
            ->paginate(6);
        //dd($videos);

        $rsociales = CmsSection::where('component_id', 'ecelt_redes_sociales_40')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        return view('jrrss/ecelt', [
            'banner' => $banner,
            'presentation' => $presentation,
            'galery' => $galery,
            'biblico' => $biblico,
            'videos' => $videos,
            'rsociales' => $rsociales
        ]);
    }

    public function revolucionjuvenil()
    {
        $banner = CmsSection::where('component_id', 'banner_rmnt_10')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $presentacion = CmsSection::where('component_id', 'rmnt_presentacion_30')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();


        $presentacion = CmsSection::where('component_id', 'rmnt_presentacion_30')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();


        // $galeryRmnt = CmsSectionItem::with('item.items')->where('section_id', 31)
        //     ->orderBy('position')
        //     ->get();

        $group_galery = CmsSection::where('component_id', 'rmnt_galeria_31')->first();

        $galeryRmnt = CmsSectionItem::with(['group' => function ($query) {
            $query->where('type_id', 5);
        }, 'group.items'])
            ->where('section_id', $group_galery->id)
            ->paginate(6);

        $rsociales = CmsSection::where('component_id', 'ecelt_redes_sociales_40')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $textBiblie = CmsSection::where('component_id', 'rmnt_texto_biblico_32')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $group_video = CmsSection::where('component_id', 'rmnt_videoteca_33')->first();

        $videoteca = CmsSectionItem::with(['group' => function ($query) {
            $query->where('type_id', 5);
        }, 'group.items'])
            ->where('section_id', $group_video->id)
            ->paginate(4);
        //dd($videos);

        return view('jrrss/revolucion-juvenil', [
            'banner' => $banner,
            'presentacion' => $presentacion,
            'galeryRmnt' => $galeryRmnt,
            'rsociales' => $rsociales,
            'textBiblie' => $textBiblie,
            'videoteca' => $videoteca
        ]);
    }

    public function kids()
    {
        $banner = CmsSection::where('component_id', 'banner_kids_11')  //siempre cambiar el id del componente

            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )

            ->orderBy('cms_section_items.position')
            ->first();

        $presentacion = CmsSection::where('component_id', 'kids_presentacion_34')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $group_galery = CmsSection::where('component_id', 'kids_galeria_35')->first();

        $galeryKids = CmsSectionItem::with(['group' => function ($query) {
            $query->where('type_id', 5);
        }, 'group.items'])
            ->where('section_id', $group_galery->id)
            ->paginate(6);

        $textBiblie = CmsSection::where('component_id', 'kids_texto_biblico_36')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $group_video = CmsSection::where('component_id', 'kids_videoteca_37')->first();

        $videoteca = CmsSectionItem::with(['group' => function ($query) {
            $query->where('type_id', 5);
        }, 'group.items'])
            ->where('section_id', $group_video->id)
            ->paginate(4);
        //dd($videos);


        return view('jrrss/kids', [
            'banner' => $banner,
            'presentacion' => $presentacion,
            'galeryKids' => $galeryKids,
            'textBiblie' => $textBiblie,
            'videoteca' => $videoteca
        ]);
    }

    public function testimonios()
    {
        $banner = CmsSection::where('component_id', 'banner_testimonios_12')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        // $testimonios = CmsSectionItem::with('item.items')->where('section_id', 38)  //cambiar el id de la seccion
        //     ->orderBy('position')
        //     ->get();

        $group_video = CmsSection::where('component_id', 'testimonios_videoteca_38')->first();

        $testimonios = CmsSectionItem::with(['group' => function ($query) {
            $query->where('type_id', 5);
        }, 'group.items'])
            ->where('section_id', $group_video->id)
            ->paginate(6);

        return view('jrrss/testimonios', [
            'banner' => $banner,
            'testimonios' => $testimonios
        ]);
    }

    public function contacto()
    {
        $banner = CmsSection::where('component_id', 'banner_contacto_13')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $difusion = CmsSection::where('component_id', 'canales_de_difusion_sede_principal_43')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $infoformulario = CmsSection::where('component_id', 'contacto_seccion_formulario_76')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $infocontacto = CmsSection::where('component_id', 'contacto_seccion_informacion_77')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $footer = CmsSection::where('component_id', 'footer_area_3')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        return view('jrrss/contacto', [
            'banner' => $banner,
            'difusion' => $difusion,
            'infoformulario' => $infoformulario,
            'infocontacto' => $infocontacto,
            'footer' => $footer
        ]);
    }

    public function donar()
    {
        $banner = CmsSection::where('component_id', 'banner_donar_41')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();
        
        
        $infoformulario = CmsSection::where('component_id', 'donar_seccion_formulario_78')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        return view('jrrss/donar', [
            'banner' => $banner,
            'infoformulario' => $infoformulario
        ]);
    }

    public function gracias_por_donar($donador)
    {
        return view('jrrss/gracias-por-donar', compact('donador'));
    }

    public function graciasporcomprartuentrada($id)
    {
        return view('jrrss/gracias-por-comprar-tu-entrada', [
            'ticket' => EvenEventTicketClient::with('event')->where('id', $id)->first(),
        ]);
    }

    public function email(){
        return view('layouts/email_gratitude');
    }

    public function testimage($content, $fecha = null)
    {


        if ($fecha == null) {
            echo "Agrega un Slash '/' y agrega la fecha ejemplo 'test-image/Miguel de Cervantes Saavedra/23-01-2021'";
        } else {
            // create Image from file
            $img = Image::make('https://aprendiendoconsira.com/wp-content/uploads/2022/06/5-2000x1000.png');


            // write text
            //$img->text('The quick brown fox jumps over the lazy dog.');

            // write text at position
            //$img->text('The quick brown fox jumps over the lazy dog.', 120, 100);

            $img->text($content, 1070, 496, function ($font) {
                $font->file('fonts/OLDENGL.TTF');
                $font->size(72);
                $font->color('#0d0603');
                $font->align('center');
                $font->valign('top');
                $font->angle(0);
            });

            $img->text("Entregado el: " . $fecha, 120, 15, function ($font) {
                $font->file('fonts/OLDENGL.TTF');
                $font->size(40);
                $font->color('#0d0603');
                $font->align('left');
                $font->valign('top');
                $font->angle(0);
            });

            $img->text("Pragot Especialistas en Especialización y mejora continua.", 1840, 15, function ($font) {
                $font->file('fonts/OLDENGL.TTF');
                $font->size(40);
                $font->color('#0d0603');
                $font->align('right');
                $font->valign('top');
                $font->angle(0);
            });

            $qr = Image::make('https://borealtech.com/wp-content/uploads/2018/10/codigo-qr-1024x1024-1.jpg');
            $qr->fit(200, 200);
            $img->insert($qr, 'bottom-left', 30, 30);


            // Ejemplo de Redimensionar la imagen manteniendo la proporción para avatares y similares
            // Establecer el ancho máximo y la altura máxima deseados
            $maxWidth = 1550;
            $maxHeight = 1550;
            $img->resize($maxWidth, $maxHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });



            // Obtener el contenido binario de la imagen
            $imageContent = $img->encode('png');


            // Generar la respuesta HTTP con la imagen
            $response = Response::make($imageContent);

            // Establecer el tipo de contenido de la respuesta como imagen PNG
            $response->header('Content-Type', 'image/png');

            // Retornar la respuesta
            return $response;
        }
    }

    public function donarTarjeta(Request $request)
    {

        $preference_id = null;

        $title = $request->get('donation_destinity_id');
        $price = $request->get('amount');
        $nombres = $request->get('nombres');
        $apellidos = $request->get('apellidos');
        $correo = $request->get('correo');
        $donador = $nombres . ' ' . $apellidos;

        try {
            MercadoPagoConfig::setAccessToken(env('MERCADOPAGO_TOKEN'));
            $client = new PreferenceClient();

            //dd($mp_items);
            $preference = $client->create([
                "items" => array(
                    array(
                        'category_id' => 'DONACIÓN',
                        'title' => $title,
                        'quantity'      => 1,
                        'currency_id'   => 'PEN',
                        'unit_price'    => floatval($price)
                    )
                ),
                "back_urls" =>  array(
                    'success' => route('web_gracias_por_donar', $donador),
                    // 'failure' => route('onlineshop_response_mercadopago'),
                    // 'pending' => route('onlineshop_response_mercadopago')
                )
            ]);
            $preference_id =  $preference->id;
        } catch (\MercadoPago\Exceptions\MPApiException $e) {
            // Manejar la excepción
            $response = $e->getApiResponse();
            dd($response); // Mostrar la respuesta para obtener más detalles
        }

        return view('jrrss/donar-pagar', [
            'preference_id' => $preference_id,
            'datos_form' => $request->all()
        ]);
    }

    public function processDonacion(Request $request)
    {
        MercadoPagoConfig::setAccessToken(env('MERCADOPAGO_TOKEN'));
        $donationdata = $request->get('items');
        $client = new PaymentClient();

        try {

            // dd($request->get('payer'));

            $payment = $client->create([
                "token" => $request->get('token'),
                "issuer_id" => $request->get('issuer_id'),
                "payment_method_id" => $request->get('payment_method_id'),
                "transaction_amount" => (float) $request->get('transaction_amount'),
                "installments" => $request->get('installments'),
                "payer" => $request->get('payer')
            ]);
            //dd($payment);
            if ($payment->status == 'approved') {
                $ticket = new EvenEventDonation();
                $ticket->nombres = $donationdata['nombre'];
                $ticket->monto = $donationdata['monto'];
                $ticket->tipo_donacion = $donationdata['tipo'];
                $ticket->status = true;
                $ticket->response_status = $request->get('collection_status');
                $ticket->response_id = $request->get('collection_id');
                $ticket->response_date_approved = Carbon::now()->format('Y-m-d');
                $ticket->response_payer = json_encode($request->all());
                $ticket->response_payment_method_id = $request->get('payment_type');
                $ticket->origen_pago = "MercadoPago";
                $ticket->tipo_moneda = "PEN";
                $ticket->comision = 3.79; // % comision de  MercadoPago en 14 días es mayor si quieren al instante 3.99
                $ticket->comision_fija =  1; //comision fija de MercadoPago en Soles
                $ticket->save();

                return response()->json([
                    'status' => $payment->status,
                    'message' => $payment->status_detail,
                    'url' => route('web_gracias_por_donar', $donationdata['nombre'])
                ]);
            } else {

                return response()->json([
                    'status' => $payment->status,
                    'message' => $payment->status_detail,
                    'url' => route('web_donar')
                ]);
            }
        } catch (\MercadoPago\Exceptions\MPApiException $e) {
            // Manejar la excepción
            $response = $e->getApiResponse();
            $content  = $response->getContent();
            $message = $content['message'];
            return response()->json(['error' => 'Error al procesar el pago: ' . $message], 412);
        }
    }
}
