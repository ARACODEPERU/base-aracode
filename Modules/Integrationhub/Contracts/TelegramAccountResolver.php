<?php

namespace Modules\Integrationhub\Contracts;

/**
 * Traduce el correo y el documento que una persona escribe en el chat del bot a
 * la ficha que puede consultar sus cursos y certificados.
 *
 * Es un contrato aparte del de registro (TelegramRegistrantResolver) a
 * proposito: el alta del chat_id y la consulta de datos privados son dos
 * capacidades distintas y un modulo puede ofrecer una sin la otra. Integrationhub
 * no conoce el padron de ningun modulo: cada modulo que tenga personas
 * registradas vincula aqui su implementacion (Academic lo hace con
 * TelegramStudentAccount) y el webhook la resuelve desde el contenedor. Si no hay
 * implementacion vinculada, el bot avisa que la consulta no esta disponible en
 * lugar de fallar.
 */
interface TelegramAccountResolver
{
    /**
     * Ficha de consulta de la persona duena del documento, si el correo coincide.
     *
     * Devuelve null tanto cuando el documento no existe como cuando el correo no
     * le pertenece: el bot responde lo mismo en los dos casos para no revelar si
     * el documento esta en el padron ni a quien pertenece.
     *
     * @return array{
     *     person_id: int,
     *     name: string,
     *     courses: array<int, array{description: string, type: string|null, time_limit: string|null}>,
     *     subscription: array{vip: bool, ends_at: string|null}|null,
     *     certificates: array<int, array{id: int, course: string, module: string|null}>,
     *     platform_url: string
     * }|null
     *
     * Ojo: los certificados vienen sin enlace de descarga a proposito. El bot
     * solo informa cuales existen y manda a la persona a la plataforma
     * (platform_url) para bajar el archivo.
     */
    public function resolveAccount(string $document, string $email): ?array;
}
