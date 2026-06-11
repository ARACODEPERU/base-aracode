<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    integration: {
        type: Object,
        default: () => ({})
    },
    endpoints: {
        type: Array,
        default: () => []
    }
});

const selectedEndpointId = ref(props.endpoints[0]?.id || null);

const selectedEndpoint = computed(() => {
    return props.endpoints.find(endpoint => endpoint.id === selectedEndpointId.value) || props.endpoints[0] || null;
});

const selectedFieldMaps = computed(() => {
    return (selectedEndpoint.value?.field_maps || selectedEndpoint.value?.fieldMaps || [])
        .filter(field => field.is_enabled !== false)
        .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
});

const phpString = value => `"${String(value || '').replace(/\\/g, '\\\\').replace(/"/g, '\\"')}"`;

const buildFieldLine = (field, value) => {
    return `            ${phpString(field.field_key)} => ${value},`;
};

const endpointName = computed(() => selectedEndpoint.value?.name || 'NombreEndpoint');

const controllerCode = computed(() => {
    const endpoint = phpString(endpointName.value);

    const fields = selectedFieldMaps.value.filter(f => f.field_key);
    const requiredFields = fields.filter(f => Boolean(f.is_required));
    const optionalFields = fields.filter(f => !f.is_required);

    const requiredLines = requiredFields.length
        ? requiredFields.map(f => buildFieldLine(f, `$request->input(${phpString(f.field_key)})`)).join('\n')
        : '            // No hay valores requeridos.';

    const optionalLines = optionalFields.length
        ? optionalFields.map(f => `            // ${buildFieldLine(f, 'null')}`).join('\n')
        : '            // No hay valores opcionales.';

    return `<?php

namespace App\\Http\\Controllers;

use Modules\\Integrationhub\\Http\\Controllers\\IntegrationhubController;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class ExampleIntegrationController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $hub = app(IntegrationhubController::class);

        return $hub->runEndpoint(
            ${endpoint},
            [
                // Valores requeridos
${requiredLines}

                // Valores opcionales
${optionalLines}

                // // custom_fields (opcional) — campos adicionales para el endpoint
                // 'custom_fields' => [
                //     'id_del_custom_field1' => 'no',                      //no he visto que funcione
                //     'id_del_custom_field2' => 'tiene',
                //     'id_del_custom_field3' => 'correo',
                //     'id_del_custom_field4' => 'ni nombre',
                // ],
            ],
            // [                          // ← extra_params (opcional)
            //     'path'  => [],
            //     'query' => [],
            //     'body'  => [
            //         "campo_extra" => "valor",
            //     ],
            //     'headers' => [],
            // ],
            // true,                      // ← track_results (default true)
            // 'batch_123',                // ← batch_id para agrupar ejecuciones (opcional)
        );
    }
}`;
});
</script>

<template>
    <div class="space-y-5">
        <div class="flex flex-col md:flex-row md:items-end gap-4">
            <div class="md:w-96">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Endpoint</label>
                <select v-model="selectedEndpointId" class="form-select">
                    <option v-for="endpoint in endpoints" :key="endpoint.id" :value="endpoint.id">
                        {{ endpoint.http_method }} - {{ endpoint.name }}
                    </option>
                </select>
            </div>
            <div v-if="selectedEndpoint" class="text-sm text-gray-500 dark:text-gray-400">
                {{ integration.name }} / {{ selectedEndpoint.name }}
            </div>
        </div>

        <div v-if="!selectedEndpoint" class="text-center py-10 text-gray-500 dark:text-gray-400">
            No hay endpoints configurados.
        </div>

        <template v-else>
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm text-blue-700 dark:text-blue-300">
                Usa <code>IntegrationhubController::runEndpoint()</code>. Solo pasas el nombre del endpoint y las variables como arrays — no necesitas construir objetos <code>Request</code> manualmente.
            </div>

            <div>
                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Uso desde Controller</h4>
                <pre class="bg-gray-100 dark:bg-zinc-900 p-4 rounded-lg text-xs font-mono overflow-x-auto max-h-[520px] text-gray-700 dark:text-gray-300">{{ controllerCode }}</pre>
            </div>
        </template>
    </div>
</template>
