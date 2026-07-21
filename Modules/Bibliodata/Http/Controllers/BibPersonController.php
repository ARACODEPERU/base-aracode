<?php

namespace Modules\Bibliodata\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Bibliodata\Entities\BibPerson;

class BibPersonController extends Controller
{
    public function searchByNumberType(Request $request)
    {
        $searchBy      = $request->input('searchBy') ?? 1;
        $document_type = $request->input('document_type');
        $number        = $request->input('number');
        $full_name     = $request->input('full_name');

        $this->validate($request, [
            'searchBy'      => 'nullable|in:1,2',
            'document_type' => 'required_if:searchBy,1',
            'number'        => 'required_if:searchBy,1',
            'full_name'     => 'required_if:searchBy,2',
        ], [
            'document_type.required_if' => 'Para la búsqueda por número es necesario elegir el tipo de documento.',
            'number.required_if'        => 'Para la búsqueda por número es necesario ingresar un número.',
            'full_name.required_if'     => 'Para la búsqueda por nombre o razón social es obligatorio ingresar un nombre o razón social.',
        ]);

        $query = BibPerson::query()->with(['author']);

        if ($searchBy == 1) {
            $person = $query->where('document_type_id', $document_type)
                ->where('number', $number)
                ->first();
        } else {
            $person = $query->where('full_name', 'LIKE', "%{$full_name}%")->get();
        }

        $status = true;
        $alert  = 'No existen datos para la búsqueda';
        $ubigeo = [];

        if ($searchBy == 1) {
            if ($person) {
                $alert = null;
                $ubigeo = [
                    'district_id' => $person->ubigeo,
                    'city_name'   => $person->ubigeo_description,
                ];
            } else {
                $status = false;
            }
        }

        if ($searchBy == 2) {
            if ($person->count() > 0) {
                $alert = null;
            } else {
                $status = false;
            }
        }

        return response()->json([
            'status'        => $status,
            'person'        => $person,
            'document_type' => '',
            'number'        => '',
            'alert'         => $alert,
            'ubigeo'        => $ubigeo,
        ]);
    }
}
