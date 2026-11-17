<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\IdentityDocumentType;
use App\Models\Parameter;
use App\Models\Person;
use App\Models\User;
use App\Support\PersonProfileData;
use App\Support\PersonUserPersistenceVerifier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Client;
use Illuminate\Validation\Rule;
use Modules\Academic\Jobs\SendProfileWebhook;
use Modules\Academic\Support\ProfileWebhookPayload;
use Modules\Security\Rules\ValidationPersonUser;

class PersonController extends Controller
{

    protected $P000012;

    public function __construct()
    {
        $this->P000012 = Parameter::where('parameter_code', 'P000012')->value('value_default');
    }

    public function searchByNumberType(Request $request)
    {
        $document_type = $request->input('document_type');
        $number        = $request->input('number');
        $full_name     = $request->input('full_name');
        $searchBy      = $request->input('searchBy') ?? 1; // 1 = number, 2 = full_name

        $msg1 = '';
        $msg2 = '';
        $status = true;
        $alert  = 'No existen datos para la búsqueda';
        $ubigeo = [];

        // Validación usando Laravel
        $this->validate($request, [
            'searchBy'      => 'nullable|in:1,2',

            // Obligatorio solo si searchBy = 1
            'document_type' => 'required_if:searchBy,1',

            // Obligatorio si la búsqueda es por número
            'number'        => 'required_if:searchBy,1',

            // Obligatorio si la búsqueda es por nombre
            'full_name'     => 'required_if:searchBy,2',
        ],[
            // Mensajes personalizados
            'document_type.required_if' => 'Para la búsqueda por número es necesario elegir el tipo de documento.',
            'number.required_if'        => 'Para la búsqueda por número es necesario ingresar un número.',
            'full_name.required_if'     => 'Para la búsqueda por nombre o razón social es obligatorio ingresar un nombre o razón social.',
        ]);

        // Base query
        $query = Person::query()->with(['teacher', 'student']);


        // Ejecutar búsqueda según tipo
        if ($searchBy == 1) {
            // SOLO 1 RESULTADO
            $person = $query->where('people.document_type_id', $document_type)
                ->where('people.number', $number)
                ->first();
        } else {
            // VARIOS RESULTADOS
            $person = $query->where('people.full_name', 'LIKE', "%{$full_name}%")->get();
        }

        // Si buscó por DNI → verificar objeto
        if ($searchBy == 1) {
            if ($person) {
                $status = true;
                $alert = null;

                // construir ubigeo
                $ubigeo = [
                    'district_id' => $person->ubigeo,
                    'city_name'   => $person->ubigeo_description
                ];
            } else {
                $status = false;
            }
        }

        // Si buscó por nombre → verificar colección
        if ($searchBy == 2) {
            if ($person->count() > 0) {
                $status = true;
                $alert  = null;
                $ubigeo = []; // no devolver ubigeo porque hay varios resultados
            } else {
                $status = false;
            }
        }

        return response()->json([
            'status'        => $status,
            'person'        => $person,
            'document_type' => $msg1,
            'number'        => $msg2,
            'alert'         => $alert,
            'ubigeo'        => $ubigeo
        ]);
    }

    public function saveUpdateOrCreate(Request $request)
    {
        $this->validate($request, [
            'document_type' => 'required',
            'number' => 'required',
            'full_name' => 'required|max:255',
            'address'   => 'required|max:255',
            'ubigeo'   => 'required',
            'email' => [
                'nullable',
                'email',
                Rule::unique('people')->where(function ($query) use ($request) {
                    return $query
                        ->where('document_type_id', '!=', $request->input('document_type'))
                        ->orWhere('number', '!=', $request->input('number'));
                })
            ],
        ]);

        $ubigeo = $request->input('ubigeo');
        $ubigeo_description = $request->input('ubigeo_description');

        $person = Person::updateOrCreate(
            [
                'document_type_id' => $request->input('document_type'),
                'number' => $request->input('number'),
            ],
            [
                'full_name' => trim($request->input('full_name')),
                'telephone' => $request->input('telephone'),
                'email' => $request->input('email'),
                'address' => $request->input('address'),
                'is_client' => $request->boolean('is_client'),
                'is_provider' => $request->boolean('is_provider'),
                'ubigeo' => is_array($ubigeo) ? $ubigeo['district_id'] : $ubigeo,
                'ubigeo_description' => is_array($ubigeo) ? $ubigeo['city_name'] : $ubigeo_description,
                'names' => $request->input('names') ?? null,
                'father_lastname' => $request->input('father_lastname') ?? null,
                'mother_lastname' => $request->input('mother_lastname') ?? null
            ]
        );

        // La ciudad es opcional: antes, una persona sin ubigeo (o con un distrito
        // inexistente) tumbaba este endpoint con un 500 y el modal de apoderado
        // cerraba sin ningun aviso.
        $district = District::with('province.department')->find($person->ubigeo);
        $person->city = $district
            ? $district->province->department->name . "-" . $district->province->name . "-" . $district->name
            : $person->ubigeo_description;

        return response()->json($person);
    }

