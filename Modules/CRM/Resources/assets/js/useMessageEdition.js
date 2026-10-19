import { onMounted, onUnmounted, ref } from 'vue';

// Ventana de edición: 90 segundos desde el envío del mensaje.
export const MESSAGE_EDIT_WINDOW_MS = 90 * 1000;

/**
 * Estado y acciones para editar mensajes propios.
 *
 * Cada chat tiene su propia forma de guardar los mensajes (text/content,
 * fromUserId/person_id), por eso la propiedad, el texto y la petición se
 * reciben como funciones. El backend valida de nuevo la propiedad y el tiempo.
 */
export function useMessageEdition({
    isOwnMessage,
    getMessageText,
    setMessageText,
    requestUpdate,
    notify = () => {},
}) {
    const nowTick = ref(Date.now());
    const editingMessageId = ref(null);
    const editingText = ref('');
    const isSavingEdit = ref(false);
    let timer = null;

    const withinEditWindow = (message) => {
        if (!message?.created_at) return false;

        const createdAt = new Date(message.created_at).getTime();
        if (Number.isNaN(createdAt)) return false;

        return nowTick.value - createdAt < MESSAGE_EDIT_WINDOW_MS;
    };

    const canEditMessage = (message) => {
        if (!message?.id || message.type !== 'text') return false;

        return isOwnMessage(message) && withinEditWindow(message);
    };

    const isEditingMessage = (message) =>
        String(editingMessageId.value) === String(message?.id);

    const startEditMessage = (message) => {
        if (!canEditMessage(message)) {
            notify('Solo puedes editar tus mensajes durante los primeros 90 segundos.', 'warning');
            return;
        }

        editingMessageId.value = message.id;
        editingText.value = getMessageText(message) ?? '';
    };

    const cancelEditMessage = () => {
        editingMessageId.value = null;
        editingText.value = '';
    };

    const saveEditedMessage = async (message) => {
        if (!isEditingMessage(message) || isSavingEdit.value) return;

        const text = (editingText.value ?? '').trim();
        if (!text) {
            notify('El mensaje no puede estar vacío.', 'warning');
            return;
        }

        isSavingEdit.value = true;

        try {
            const data = await requestUpdate(message, editingText.value);

            if (data?.success) {
                const actualizado = data.message ?? {};
                setMessageText(message, actualizado.text ?? editingText.value);
                message.edited = true;
                if (actualizado.updated_at) {
                    message.updated_at = actualizado.updated_at;
                }

                cancelEditMessage();
                notify('Mensaje actualizado.', 'success');
            } else {
                notify(data?.message || 'No se pudo editar el mensaje. Intenta nuevamente.', 'error');
            }
        } catch (error) {
            notify(
                error?.response?.data?.message || 'No se pudo editar el mensaje. Intenta nuevamente.',
                'error'
            );
        } finally {
            isSavingEdit.value = false;
        }
    };

    onMounted(() => {
        // Recalcula la ventana de 90 segundos para que el botón Editar
        // desaparezca solo, sin necesidad de recargar la conversación.
        timer = window.setInterval(() => {
            nowTick.value = Date.now();
        }, 1000);
    });

    onUnmounted(() => {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    });

    return {
        editingMessageId,
        editingText,
        isSavingEdit,
        canEditMessage,
        isEditingMessage,
        startEditMessage,
        cancelEditMessage,
        saveEditedMessage,
    };
}
