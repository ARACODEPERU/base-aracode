<?php

namespace Modules\Integrationhub\Contracts;

/**
 * Traduce el documento que una persona escribe en el chat del bot a la ficha
 * que puede recibir avisos.
 *
 * Integrationhub no conoce el padron de ningun modulo: cada modulo que tenga
 * personas registradas vincula aqui su implementacion (Academic lo hace con
 * TelegramStudentDirectory) y el webhook la resuelve desde el contenedor. Si no
 * hay implementacion vinculada, el bot avisa que el registro no esta habilitado
 * en lugar de fallar.
 */
interface TelegramRegistrantResolver
{
    /**
     * Ficha de la persona duena del documento.
     *
     * Devuelve null cuando el documento no pertenece a nadie del padron (el bot
     * responde con un mensaje generico). Cuando la persona existe pero no tiene
     * acceso, devuelve su ficha con "programs" vacio y "subscription" en false,
     * para poder distinguir el caso sin exponer informacion de terceros.
     *
     * @return array{person_id: int, name: string, programs: array<int, string>, subscription: bool}|null
     */
    public function resolveByDocument(string $document): ?array;
}
