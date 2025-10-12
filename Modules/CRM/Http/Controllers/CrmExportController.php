<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExcelExportJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CrmExportController extends Controller
{
    public function exportStatus($id)
    {
        $excelExportJob = ExcelExportJob::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$excelExportJob) {
            return response()->json(['message' => 'Exportación no encontrada o no autorizada.'], 404);
        }

        return response()->json($excelExportJob);
    }
}
