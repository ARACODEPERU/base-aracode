<?php

namespace Modules\Churchcommunity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Person;
use App\Models\PersonRelative;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Churchcommunity\Entities\CigleBeliever;
use Modules\Churchcommunity\Entities\CigleMember;
use Modules\Churchcommunity\Entities\CiglePerson;
use Modules\Churchcommunity\Entities\CiglePlaceEvangelization;
use Modules\Churchcommunity\Entities\CigleSchedule;
use DataTables;
use Modules\Churchcommunity\Entities\CigleMemberType;

class CigleMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sedes = Sede::get();
        $identityDocumentTypes = DB::table('identity_document_type')->whereNotIn('id',[6])->get();
        //dd($sedes);
        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();

        $evangelizations = CiglePlaceEvangelization::get();
        $schedules = CigleSchedule::get();
        $memberTypes = CigleMemberType::get();

        return Inertia::render('Churchcommunity::Members/ListGeneral',[
            'sedes' => $sedes,
            'identityDocumentTypes' => $identityDocumentTypes,
            'ubigeo' => $ubigeo,
            'evangelizations' => $evangelizations,
            'schedules' => $schedules,
            'memberTypes' => $memberTypes
        ]);
    }

    public function getMembers(Request $request)
    {
        //dd($request->all());
        $members = CigleMember::query()
        ->with(['person', 'level']);

        $hasFullAccess = Auth::user()->hasAnyRole(['admin', 'Administrador']);
        if ($hasFullAccess) {
            $members->with('believers');
        }

        return DataTables::eloquent($members)
            ->filter(function ($query) use ($request, $hasFullAccess) {
                if ($request->custom_search) {
                    $search = $request->custom_search;
                    $query->whereHas('person', function($q) use ($search) {
                        $q->where('full_name', 'like', "%{$search}%");
                    });
                }

                $filters = $request->filters;
                if ($filters['nivel']) {
                    $query->where('type_id', $filters['nivel']);
                }
                if ($filters['sede']) {
                    $query->where('sede_id', $filters['sede']);
                }
                if ($filters['ubigeo']) {
                    $query->whereHas('person', function($q) use ($filters) {
                        $q->where('ubigeo', $filters['ubigeo']);
                    });
                }
                if($filters['evangelization'] && $hasFullAccess){
                    $query->whereHas('believers', function($q) use ($filters) {
                        $q->where('evangelization_id', $filters['evangelization']);
                    });
                }
            })
            ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function believerCreate()
    {
        $sedes = Sede::get();
        $identityDocumentTypes = DB::table('identity_document_type')->whereNotIn('id',[6])->get();
        //dd($sedes);
        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();

            $evangelizations = CiglePlaceEvangelization::get();
            $schedules = CigleSchedule::get();

        return Inertia::render('Churchcommunity::Members/BelieversCard',[
            'sedes' => $sedes,
            'identityDocumentTypes' => $identityDocumentTypes,
            'ubigeo' => $ubigeo,
            'evangelizations' => $evangelizations,
            'schedules' => $schedules
        ]);
    }

    public function believerAction(Request $request){
        //dd($request->all());
        $this->validate(
            $request,
            [
                'sede_id'           => 'required',
                'document_type_id'  => 'required',
                'number'            => 'required|max:12',
                'telephone'         => 'required|max:12',
                'email'             => 'required|email|max:255',
                'address'           => 'required|max:255',
                'ubigeo'            => 'required|max:255',
                'birthdate'         => 'required|',
                'names'             => 'required|max:255',
                'father_lastname'   => 'required|max:255',
                'mother_lastname'   => 'required|max:255',
            ]
        );

        $documentType = $request->get('document_type_id');
        $number = $request->get('number');

        $person = Person::where([
            ['document_type_id', '=', $documentType],
            ['number', '=', $number],
        ])->first();

        if($person){

            $update_id = $person->id;
            $user = User::where('person_id', $person->id)->first();

            $this->validate(
                $request,
                [
                    'number'            => 'unique:people,number,' . $update_id . ',id,document_type_id,' . $request->get('document_type_id'),
                    'email'             => 'unique:people,email,' . $update_id . ',id',
                    'email'             => 'unique:users,email,' . ($user ? $user->id  : null) . ',id',
                ]
            );

        } else {
            $person = new Person();
        }

        $person->document_type_id = $documentType;
        $person->number = $number;
        $person->birthdate = $request->get('birthdate');
        $person->names = $request->get('names');
        $person->father_lastname = $request->get('father_lastname');
        $person->mother_lastname = $request->get('mother_lastname');
        $person->address = $request->get('address');
        $person->telephone = $request->get('telephone');
        $person->is_provider = false;
        $person->is_client = false;
        $person->ubigeo = $request->get('ubigeo');
        $person->ubigeo_description = $request->get('ubigeo_description');
        $person->gender = $request->get('gender') ?? 'M';
        $person->short_name = $request->get('names');
        $person->full_name = trim($request->get('father_lastname') . ' ' .  $request->get('mother_lastname') . ' ' . $request->get('names'));

        $person->save();
        //dd($request->get('believer_id'));
        if($request->get('member_id') && $request->get('believer_id')){
            $this->believerUpdate($request, $person);
        }elseif (CigleMember::where('person_id', $person->id)->exists()){
            $this->believerUpdate($request, $person);
        }else{
            $this->believerStore($request, $person);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function believerStore($request, $person)
    {

        $member = CigleMember::create([
            'sede_id' => $request->get('sede_id'),
            'person_id' => $person->id,
            'type_id' => 1,
            'main' => false,
            'date_statement' => $request->get('date_statement')
        ]);

        CigleBeliever::create([
            'sede_id' => $request->get('sede_id'),
            'member_id' => $member->id,
            'person_id' => $person->id,
            'accept_Christ' => $request->get('accept_Christ'),
            'schedule_id' => $request->get('schedule_id'),
            'name_red' => $request->get('name_red'),
            'prayer_reason' => $request->get('prayer_reason'),
            'evangelization_id' => $request->get('soul_won'),
            'well_known_place' => $request->get('well_known_place'),
            'contact_person_id' => $request->get('contact_person_id') ?? null,
            'contact_full_names' => $request->get('contact_full_names'),
            'user_id' => Auth::id(),
        ]);

        $relatives = $request->get('relatives') ?? [];

        if(count($relatives) > 0){
            foreach($relatives as $relative){
                $personRelative = Person::firstOrCreate([
                    'document_type_id' => $relative['document_type_id'],
                    'number' => $relative['number'],
                ],
                [
                    'short_name' => $relative['names'],
                    'names' => $relative['names'],
                    'full_name' => $relative['father_lastname'] . ' ' . $relative['mother_lastname'] . ' ' . $relative['names'],
                    'father_lastname' => $relative['father_lastname'],
                    'mother_lastname' => $relative['mother_lastname'],
                    'gender' => $relative['gender'],
                    'birthdate' => $relative['birthdate']
                ]);

                PersonRelative::create([
                    'person_one_id' => $person->id,
                    'person_two_id' => $personRelative->id,
                    'relationship' => $relative['relationship']
                ]);
            }
        }

    }

    public function believerUpdate($request, $person)
    {
        $memberId   = $request->get('member_id');
        $believerId = $request->get('believer_id');

        // Buscar registros
        $member   = CigleMember::find($memberId);
        $believer = CigleBeliever::find($believerId);

        // ✅ Solo actualizar si existe el member
        if ($member) {
            $member->update([
                'sede_id'        => $request->get('sede_id'),
                'person_id'      => $person->id, // asumo que $person ya está definido arriba
                'type_id'        => 1,
                'main'           => false,
                'date_statement' => $request->get('date_statement')
            ]);
        }

        // ✅ Solo actualizar si existe el believer
        if ($believer) {
            $believer->update([
                'sede_id'           => $request->get('sede_id'),
                'member_id'         => $member ? $member->id : null, // usa null si no existe member
                'person_id'         => $person->id,
                'accept_Christ'     => $request->get('accept_Christ'),
                'schedule_id'       => $request->get('schedule_id'),
                'name_red'          => $request->get('name_red'),
                'prayer_reason'     => $request->get('prayer_reason'),
                'evangelization_id' => $request->get('soul_won'),
                'well_known_place'  => $request->get('well_known_place'),
                'contact_person_id' => $request->get('contact_person_id') ?? null,
                'contact_full_names'=> $request->get('contact_full_names'),
                'user_id'           => Auth::id(),
            ]);
        }

        $relatives = $request->get('relatives') ?? [];
        if(count($relatives) > 0){
            foreach($relatives as $relative){
                $personRelative = Person::updateOrCreate([
                    'document_type_id' => $relative['document_type_id'],
                    'number' => $relative['number'],
                ],[
                    'short_name' => $relative['names'],
                    'names' => $relative['names'],
                    'full_name' => $relative['father_lastname'] . ' ' . $relative['mother_lastname'] . ' ' . $relative['names'],
                    'father_lastname' => $relative['father_lastname'],
                    'mother_lastname' => $relative['mother_lastname'],
                    'gender' => $relative['gender'],
                    'birthdate' => $relative['birthdate']
                ]);

                PersonRelative::firstOrCreate([
                    'person_one_id' => $person->id,
                    'person_two_id' => $personRelative->id,
                ],[
                    'relationship' => $relative['relationship']
                ]);
            }
        }

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

    public function searchMember(Request $request)
    {
        $from = $request->get('from');
        $documentType = $request->get('documentType');
        $number = $request->get('number');

        $query = CiglePerson::with(['member', 'relatives.person']);

        if ($from === 'believers') {
            //dd($from);
            $query->with('believer');
        }

        $person = $query->where([
            ['document_type_id', '=', $documentType],
            ['number', '=', $number],
        ])->first();

        return response()->json([
            'success' => $person ? true : false,
            'person'  => $person
        ]);
    }
}
