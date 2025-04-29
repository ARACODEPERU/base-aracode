<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AcaRegistrationExists implements Rule
{
    protected $parameter;


    public function __construct($parameter)
    {
        $this->parameter = $parameter;
    }

    public function passes($attribute, $value)
    {
        $result = false;

        $exists = DB::table('aca_cap_registrations')
            ->where('student_id', $value)
            ->where('course_id', $this->parameter)
            ->exists();

        // $courseFree = DB::table('aca_courses')
        //     ->where('id', $this->parameter)
        //     ->where(function ($query) {
        //         $query->where('price', '=', 0)
        //             ->orWhereNull('price');
        //     })
        //     ->exists();
        $courseFree = false;
        if ($courseFree) {
            $result =  true;
        } elseif ($exists) {
            $result =  true;
        }

        return $result;
    }

    public function message()
    {
        return 'El alumno no tiene una matricula registrada para el curso elejido.';
    }
}
