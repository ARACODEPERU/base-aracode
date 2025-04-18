<?php

namespace Modules\Academic\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaStudent;

class AcademicController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('academic::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function studentsEnrolledMonth()
    {
        $currentYear = Carbon::now()->year;

        $studentsPerMonth = AcaCapRegistration::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as count')
        )
            ->whereYear('created_at', $currentYear)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('month')
            ->get();

        $studentsPerMonthFormatted = [];
        foreach ($studentsPerMonth as $item) {
            array_push($studentsPerMonthFormatted, [
                'month' => $item->month,
                'count' => $item->count
            ]);
        }

        return response()->json([
            'students' => $studentsPerMonthFormatted
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function getStudentsCourses(Request $request)
    {
        $courses = DB::table('aca_cap_registrations as r')
            ->join('aca_students as s', 'r.student_id', '=', 's.id')
            ->join('people as p', 's.person_id', '=', 'p.id')
            ->join('aca_courses as c', 'r.course_id', '=', 'c.id')
            ->select(
                'r.course_id',
                'c.description as curso',
                DB::raw('COUNT(r.student_id) as total_estudiantes'),
                DB::raw('SUM(CASE WHEN p.gender = "M" THEN 1 ELSE 0 END) as men'),
                DB::raw('SUM(CASE WHEN p.gender = "F" THEN 1 ELSE 0 END) as women')
            )
            ->where('r.status', true)
            ->groupBy('r.course_id', 'c.description')
            ->orderBy('total_estudiantes', 'desc')
            ->limit(10)
            ->get();

        // Barajar (shuffle) la colección para que se muestre de forma aleatoria
        $courses = $courses->shuffle();

        return response()->json([
            'courses' => $courses
        ]);
    }


    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('academic::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('academic::edit');
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
}
