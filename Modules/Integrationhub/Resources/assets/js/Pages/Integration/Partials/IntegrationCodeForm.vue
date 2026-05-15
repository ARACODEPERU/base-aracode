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

const phpIdentifier = (value, fallback = 'valor') => {
    const identifier = String(value || fallback)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9_]+/g, '_')
        .replace(/^_+|_+$/g, '')
        .replace(/_{2,}/g, '_');

    const safeIdentifier = identifier || fallback;

    return /^[a-z_]/.test(safeIdentifier) ? safeIdentifier : `_${safeIdentifier}`;
};

const integrationAlias = computed(() => phpIdentifier(props.integration.name, 'nombre_conexion'));

const selectedEndpointAlias = computed(() => phpIdentifier(selectedEndpoint.value?.name, 'nombre_endpoint'));

const buildFieldLine = (field, value) => {
    return `            ${phpString(field.field_key)} => ${value},`;
};

const usageVariablesCode = computed(() => {
    const fields = selectedFieldMaps.value.filter(field => field.field_key);
    const requiredFields = fields.filter(field => Boolean(field.is_required));
    const optionalFields = fields.filter(field => !field.is_required);
    const lines = [];

    lines.push('            // Valores requeridos');
    if (requiredFields.length) {
        requiredFields.forEach(field => {
            lines.push(buildFieldLine(field, `$request->input(${phpString(field.field_key)})`));
        });
    } else {
        lines.push('            // No hay valores requeridos configurados para este endpoint.');
    }

    lines.push('');
    lines.push('            // Valores opcionales');
    if (optionalFields.length) {
        optionalFields.forEach(field => {
            lines.push(buildFieldLine(field, 'null'));
        });
    } else {
        lines.push('            // No hay valores opcionales configurados para este endpoint.');
    }

    lines.push('');
    lines.push('            // Valores extra');
    lines.push('            // "tracking_id" => $request->input("tracking_id"),');
    lines.push('            // "metadata" => [');
    lines.push('            //     "source" => "web",');
    lines.push('            //     "tags" => ["nuevo", "cliente"],');
    lines.push('            // ],');

    return lines.join("\n");
});

const controllerCode = computed(() => `<?php

namespace App\\Http\\Controllers;

use App\\Services\\Integrationhub;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class ExampleIntegrationController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $result = Integrationhub::${integrationAlias.value}()->${selectedEndpointAlias.value}([
${usageVariablesCode.value}
        ]);

        return response()->json($result);
    }
}`);
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
                Usa la clase <code>App\Services\Integrationhub</code>. El metodo es el nombre normalizado de la integracion y luego el endpoint; solo envia variables.
            </div>

            <div>
                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Uso desde Controller</h4>
                <pre class="bg-gray-100 dark:bg-zinc-900 p-4 rounded-lg text-xs font-mono overflow-x-auto max-h-[520px] text-gray-700 dark:text-gray-300">{{ controllerCode }}</pre>
            </div>
        </template>
    </div>
</template>