    public function searchByNameOrNumber(Request $request)
    {
        $search = $request->input('search');
        $status = true;
        $persons = [];
        $alert = 'No existen datos para la busqueda';

        $persons = Person::where('number', $search)
            ->orWhere('full_name', 'like', '%' . $search . '%')
            ->orWhere('short_name', 'like', '%Clientes Varios%')
            ->orderBy('id')
            ->get();

        if (count($persons) > 0) {
            $status = true;
            $alert = null;
        } else {
            $status = false;
            $alert = 'No existen datos para la busqueda';
        }

        return response()->json([
            'status'        => $status,
            'persons'        => $persons,
            'alert'         => $alert
        ]);
    }

    public function updateInformationPerson(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        // La ficha se resuelve por el usuario autenticado, no por el id que manda
        // el formulario: asi nadie edita la persona de otra cuenta.
        $person = $user->person_id ? Person::find($user->person_id) : null;

        // updated_information en false significa que es la primera vez que la persona
        // completa su perfil obligatorio: esa transicion es la que avisa a n8n.
        $wasUpdatedInformation = (bool) $user->updated_information;

        $isForeign = IdentityDocumentType::isForeign($request->get('document_type_id'));

        $rules = [
            'document_type_id'  => 'required',
            'number'            => ['required', 'max:12', Rule::unique('people', 'number')->ignore($person?->id, 'id')],
            'telephone'         => 'required|max:12',
            'email'             => ['required', 'max:255', Rule::unique('people', 'email')->ignore($person?->id, 'id')],
            'address'           => 'required|max:255',
            'birthdate'         => 'required',
            'names'             => 'required|max:255',
            'father_lastname'   => 'required|max:255',
            'mother_lastname'   => 'required|max:255',
            // Cargo, profesion, empresa, industria y genero: son los campos que el
            // payload a n8n envia en el bloque 'persona'. Se piden con el mismo
            // criterio que en el formulario publico de una negociacion.
            'gender'            => 'nullable|in:M,F',
            'occupation_id'     => 'nullable|integer|exists:occupations,id',
            'profession_id'     => 'nullable|integer|exists:professions,id',
            'industry_id'       => 'nullable|integer|exists:industries,id',
            'company'           => 'nullable|max:200',
        ];

        if ($isForeign) {
            $rules['foreign_country_id'] = 'required';
            $rules['foreign_state'] = 'required|max:255';
            $rules['foreign_city'] = 'required|max:255';
        } else {
            $rules['ubigeo'] = 'required|max:255';
        }

        $this->validate($request, $rules);

        // Solo se guarda una imagen nueva (data URI o archivo). Si el formulario
        // reenvia la ruta que ya tenia la ficha, se conserva la foto actual.
        $newImage = PersonProfileData::storeNewImage(
            $request,
            'image',
            'uploads/students',
            (string) $request->get('number')
        );

        // El formulario obligatorio no tiene campo description: si no llega, no se
        // incluye en el update y people.description conserva su valor anterior.
        $updateData = [
            'document_type_id'      => $request->get('document_type_id'),
            'short_name'            => trim($request->get('names')),
            'full_name'             => trim($request->get('father_lastname') . ' ' .  $request->get('mother_lastname') . ' ' . $request->get('names')),
            'number'                => $request->get('number'),
            'telephone'             => $request->get('telephone'),
            'email'                 => $request->get('email'),
            'address'               => $request->get('address'),
            'birthdate'             => $request->get('birthdate'),
            'names'                 => trim($request->get('names')),
            'father_lastname'       => trim($request->get('father_lastname')),
            'mother_lastname'       => trim($request->get('mother_lastname')),
        ];

        // Campos opcionales: solo se escriben cuando el formulario los envia con
        // valor. is_provider/is_client no se tocan.
        $updateData = array_merge($updateData, PersonProfileData::onlyFilled($request, [
            'description'   => 'description',
            'gender'        => 'gender',
            'company'       => 'company',
        ]));

        // Los catalogos se resuelven contra su tabla para guardar el id y el texto
        // oficial, pero solo cuando el request trae el campo.
        foreach ([
            ['input' => 'occupation_id', 'table' => 'occupations', 'idField' => 'occupation_id', 'textField' => 'ocupacion'],
            ['input' => 'profession_id', 'table' => 'professions', 'idField' => 'profession_id', 'textField' => 'profession'],
            ['input' => 'industry_id', 'table' => 'industries', 'idField' => 'industry_id', 'textField' => 'industry'],
        ] as $catalogo) {
            if (! $request->exists($catalogo['input'])) {
                continue;
            }

            [$catalogoId, $catalogoTexto] = $this->resolveCatalogValue(
                $request->input($catalogo['input']),
                $catalogo['table']
            );

            $updateData[$catalogo['idField']] = $catalogoId;
            $updateData[$catalogo['textField']] = $catalogoTexto;
        }

        if ($isForeign) {
            $country = \App\Models\Country::find($request->get('foreign_country_id'));
            $countryName = $country ? $country->description : '';
            $updateData['ubigeo'] = null;
            $updateData['ubigeo_description'] = trim($countryName . ' - ' . $request->get('foreign_state') . ' - ' . $request->get('foreign_city'));
            $updateData['foreign_country_id'] = $request->get('foreign_country_id');
            $updateData['foreign_state'] = $request->get('foreign_state');
            $updateData['foreign_city'] = $request->get('foreign_city');
        } else {
            $updateData['ubigeo'] = $request->get('ubigeo');
            $updateData['foreign_country_id'] = null;
            $updateData['foreign_state'] = null;
            $updateData['foreign_city'] = null;
        }

        if ($newImage) {
            $updateData['image'] = $newImage;
        }

        if ($person) {
            $person->update($updateData);
        } else {
            // Usuario sin ficha vinculada (por ejemplo, registrado solo con su
            // cuenta): se crea la ficha con los datos validados y se enlaza.
            $person = Person::create(array_merge($updateData, [
                'is_client' => true,
                'is_provider' => false,
            ]));
        }

        $userData = [
            'name' => $request->get('names'),
            'email' => $request->get('email'),
            'updated_information' => true,
        ];

        if (! $user->person_id) {
            $userData['person_id'] = $person->id;
        }

        if ($request->exists('description') && PersonProfileData::hasValue($request->input('description'))) {
            $userData['information'] = trim((string) $request->input('description'));
        }

        if ($newImage) {
            $userData['avatar'] = $newImage;
        }

        $user->update($userData);

        // El codigo de alumno se actualiza por persona: no se usa el student_id
        // que viaja en el formulario.
        DB::table('aca_students')->where('person_id', $person->id)->update([
            'student_code'  => $request->get('number'),
        ]);

        PersonUserPersistenceVerifier::verify($person, $user, $updateData, $userData);

        // Primera vez que la persona completa su perfil obligatorio: se envian todos
        // sus datos y sus cursos a n8n. En las ediciones posteriores
        // updated_information ya venia en true, asi que no se reenvia.
        if (! $wasUpdatedInformation) {
            $webhookPersonId = (int) $person->id;

            if ($webhookPersonId > 0) {
                SendProfileWebhook::dispatchOnce(
                    'perfil:'.$webhookPersonId,
                    $webhookPersonId,
                    ProfileWebhookPayload::PERFIL_COMPLETADO
                );
            }
        }

        return redirect()->route('aca_mycourses')
            ->with('updateMessageStudent', __('Informacion del estudiante actualizado con éxito'));
    }

