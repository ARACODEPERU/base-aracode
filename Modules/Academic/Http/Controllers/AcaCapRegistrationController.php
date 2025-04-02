<?php

namespace Modules\Academic\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudent;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Academic\Entities\AcaSubscriptionType;

class AcaCapRegistrationController extends Controller
{
    use ValidatesRequests;

    public function index()
    {
        return view('academic::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create($id)
    {
        $student = AcaStudent::with('person')->where('id', $id)->first();

        $courses = AcaCourse::where('status', true)
            ->get();

        $registrations = AcaCapRegistration::with('course')
            ->where('student_id', $id)->get();

        $subscriptionStudent = AcaStudentSubscription::with('subscription')
            ->where('student_id', $student->id)->get();

        $subscriptions = AcaSubscriptionType::where('status', true)->get();


        return Inertia::render('Academic::Registrations/Create', [
            'student'   => $student,
            'courses'   => $courses,
            'registrations' => $registrations,
            'subscriptions' => $subscriptions,
            'subscriptionStudent' => $subscriptionStudent
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $student_id = $request->get('student_id');
        $course_id = $request->get('course_id');

        $this->validate(
            $request,
            [
                'student_id'  => 'required',
                'course_id'   => 'required',
            ]
        );

        $true = AcaCapRegistration::where('student_id', $student_id)->where('course_id', $course_id)->doesntExist();

        if ($true) {
            AcaStudent::find($student_id)->update([
                'new_student' => false
            ]);
            AcaCapRegistration::create([
                'student_id'        => $student_id,
                'course_id'         => $course_id,
                'status'             => true
            ]);
        }

        return redirect()->route('aca_students_registrations_create', $student_id);
    }

    public function destroy($id)
    {
        $message = null;
        $success = false;
        try {
            DB::beginTransaction();

            $item = AcaCapRegistration::findOrFail($id);

            $item->delete();

            DB::commit();

            $message =  'Matricula eliminado correctamente';
            $success = true;
        } catch (\Exception $e) {
            DB::rollback();
            $success = false;
            $message = $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'message' => $message
        ]);
    }

    public function subscriptionStore(Request $request)
    {
        $student = AcaStudent::find($request->get('student_id'));
        $subscription_id = $request->get('subscription_id');
        $stsubscription = AcaStudentSubscription::where('student_id', $student->id)
            ->where('subscription_id', $subscription_id)
            ->first();
        $subscription = AcaSubscriptionType::find($subscription_id);
        $dateStart = Carbon::today(); // Solo fecha sin hora
        $dateEnd = null;

        // Calcular fecha de fin
        switch ($subscription->period) {
            case 'Mensual':
                $dateEnd = $dateStart->copy()->addMonth();
                break;

            case 'Trimestral':
                $dateEnd = $dateStart->copy()->addMonths(3); // 3 meses
                break;

            case 'Semestral':
                $dateEnd = $dateStart->copy()->addMonths(6); // 6 meses
                break;

            case 'Anual':
                $dateEnd = $dateStart->copy()->addYear();
                break;

            case 'Semanal':
                $dateEnd = $dateStart->copy()->addWeek();
                break;

            case 'Diario':
                $dateEnd = $dateStart->copy()->addDay();
                break;

            case 'Prueba gratuita': // Caso para fechas nulas
            case 'Única Vez':
                $dateEnd = null;
                break;

            default:
                $dateEnd = null;
        }

        $user = User::find(Auth::id());

        $amount = 0;
        if ($subscription->prices) {
            foreach (json_decode($subscription->prices) as $price) {
                if ($price->currency == 'PEN') {
                    $amount = $price->amount;
                }
            }
        }

        if ($stsubscription) {
            $stsubscription->update([
                'student_id' => $student->id,
                'subscription_id' => $subscription_id,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'status' => true,
                'notes' => null,
                'renewals' => true,
                'registration_user_id' => $user->id,
                'onli_sale_id' => null,
                'amount_paid' => $amount
            ]);
        } else {
            AcaStudentSubscription::create([
                'student_id' => $student->id,
                'subscription_id' => $subscription_id,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'status' => true,
                'notes' => null,
                'renewals' => 0,
                'registration_user_id' => $user->id,
                'onli_sale_id' => null,
                'amount_paid' => $amount
            ]);
        }
    }

    public function subscriptionDestroy($student_id, $subscription_id)
    {
        try {
            DB::beginTransaction();

            $deleted = AcaStudentSubscription::where('student_id', $student_id)
                ->where('subscription_id', $subscription_id)
                ->delete();

            DB::commit();

            return response()->json([
                'success' => $deleted > 0,
                'message' => $deleted ? 'Suscripción eliminada correctamente' : 'No se encontró la suscripción'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
