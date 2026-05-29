/**
 * Normaliza base64 crudo o data URL a data URL listo para <img> y backend Laravel.
 */
export function toDataUrl(value, mimeType = 'image/png') {
    if (!value || typeof value !== 'string') {
        return null;
    }

    if (value.startsWith('data:')) {
        return value;
    }

    return `data:${mimeType};base64,${value}`;
}

export function extractAiImagePayload(data) {
    const raw = data?.imageBase64 ?? data?.image ?? data?.base64 ?? null;
    const mimeType = data?.mimeType ?? 'image/png';

    return {
        raw,
        previewSrc: toDataUrl(raw, mimeType),
    };
}

export function aiErrorMessage(error) {
    let errorMessage = 'Error al generar la imagen.';

    if (error.response?.data?.error?.message) {
        const originalMessage = error.response.data.error.message;
        if (originalMessage.includes('You exceeded your current quota')) {
            return 'Has excedido tu cuota actual de la API de IA. Reintenta en unos minutos o verifica tu plan.';
        }

        return originalMessage;
    }

    if (error.response?.data?.message) {
        return error.response.data.message;
    }

    if (error.message) {
        return error.message;
    }

    return errorMessage;
}
