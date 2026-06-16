<?php

namespace App\Http\Controllers;

use App\Models\Parameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ParametersController extends Controller
{
    public function index()
    {
        $parameters = (new Parameter())->newQuery();

        if (request()->has('search')) {
            $parameters->where('description', 'Like', '%' . request()->input('search') . '%');
        }
        $parameters = $parameters->orderBy('parameter_code')->get();

        $formatted = [];

        foreach ($parameters as $parameter) {
            $options = $this->resolveOptions($parameter->control_type, $parameter->json_query_data);

            $formatted[] = [
                'id' => $parameter->id,
                'parameter_code' => $parameter->parameter_code,
                'description' => $parameter->description,
                'control_type' => $parameter->control_type,
                'json_query_data' => $parameter->json_query_data,
                'options' => $options,
                'value_default' => $parameter->value_default,
                'selected_values' => $this->parseMultiValue($parameter->value_default),
            ];
        }

        return Inertia::render('Parameters/List', [
            'parameters' => $formatted,
            'filters' => request()->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Parameters/Create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'parameter_code'        => 'required|max:10',
            'parameter_code'        => 'unique:parameters,parameter_code',
            'description'           => 'required|max:255',
            'control_type'          => 'required',
            'value_default'         => 'required'
        ]);

        $valor_seguro = $request->get('value_default');

        if($request->get('control_type') == 'tx'){
            $value_default = $request->get('value_default');
            // Convertir los caracteres especiales a entidades HTML
            $valor_seguro = htmlspecialchars($value_default, ENT_QUOTES, 'UTF-8');
        }

        Parameter::create([
            'parameter_code'        => $request->get('parameter_code'),
            'description'           => $request->get('description'),
            'control_type'          => $request->get('control_type'),
            'json_query_data'       => $request->get('json_query_data'),
            'value_default'         => $valor_seguro
        ]);
    }

    public function edit($id)
    {
        $parameter = Parameter::find($id);
        return Inertia::render('Parameters/Edit', [
            'parameter' => $parameter
        ]);
    }

    public function update(Request $request, $id)
    {

        $this->validate($request, [
            'parameter_code'        => 'required|max:10',
            'parameter_code'        => 'unique:parameters,parameter_code,' . $id,
            'description'           => 'required|max:255',
            'control_type'          => 'required',
            'value_default'         => 'required'
        ]);

        $valor_seguro = $request->get('value_default');

        if($request->get('control_type') == 'tx'){
            $value_default = $request->get('value_default');
            // Convertir los caracteres especiales a entidades HTML
            $valor_seguro = htmlspecialchars($value_default, ENT_QUOTES, 'UTF-8');
        }

        Parameter::find($id)->update([
            'parameter_code'        => $request->get('parameter_code'),
            'description'           => $request->get('description'),
            'control_type'          => $request->get('control_type'),
            'json_query_data'       => $request->get('json_query_data'),
            'value_default'         => $valor_seguro
        ]);
    }

    public function getSubQuery($json_query_data)
    {
        $result = DB::select($json_query_data);

        return json_encode($result);
    }

    public function updateDefaultValue(Request $request, $id): JsonResponse
    {
        $parameter = Parameter::findOrFail($id);
        $value = $request->input('value', $request->route('val'));

        if (in_array($parameter->control_type, ['chq', 'chj'], true)) {
            if (is_array($value)) {
                $value = json_encode(array_values(array_map('strval', $value)));
            } elseif (is_string($value) && str_starts_with(trim($value), '[')) {
                $decoded = json_decode($value, true);
                $value = is_array($decoded)
                    ? json_encode(array_values(array_map('strval', $decoded)))
                    : $value;
            }
        } elseif ($parameter->control_type === 'tx') {
            $value = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        } elseif ($parameter->control_type === 'chx') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        } else {
            $value = is_scalar($value) || $value === null ? (string) $value : json_encode($value);
        }

        $parameter->update([
            'value_default' => $value,
        ]);

        return response()->json([
            'success' => true,
            'value_default' => $parameter->fresh()->value_default,
            'selected_values' => $this->parseMultiValue($parameter->fresh()->value_default),
        ]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function resolveOptions(?string $controlType, ?string $jsonQueryData): array
    {
        if (! in_array($controlType, ['sq', 'chq', 'sa', 'chj', 'rdj'], true) || empty($jsonQueryData)) {
            return [];
        }

        if (in_array($controlType, ['sq', 'chq'], true)) {
            return $this->resolveQueryOptions($jsonQueryData);
        }

        return $this->resolveJsonOptions($jsonQueryData);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function resolveQueryOptions(string $query): array
    {
        $trimmed = trim($query);

        if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
            return $this->resolveJsonOptions($query);
        }

        return $this->mapRowsToOptions(DB::select($query));
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function resolveJsonOptions(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_map(function ($item) {
            if (! is_array($item)) {
                $stringValue = (string) $item;

                return ['value' => $stringValue, 'label' => $stringValue];
            }

            $value = $item['value'] ?? $item['id'] ?? $item['identifier'] ?? null;
            $label = $item['label'] ?? $item['description'] ?? $item['name'] ?? $value;

            return [
                'value' => (string) $value,
                'label' => (string) $label,
            ];
        }, $decoded));
    }

    /**
     * @param  array<int, object>  $rows
     * @return array<int, array{value: string, label: string}>
     */
    protected function mapRowsToOptions(array $rows): array
    {
        return array_values(array_map(function ($row) {
            $row = (array) $row;
            $value = $row['id'] ?? $row['identifier'] ?? $row['value'] ?? reset($row);
            $label = $row['description'] ?? $row['label'] ?? $row['name'] ?? (string) $value;

            return [
                'value' => (string) $value,
                'label' => (string) $label,
            ];
        }, $rows));
    }

    /**
     * @return array<int, string>
     */
    protected function parseMultiValue(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return array_values(array_map('strval', $decoded));
        }

        if (preg_match_all("/['\"]([^'\"]+)['\"]/", $value, $matches)) {
            return array_values($matches[1]);
        }

        return [(string) $value];
    }
}
