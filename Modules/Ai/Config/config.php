<?php

/*
 * Configuración del módulo de inteligencia artificial.
 *
 * La fuente única de verdad de todos los ajustes es la tabla `parameters`
 * (códigos IA-...). Este archivo solo guarda los valores de respaldo que se
 * usan mientras un parámetro no existe o está vacío, además de los precios
 * estimados por modelo y las reglas de Aracodito.
 *
 * El módulo de IA y la pantalla de Parámetros del sistema leen y escriben las
 * mismas filas: cambiar un ajuste en cualquiera de las dos se refleja en la
 * otra sin duplicar configuración.
 */
return [
    'name' => 'Ai',

    /*
     * Códigos de la tabla `parameters` que administran la IA. Se listan todos
     * en un solo lugar para que IaSettings y el observador de parámetros no
     * repitan cadenas sueltas.
     */
    'parameters' => [
        'openai_api_key' => 'IA-00001',
        'provider' => 'IA-00002',
        'openai_agent_id' => 'IA-00003',
        'agent_timeout' => 'IA-00004',
        'openai_model' => 'IA-00005',
        'gemini_model' => 'IA-00006',
        'monthly_budget' => 'IA-00007',
        'daily_limit' => 'IA-00008',
        'store_prompts' => 'IA-00009',
        'max_output_tokens' => 'IA-00010',
        'temperature' => 'IA-00011',
        'gemini_api_key' => 'IA-00012',
        'budget_warning_percent' => 'IA-00013',
        'budget_hard_stop' => 'IA-00014',
    ],

    /*
     * Valores usados cuando el parámetro todavía no existe o quedó vacío.
     */
    'defaults' => [
        'openai_model' => 'gpt-4.1-mini',
        'gemini_model' => 'gemini-2.5-flash',
        'provider' => 'gemini',
        'monthly_budget_usd' => 0.0,
        'daily_limit' => 0,
        'store_prompts' => false,
        'max_output_tokens' => 800,
        'temperature' => 0.2,
        'budget_warning_percent' => 80,
        'budget_hard_stop' => true,
    ],

    'openai' => [
        'base_url' => 'https://api.openai.com/v1',
        'timeout' => (int) env('AI_OPENAI_TIMEOUT', 60),
        'instructions' => env(
            'AI_OPENAI_INSTRUCTIONS',
            'Responde en español, de forma breve y clara, usando únicamente la información del sistema.'
        ),
    ],

    'gemini' => [
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        'timeout' => (int) env('AI_GEMINI_TIMEOUT', 60),
    ],

    /*
     * Tarifas de respaldo en dólares por millón de tokens. La tabla
     * `ai_model_prices` es la que manda: estas cifras solo se usan cuando el
     * modelo consultado no tiene una fila activa.
     */
    'prices' => [
        'openai' => [
            'gpt-4.1-mini' => ['input' => 0.40, 'output' => 1.60],
            'gpt-4.1' => ['input' => 2.00, 'output' => 8.00],
        ],
        'gemini' => [
            'gemini-2.5-flash' => ['input' => 0.30, 'output' => 2.50],
            'gemini-2.5-pro' => ['input' => 1.25, 'output' => 10.00],
        ],
    ],

    /*
     * Aracodito solo ayuda con el uso del sistema: qué hace una pantalla, para
     * qué sirve una función, qué puede o no puede hacer un usuario según sus
     * permisos y los pasos para completar un proceso dentro del sistema. No es
     * un asistente general y no responde temas ajenos al sistema.
     */
    'aracodito' => [
        'solo_uso_del_sistema' => true,
        'instrucciones' => 'Eres Aracodito, el asistente del sistema. Solo ayudas con el uso del sistema: '
            . 'para qué sirve una pantalla o una función, cómo se hace un proceso dentro del sistema, '
            . 'qué puede o no puede hacer un usuario según sus permisos y los pasos para completar una tarea. '
            . 'No respondes preguntas generales, académicas, legales, médicas, financieras, contables, '
            . 'de programación ni de ningún tema ajeno al sistema. Si la pregunta no es sobre el uso del '
            . 'sistema, respondes únicamente que solo puedes ayudar a usar el sistema y ofreces las pantallas '
            . 'relacionadas. Nunca inventes pantallas, botones ni rutas que no estén en el mapa del sistema.',
        'mensaje_fuera_de_alcance' => 'Solo puedo ayudarte a usar el sistema: para qué sirve una pantalla, '
            . 'qué puede hacer un usuario o los pasos de un proceso. ¿Qué parte del sistema necesitas usar?',
    ],
];
