<?php

namespace App\Listeners;

use App\Events\BlogUserRegistered;
use App\Services\BlogRegistrationIntegration;

/**
 * Recibe el alta del blog y la envia al Integrationhub.
 *
 * Se ejecuta en linea (no en cola) para cumplir con "inmediatamente despues
 * del registro", pero todo el trabajo esta protegido dentro del servicio: un
 * fallo de la integracion no interrumpe ni revierte el alta del usuario.
 */
class SendBlogUserToIntegrationhub
{
    public function handle(BlogUserRegistered $event): void
    {
        app(BlogRegistrationIntegration::class)->send($event->user);
    }
}
