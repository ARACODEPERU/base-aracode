<?php

namespace App\Http\Controllers;

use App\Models\Parameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class ParametersController extends Controller
{
    /**
     * Tipo de control de los parametros confidenciales (credenciales).
     *
     * Se guardan como cualquier otro parametro, pero la pantalla nunca los
     * vuelve a mostrar: solo indica que hay un valor guardado.
     */
    private const SECRET_CONTROL_TYPE = 'pwd';

    /**
     * Centinela de la mascara: si la interfaz manda este texto (o un valor
     * vacio) no se pisa el valor guardado.
     */
    private const SECRET_PLACEHOLDER = '********';

    public function index()
    {
        $parameters = (new Parameter())->newQuery();

        if (request()->has('search')) {
            $parameters->where('description', 'Like', '%' . request()->input('search') . '%');
        }
        $parameters = $parameters->get();

        $formatted = [];

        foreach ($parameters as $parameter) {
            $json_query_data = [];
            if ($parameter->control_type == 'sq') {
                $json_query_data = $this->getSubQuery($parameter->json_query_data);
            } else {
                $data = json_decode($parameter->json_query_data);
                // Verifica si la decodificación fue exitosa
                if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                    // El JSON es inválido
                    $json_query_data = null;
                } else {
                    $json_query_data = $parameter->json_query_data;
                }
            }

            // Verificar estado de sincronizacion para archivos (P000026, P000027)
            $sync_status = $this->getFileSyncStatus($parameter);

            // Interruptores (chx): normalizar a '1'/'0' para que el checkbox
            // refleje el estado real guardado en BD ('true', '1', etc.).
            $value_default = $parameter->value_default;
            if ($parameter->control_type == 'chx') {
                $value_default = in_array(strtolower(trim((string) $value_default)), ['1', 'true']) ? '1' : '0';
            }

            // Confidenciales (pwd): el valor NO viaja al navegador. Solo se
            // informa si ya hay uno guardado para que la pantalla lo indique.
            $is_secret = $parameter->control_type === self::SECRET_CONTROL_TYPE;
            $has_value = trim((string) $parameter->value_default) !== '';

            if ($is_secret) {
                $value_default = null;
            }

            array_push($formatted, [
                'id' => $parameter->id,
                'parameter_code' => $parameter->parameter_code,
                'description' => $parameter->description,
                'control_type' => $parameter->control_type,
                'json_query_data' => $json_query_data,
                'value_default' => $value_default,
                'is_secret' => $is_secret,
                'has_value' => $has_value,
                'sync_status' => $sync_status,
            ]);
        }
        //dd($formatted);
        return Inertia::render('Parameters/List', [
            'parameters' => $formatted
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

        $is_secret = $this->isSecret($parameter);
        $has_value = $this->hasStoredValue($parameter);

        // El valor de un parametro confidencial no viaja al formulario: solo el
        // aviso de que ya hay uno guardado.
        if ($is_secret && $parameter) {
            $parameter->value_default = null;
        }

        return Inertia::render('Parameters/Edit', [
            'parameter' => $parameter,
            'is_secret' => $is_secret,
            'has_value' => $has_value,
        ]);
    }

    public function update(Request $request, $id)
    {
        $parameter = Parameter::find($id);
        $is_secret = $this->isSecret($parameter);

        $this->validate($request, [
            'parameter_code'        => 'required|max:10',
            'parameter_code'        => 'unique:parameters,parameter_code,' . $id,
            'description'           => 'required|max:255',
            'control_type'          => 'required',
            // En un confidencial el campo vacio significa "no lo cambies".
            'value_default'         => $is_secret ? 'nullable' : 'required'
        ]);

        $valor_seguro = $request->get('value_default');

        if($request->get('control_type') == 'tx'){
            $value_default = $request->get('value_default');
            // Convertir los caracteres especiales a entidades HTML
            $valor_seguro = htmlspecialchars($value_default, ENT_QUOTES, 'UTF-8');
        }

        if (! $parameter) {
            return;
        }

        $attributes = [
            'parameter_code'        => $request->get('parameter_code'),
            'description'           => $request->get('description'),
            'control_type'          => $request->get('control_type'),
            'json_query_data'       => $request->get('json_query_data'),
        ];

        // Un confidencial que llega vacio conserva su valor guardado.
        if (! ($is_secret && ! $this->isNewSecretValue((string) $valor_seguro))) {
            $attributes['value_default'] = $valor_seguro;
        }

        $parameter->update($attributes);

        // Invalidar caches que dependen de valores de parametros (ej: API Key de OpenAI en P000025)
        Cache::forget('academic:openai-api-key:' . $request->get('parameter_code'));
        Log::info('Parametro actualizado, cache de API key invalidada', ['parameter_code' => $request->get('parameter_code')]);

        // Sincronizar archivos (robots.txt, llms.txt)
        $this->syncFileFromParameter($parameter);
    }

    public function getSubQuery($json_query_data)
    {
        $result  = DB::select($json_query_data);

        return json_encode($result);
    }

    public function updateDefaultValue($id, $val)
    {
        $parameter = Parameter::find($id);

        if (! $parameter) {
            return;
        }

        // Confidencial sin valor nuevo: se conserva el guardado.
        if ($this->isSecret($parameter) && ! $this->isNewSecretValue((string) $val)) {
            return;
        }

        $parameter->update([
            'value_default' => $val
        ]);

        // Invalidar caches que dependen de valores de parametros (ej: API Key de OpenAI en P000025)
        Cache::forget('academic:openai-api-key:' . $parameter->parameter_code);

        // Sincronizar archivos (robots.txt, llms.txt)
        $this->syncFileFromParameter($parameter);
    }

    /**
     * Endpoint POST para guardar valores largos (textareas como robots.txt / llms.txt)
     * desde la lista de parametros, evitando el limite de longitud de URL en GET.
     *
     * Tambien es el endpoint que usa la lista para los parametros confidenciales
     * (pwd): un valor vacio o la mascara NO sobrescriben lo guardado.
     */
    public function updateDefaultValuePost(Request $request, $id)
    {
        $parameter = Parameter::find($id);

        if (! $parameter) {
            return response()->json(['success' => false, 'message' => 'El parametro no existe.'], 404);
        }

        $value = (string) $request->input('value_default', '');

        if ($this->isSecret($parameter) && ! $this->isNewSecretValue($value)) {
            return response()->json([
                'success' => true,
                'unchanged' => true,
                'has_value' => $this->hasStoredValue($parameter),
            ]);
        }

        $parameter->update([
            'value_default' => $value
        ]);

        Cache::forget('academic:openai-api-key:' . $parameter->parameter_code);
        $this->syncFileFromParameter($parameter);

        return response()->json([
            'success' => true,
            'unchanged' => false,
            'has_value' => trim($value) !== '',
        ]);
    }

    /**
     * true si el parametro es confidencial (credenciales que no se muestran).
     */
    private function isSecret(?Parameter $parameter): bool
    {
        return $parameter !== null && $parameter->control_type === self::SECRET_CONTROL_TYPE;
    }

    /**
     * true si el parametro ya tiene un valor guardado.
     */
    private function hasStoredValue(?Parameter $parameter): bool
    {
        return $parameter !== null && trim((string) $parameter->value_default) !== '';
    }

    /**
     * true si el valor recibido es un valor nuevo de verdad (no vacio y no la
     * mascara). Es lo unico que puede reemplazar un valor confidencial.
     */
    private function isNewSecretValue(string $value): bool
    {
        $value = trim($value);

        return $value !== '' && $value !== self::SECRET_PLACEHOLDER;
    }

    /**
     * Sincroniza el contenido del parametro con su archivo correspondiente en public/.
     * Aplica solo para P000026 (robots.txt) y P000027 (llms.txt).
     */
    private function syncFileFromParameter(Parameter $parameter): void
    {
        $fileMap = [
            'P000026' => 'robots.txt',
            'P000027' => 'llms.txt',
        ];

        if (!isset($fileMap[$parameter->parameter_code])) {
            return;
        }

        $fileName = $fileMap[$parameter->parameter_code];
        $filePath = public_path($fileName);
        $content = $parameter->value_default ?? '';

        try {
            File::put($filePath, $content);
            Log::info("Archivo {$fileName} sincronizado desde parametro {$parameter->parameter_code}");
        } catch (\Exception $e) {
            Log::error("Error al sincronizar {$fileName}: " . $e->getMessage());
        }
    }

    /**
     * Verifica si el valor del parametro coincide con el contenido del archivo.
     * Retorna 'Actualizado' si coinciden, 'Pendiente' si son diferentes, o null si no aplica.
     */
    private function getFileSyncStatus(Parameter $parameter): ?string
    {
        $fileMap = [
            'P000026' => 'robots.txt',
            'P000027' => 'llms.txt',
        ];

        if (!isset($fileMap[$parameter->parameter_code])) {
            return null;
        }

        $fileName = $fileMap[$parameter->parameter_code];
        $filePath = public_path($fileName);

        if (!File::exists($filePath)) {
            return 'Pendiente';
        }

        $fileContent = File::get($filePath);
        $paramContent = $parameter->value_default ?? '';

        // Normalizar saltos de linea para comparacion
        $fileContentNormalized = str_replace("\r\n", "\n", $fileContent);
        $paramContentNormalized = str_replace("\r\n", "\n", $paramContent);

        return $fileContentNormalized === $paramContentNormalized ? 'Actualizado' : 'Pendiente';
    }
}
