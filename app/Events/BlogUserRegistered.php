<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se dispara cuando un lector crea su cuenta desde el blog.
 *
 * Va separado del alta para que el envio a Integrationhub viva en un listener
 * y el controlador no mezcle dos responsabilidades.
 */
class BlogUserRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user)
    {
    }
}
