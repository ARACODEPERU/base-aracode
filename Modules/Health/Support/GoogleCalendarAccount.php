<?php

namespace Modules\Health\Support;

/**
 * Cuenta de Google Calendar a la que se escriben las citas.
 *
 * La cuenta principal del consultorio tiene `doctorId` nulo; la de cada doctor
 * que enlazo su propio calendario lleva el id del doctor. Con esto el servicio
 * de Google usa el refresh token y el calendario correctos sin depender de un
 * unico parametro global.
 */
final class GoogleCalendarAccount
{
    public function __construct(
        public readonly string $refreshToken,
        public readonly string $calendarId,
        public readonly ?int $doctorId = null,
    ) {
    }

    /**
     * true si es la agenda principal del consultorio.
     */
    public function isPrincipal(): bool
    {
        return $this->doctorId === null;
    }

    /**
     * Clave estable para la cache del access token (una por cuenta).
     */
    public function cacheKey(): string
    {
        return $this->isPrincipal()
            ? 'principal'
            : 'doctor.' . $this->doctorId;
    }
}