    /**
     * Valor de catalogo elegido en el perfil obligatorio (cargo u ocupacion,
     * profesion, industria). Devuelve el id junto al texto oficial del catalogo;
     * un id vacio o inexistente no guarda nada.
     *
     * @return array{0: ?int, 1: ?string}
     */
    private function resolveCatalogValue($id, string $table): array
    {
        if (blank($id)) {
            return [null, null];
        }

        $description = DB::table($table)->where('id', $id)->value('description');

        if ($description === null) {
            return [null, null];
        }

        return [(int) $id, $description];
    }

    public function createdOrUpdated(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        // La ficha se resuelve por el usuario autenticado. El id del formulario no
        // puede reapuntar users.person_id a otra persona ni dejar la ficha anterior
        // huerfana.
        $person = $user->person_id ? Person::find($user->person_id) : null;

        // Si ya habia completado sus datos antes, este guardado es una edicion y no
        // debe reenviarse el aviso a n8n.
        $wasUpdatedInformation = (bool) $user->updated_information;
        $webhookPersonId = null;

        $isForeign = IdentityDocumentType::isForeign($request->get('document_type_id'));

        $rules = [
            'document_type_id'  => 'required',
            'number'            => 'required|max:12',
            'telephone'         => 'nullable|max:12',
            'email'             => 'nullable|max:255',
            'address'           => 'nullable|max:255',
            'birthdate'         => 'nullable',
            'names'             => 'required|max:255',
            'father_lastname'   => 'required|max:255',
            'mother_lastname'   => 'required|max:255',
        ];

        if ($isForeign) {
            $rules['foreign_country_id'] = 'required';
            $rules['foreign_state'] = 'required|max:255';
            $rules['foreign_city'] = 'required|max:255';
        } else {
            $rules['ubigeo'] = 'required';
        }

        $this->validate($request, $rules);

        // Always use the authenticated user's email (field is disabled, not editable)
        $email = $user->email;

        // Construir datos de ubicación
        if ($isForeign) {
            $country = \App\Models\Country::find($request->input('foreign_country_id'));
            $countryName = $country ? $country->description : '';
            $ubigeoValue = null;
            $ubigeoDescription = trim($countryName . ' - ' . $request->input('foreign_state') . ' - ' . $request->input('foreign_city'));
            $foreignCountryId = $request->input('foreign_country_id');
            $foreignState = $request->input('foreign_state');
            $foreignCity = $request->input('foreign_city');
        } else {
            $ubigeoData = $request->input('ubigeo');
            $ubigeoValue = is_array($ubigeoData) ? ($ubigeoData['district_id'] ?? null) : $ubigeoData;
            $ubigeoDescription = is_array($ubigeoData) ? ($ubigeoData['name_city'] ?? null) : ($request->input('ubigeo_description') ?? null);
            $foreignCountryId = null;
            $foreignState = null;
            $foreignCity = null;
        }

        // Solo se envian a people las claves presentes con valor. description,
        // ocupacion, presentacion y gender no se borran cuando el formulario no
        // los manda. is_provider/is_client/status tampoco se tocan.
        $personData = PersonProfileData::onlyFilled($request, [
            'document_type_id' => 'document_type_id',
            'number'           => 'number',
            'description'      => 'description',
            'telephone'        => 'telephone',
            'address'          => 'address',
            'birthdate'        => 'birthdate',
            'names'            => 'names',
            'father_lastname'  => 'father_lastname',
            'mother_lastname'  => 'mother_lastname',
            'ocupacion'        => 'ocupacion',
            'presentacion'     => 'presentacion',
            'gender'           => 'gender',
        ]);

        $personData['short_name'] = trim((string) $request->input('names'));
        $personData['full_name'] = trim($request->get('father_lastname') . ' ' . $request->get('mother_lastname') . ' ' . $request->get('names'));
        $personData['email'] = $email;
        $personData['ubigeo'] = $ubigeoValue;
        $personData['ubigeo_description'] = $ubigeoDescription;
        $personData['foreign_country_id'] = $foreignCountryId;
        $personData['foreign_state'] = $foreignState;
        $personData['foreign_city'] = $foreignCity;

        if ($request->exists('social_networks') && PersonProfileData::hasValue($request->input('social_networks'))) {
            $socialNetworks = $request->input('social_networks');
            $personData['social_networks'] = is_array($socialNetworks) ? json_encode($socialNetworks) : $socialNetworks;
        }

        if ($person) {
            $person->update($personData);
        } else {
            // Sin ficha previa: se busca por documento y, si no existe, se crea.
            // Nunca se duplica una persona que ya tenga ese documento.
            $person = Person::firstOrNew([
                'document_type_id' => $request->get('document_type_id'),
                'number'           => trim((string) $request->input('number')),
            ]);
            $person->fill($personData)->save();
        }

        $userData = [
            'name'                => trim((string) $request->input('names')),
            'email'               => $email,
            'updated_information' => true,
        ];

        // Solo se enlaza la persona cuando el usuario no tenia una. Si ya tenia,
        // se conserva esa vinculacion.
        if (! $user->person_id) {
            $userData['person_id'] = $person->id;
        }

        $user->update($userData);

        $webhookPersonId = $person->id;

        PersonUserPersistenceVerifier::verify($person, $user, $personData, $userData);

        // Primera vez que la persona completa sus datos: mismo aviso a n8n que el
        // formulario de perfil obligatorio, con sus cursos incluidos.
        if (! $wasUpdatedInformation && $webhookPersonId) {
            SendProfileWebhook::dispatchOnce(
                'perfil:'.$webhookPersonId,
                $webhookPersonId,
                ProfileWebhookPayload::PERFIL_COMPLETADO
            );
        }

        return redirect()->route('dashboard');
    }


