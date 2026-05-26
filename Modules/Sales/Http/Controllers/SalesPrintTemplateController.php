<?php

namespace Modules\Sales\Http\Controllers;

use App\Models\Parameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Sales\Support\SalesA4Template;

class SalesPrintTemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Sales::Administration/PrintTemplates', [
            'currentTemplate' => SalesA4Template::current(),
            'templateOptions' => SalesA4Template::options(),
            'affectedDocuments' => [
                'Notas de venta A4',
                'Boletas A4',
                'Facturas A4',
                'Notas de crédito A4',
                'Notas de débito A4',
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template' => 'required|in:'
                .SalesA4Template::CURRENT.','
                .SalesA4Template::MODERN.','
                .SalesA4Template::TEMPLATE_THREE.','
                .SalesA4Template::TEMPLATE_FOUR,
        ]);

        Parameter::updateOrCreate(
            ['parameter_code' => SalesA4Template::PARAMETER_CODE],
            [
                'description' => 'Plantilla A4 para impresión de documentos de ventas',
                'control_type' => 'rdj',
                'json_query_data' => json_encode([
                    [
                        'value' => SalesA4Template::CURRENT,
                        'label' => 'Formato plantilla 1',
                    ],
                    [
                        'value' => SalesA4Template::MODERN,
                        'label' => 'Formato plantilla 2',
                    ],
                    [
                        'value' => SalesA4Template::TEMPLATE_THREE,
                        'label' => 'Formato plantilla 3',
                    ],
                    [
                        'value' => SalesA4Template::TEMPLATE_FOUR,
                        'label' => 'Formato plantilla 4',
                    ],
                ]),
                'value_default' => $validated['template'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Plantilla A4 actualizada correctamente.',
        ]);
    }
}
