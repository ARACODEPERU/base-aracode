<?php

namespace App\Http\Controllers;

use App\Mail\SendClaimConfirmationEmail;
use App\Models\ComplaintsBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\CMS\Entities\CmsSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ComplaintsBookController extends Controller
{
    public function index() {
        $numItems = env('RECORDS_PAGE_TABLE',20);
        $books = ComplaintsBook::with('attentions')->orderBy('id','DESC')->paginate($numItems);
        $monedas = DB::table('sunat_currency_types')->get();
        $priorities = getEnumValues('complaints_book_attentions','priority', true);
        $meansCommunication = getEnumValues('complaints_book_attentions','means_communication', true);
        //dd($meansCommunication);
        return Inertia::render('CRM::ComplaintsBook/List',[
            'books' => $books,
            'monedas' => $monedas,
            'priorities' => $priorities,
            'meansCommunication' => $meansCommunication
        ]);
    }

    public function createdByClient(){
        $header = CmsSection::with(['items' => function ($query) {
            $query->orderBy('position');
        }, 'items.item'])
            ->where('component_id', 'encabezado_2')
            ->first();

        $monedas = DB::table('sunat_currency_types')->get();

        $tipoDocuemntos = DB::table('identity_document_type')->get();

        // para vistas BLADE
        // return view('pages/complaints-book', [
        //     'monedas' => $monedas,
        //         'tipoDocumentos' => $tipoDocumentos,
        // ]);
        // para vistas VueJS
        return Inertia::render('Landing/ComplaintsBook',[
            'dataBook' => [
                'header' => $header,
                'monedas' => $monedas,
                'tipoDocuemntos' => $tipoDocuemntos
            ],
        ]);
    }

    public function storeByClient (Request $request): RedirectResponse
    {
        // 1. Validación de los datos
        // NOTA: 'names' está duplicado en tus reglas originales. Lo he ajustado.
        // Agregué 'accepted' para 'acepto' que es ideal para checkboxes.
        // Agregué 'numeric' para 'monto' y 'min:0'.
        $this->validate($request, [
            'names' => 'required|string|max:255', // Asumo que es el nombre completo
            'tipoIdentificacion' => 'required', // Tipo de documento (DNI, RUC, etc.)
            'dni' => 'required|string|max:20', // Número de documento
            'email' => 'required|email:rfc,dns|max:255', // 'email:rfc,dns' para validación muy estricta, incluyendo verificación de DNS
            'telefono' => 'required|string|max:15',
            'tipoBien' => 'required|string', // Tipo de bien/servicio
            'descripcion_bien' => 'required|string',
            'moneda' => 'nullable|string|max:5', // 'nullable' si no siempre es obligatorio
            'monto' => 'nullable|numeric|min:0', // 'nullable' y numérico/mínimo 0
            'tipoReclamo' => 'required|string', // Tipo de reclamo (Reclamo/Queja)
            'reclamo' => 'required|string', // Detalle del reclamo
            'pedido' => 'required|string', // Pedido del consumidor
            'acepto' => 'required|accepted', // Para el checkbox de aceptación
        ],[
            // Mensajes personalizados para una mejor UX
            'names.required' => 'El campo Nombre Completo es obligatorio.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'email.rfc' => 'El correo electrónico no cumple con los estándares RFC.',
            'email.dns' => 'El dominio del correo electrónico no existe o no tiene registros válidos.',
            'monto.numeric' => 'El monto debe ser un valor numérico.',
            'acepto.required' => 'Debe aceptar el tratamiento de sus datos personales según lo descrito.',
            'acepto.accepted' => 'Debe aceptar el tratamiento de sus datos personales según lo descrito.',
        ]);

        // 2. Usar una transacción de base de datos
        // Si ocurre alguna excepción dentro de este bloque,
        // la base de datos hará un ROLLBACK (deshará los cambios).
        try {
            DB::transaction(function () use ($request) {
                // Crear el reclamo en la base de datos
                // Asegúrate de que los nombres de los campos en el array coincidan con los de tu DB y $fillable
                $book = ComplaintsBook::create([
                    'names' => $request->get('names'),
                    'document_type_id' => $request->get('tipoIdentificacion'),
                    'dni' => $request->get('dni'),
                    'email' => $request->get('email'),
                    'telefono' => $request->get('telefono'),
                    'type_service' => $request->get('tipoBien'),
                    'description_service' => $request->get('descripcion_bien'),
                    // Usa el operador null coalescing para asignar null si no está presente
                    'currency' => $request->get('moneda') ?? null,
                    'amount' => $request->get('monto') ?? 0,
                    'type_claim' => $request->get('tipoReclamo'),
                    'claim' => $request->get('reclamo'),
                    'called' => $request->get('pedido'),
                    'accepts' => $request->get('acepto'), // El valor '1' o 'on'
                    // El 'composite_code' se genera automáticamente en el evento 'creating' del modelo
                    'status' => 'RE'
                ]);

                // Enviar el correo de confirmación
                // Si esta línea lanza una excepción (ej. problemas de configuración de correo),
                // la transacción se revertirá y el registro de la DB no se creará.
                Mail::to($book->email)->send(new SendClaimConfirmationEmail($book));
            });

            // Si todo fue exitoso (validación, registro y envío de correo)
            return redirect()->route('complaints_book')->with('success', '¡Su reclamo ha sido registrado con éxito! Revise su correo electrónico para obtener el número de folio.');

        } catch (\Exception $e) {
            // Si algo falla dentro de la transacción (incluido el envío de correo)
            // se captura la excepción.
            // Es buena práctica loggear el error para debugging.
            Log::error('Error al registrar o enviar correo de reclamo: ' . $e->getMessage(), ['exception' => $e]);

            // Redirige de vuelta con un mensaje de error genérico
            return redirect()->back()->withInput()->with('error', 'Hubo un problema al procesar su reclamo. Por favor, inténtelo de nuevo más tarde o contáctenos directamente.');
        }
    }
}