    public function consultApisNet($dni)
    {
        $token = $this->P000012;
        $numero = $dni;
        $client = new Client(['base_uri' => 'https://api.apis.net.pe', 'verify' => false]);
        $parameters = [
            'http_errors' => true,
            'connect_timeout' => 5,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Referer' => 'https://apis.net.pe/api-consulta-dni',
                'User-Agent' => 'laravel/guzzle',
                'Accept' => 'application/json',
            ],
            'query' => ['numero' => $numero]
        ];
        $res = $client->request('GET', '/v2/reniec/dni', $parameters);

        if ($res->getStatusCode() == 200) {
            $response = json_decode($res->getBody()->getContents(), true);
            // Resto del código
        } else {
            // Manejo de error en caso de que la solicitud no sea exitosa
        }

        return view('DniConsulta', ['dni' => $response]);
    }

    public function getBirthdays()
    {
        $today = Carbon::today();
        $start = $today->copy()->subDays(2);
        $end   = $today->copy()->addWeek();

        $startDay = $start->dayOfYear;
        $endDay   = $end->dayOfYear;
        $daysInYear = $today->isLeapYear() ? 366 : 365;

        $persons = Person::all()
            ->filter(function ($person) use ($startDay, $endDay, $daysInYear) {
                if (!$person->birthdate) {
                    return false;
                }

                $birthday = Carbon::parse($person->birthdate)->dayOfYear;

                // 🔥 Rango normal
                if ($startDay <= $endDay) {
                    return $birthday >= $startDay && $birthday <= $endDay;
                }

                // 🔥 Cruza fin de año
                return $birthday >= $startDay || $birthday <= $endDay;
            })
            ->sortBy(function ($person) use ($startDay, $endDay) {
                $birthday = Carbon::parse($person->birthdate)->dayOfYear;

                // Ajuste para ordenar bien cuando cruza el año
                return $birthday < $startDay ? $birthday + 366 : $birthday;
            })
            ->values()
            ->map(function ($person) use ($today) {

                $birthdate = Carbon::parse($person->birthdate);
                $currentYear = $today->year;

                $birthdayThisYear = Carbon::create(
                    $currentYear,
                    $birthdate->month,
                    $birthdate->day
                );

                $todayDOY = $today->dayOfYear;
                $birthdayDOY = $birthdayThisYear->dayOfYear;

                if ($birthdayDOY < $todayDOY) {
                    $status = 'pasado';
                } elseif ($birthdayDOY > $todayDOY) {
                    $status = 'proximo';
                } else {
                    $status = 'hoy';
                }

                return [
                    'image' => $person->image,
                    'name' => $person->full_name,
                    'birthdate' => $birthdayThisYear->translatedFormat('d \d\e F'),
                    'age' => $currentYear - $birthdate->year,
                    'status' => $status,
                    'id' => $person->id,
                    'email' => $person->email,
                    'telephone' => $person->telephone,
                ];
            });

            //dd($persons);
        return $persons;
    }

    public function updateInfoPersonByUser(Request $request)
    {
        $authUser = Auth::user();

        if (! $authUser) {
            abort(403);
        }

        // El panel de usuarios puede editar otra cuenta solo si quien guarda es
        // administrador; el resto de usuarios siempre edita su propia cuenta.
        $user = $authUser;
        if ($request->filled('user_id')
            && (int) $request->input('user_id') !== (int) $authUser->id
            && $authUser->hasAnyRole(['admin', 'Administrador'])) {
            $user = User::findOrFail($request->input('user_id'));
        }

        // La ficha sale del usuario objetivo, no del id que manda el formulario.
        $person = $user->person_id ? Person::find($user->person_id) : null;

        $isForeign = IdentityDocumentType::isForeign($request->get('document_type'));

        $rules = [
            'document_type'     => 'required',
            'number'            => [
                'required', 'max:12',
                Rule::unique('people', 'number')
                    ->ignore($person?->id, 'id')
                    ->where('document_type_id', $request->get('document_type'))
            ],
            'email'             => [
                'required', 'email', 'max:255',
                Rule::unique('people', 'email')->ignore($person?->id, 'id'),
                Rule::unique('users', 'email')->ignore($user->id, 'id')
            ],
            'names'             => 'required|max:255',
            'father_lastname'   => 'required|max:255',
            'mother_lastname'   => 'required|max:255',
            'telephone'         => 'required|max:12',
            'address'           => 'required|max:255',
            'birthdate'         => 'required',
        ];

        if ($isForeign) {
            $rules['foreign_country_id'] = 'required';
            $rules['foreign_state'] = 'required|max:255';
            $rules['foreign_city'] = 'required|max:255';
        } else {
            $rules['ubigeo'] = 'required';
        }

        $this->validate($request, $rules);

        DB::transaction(function () use ($request, $person, $user, $isForeign) {

            // Solo los campos que llegan con valor. gender deja de forzarse a 'M'
            // y los campos opcionales no se borran por no venir en el formulario.
            $data = PersonProfileData::onlyFilled($request, [
                'document_type_id'   => 'document_type',
                'number'             => 'number',
                'telephone'          => 'telephone',
                'email'              => 'email',
                'address'            => 'address',
                'birthdate'          => 'birthdate',
                'names'              => 'names',
                'father_lastname'    => 'father_lastname',
                'mother_lastname'    => 'mother_lastname',
                'gender'             => 'gender',
            ]);

            $data['short_name'] = trim((string) $request->input('names'));
            $data['full_name'] = trim($request->get('father_lastname').' '.$request->get('mother_lastname').' '.$request->get('names'));

            if ($isForeign) {
                $country = \App\Models\Country::find($request->get('foreign_country_id'));
                $countryName = $country ? $country->description : '';
                $data['ubigeo'] = null;
                $data['ubigeo_description'] = trim($countryName . ' - ' . $request->get('foreign_state') . ' - ' . $request->get('foreign_city'));
                $data['foreign_country_id'] = $request->get('foreign_country_id');
                $data['foreign_state'] = $request->get('foreign_state');
                $data['foreign_city'] = $request->get('foreign_city');
            } else {
                $data['ubigeo'] = $request->get('ubigeo');
                $data['ubigeo_description'] = $request->get('ubigeo_description');
                $data['foreign_country_id'] = null;
                $data['foreign_state'] = null;
                $data['foreign_city'] = null;
            }

            if ($person) {
                $person->update($data);
            } else {
                $person = Person::create($data);
                $user->person_id = $person->id;
            }

            $userData = [
                'name'  => $request->get('names'),
                'email' => $request->get('email'),
            ];

            if (! $user->person_id) {
                $userData['person_id'] = $person->id;
            }

            $user->update($userData);

            PersonUserPersistenceVerifier::verify($person, $user, $data, $userData);
        });

        return response()->json(['message' => 'Datos actualizados correctamente']);
    }
}
