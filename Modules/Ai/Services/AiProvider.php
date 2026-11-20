<?php

namespace Modules\Ai\Services;

use Modules\Ai\Support\AiResult;

/**
 * Contrato de un proveedor de IA de texto.
 *
 * Cada proveedor traduce el mensaje al formato de su API y devuelve un
 * AiResult con el texto, los tokens y el costo estimado.
 */
interface AiProvider
{
    /**
     * Identificador del proveedor: 'openai' o 'gemini'.
     */
    public function name(): string;

    /**
     * Modelo configurado para este proveedor.
     */
    public function model(): string;

    /**
     * Envía una consulta y devuelve la respuesta normalizada.
     *
     * Opciones admitidas: model, instructions, temperature,
     * max_output_tokens y previous_response_id (solo OpenAI).
     *
     * @param  array<string, mixed>  $options
     */
    public function chat(string $message, array $options = []): AiResult;
}
