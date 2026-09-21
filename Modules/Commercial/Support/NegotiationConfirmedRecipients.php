<?php

namespace Modules\Commercial\Support;

use App\Models\User;
use App\Support\MailSender;
use Modules\Commercial\Database\Seeders\CommercialDatabaseSeeder;
use Modules\Commercial\Entities\CommercialNegotiation;

/**
 * Destinatarios del aviso que sale cuando el cliente abre su enlace y envia sus
 * datos (negociacion "confirmada").
 *
 * Van tres grupos, porque el proceso de 9 pasos no debe quedarse detenido:
 *  - el asesor que creo la negociacion (created_by), que es quien atendio al cliente;
 *  - todos los usuarios con rol administrador, para que cualquiera pueda retomarlo;
 *  - el buzon de administracion del .env (MAIL_ADMIN), que recibe el aviso aunque
 *    ningun usuario tenga el rol asignado.
 *
 * Los roles administradores se toman de CommercialDatabaseSeeder::ADMIN_ROLES
 * ('admin' y 'Administrador'): son los mismos a los que las migraciones y el seeder
 * del modulo entregan 'comm_negociaciones_verificar', el permiso que permite aprobar,
 * rechazar y ejecutar el proceso. Consultar por nombre de rol y no por id es la
 * convencion del modulo: el id cambia entre entornos.
 */
class NegotiationConfirmedRecipients
{
    /**
     * Correos de los administradores mas el del asesor que creo la negociacion.
     *
     * @return array<int, string>
     */
    public static function forNegotiation(CommercialNegotiation $negotiation): array
    {
        $adminEmails = User::query()
            ->role(CommercialDatabaseSeeder::ADMIN_ROLES)
            ->pluck('email');

        // El buzon de administracion del .env se suma a los que tienen rol: es un
        // destinatario mas, no un reemplazo. MailSender::adminAddress() nunca
        // devuelve vacio (aplica respaldo si el .env no sirve).
        return self::merge(
            self::withAdminAddress($adminEmails),
            $negotiation->creator?->email
        );
    }

    /**
     * Suma el buzon de administracion (MailSender::adminAddress()) a una lista de
     * correos.
     *
     * Se mantiene como metodo aparte, y sin tocar la base de datos, para poder
     * probar la regla del .env (MAIL_ADMIN y su respaldo) sin depender de MySQL.
     *
     * @param  iterable<mixed>  $emails
     * @return array<int, mixed>
     */
    public static function withAdminAddress(iterable $emails): array
    {
        $list = is_array($emails) ? array_values($emails) : iterator_to_array($emails, false);

        $list[] = MailSender::adminAddress();

        return $list;
    }

    /**
     * Une, limpia y deduplica los correos: el asesor suele ser tambien administrador
     * y no debe recibir el mismo aviso dos veces.
     *
     * @param  iterable<mixed>  $adminEmails
     * @return array<int, string>
     */
    public static function merge(iterable $adminEmails, ?string $advisorEmail = null): array
    {
        $candidates = is_array($adminEmails) ? $adminEmails : iterator_to_array($adminEmails, false);

        if ($advisorEmail !== null) {
            $candidates[] = $advisorEmail;
        }

        $emails = [];

        foreach ($candidates as $email) {
            if (! is_string($email)) {
                continue;
            }

            $email = mb_strtolower(trim($email));

            // Un correo vacio o mal escrito dejaria a esa persona sin el aviso, y con
            // un destinatario invalido el envio completo falla para todos.
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $emails[$email] = true;
        }

        return array_keys($emails);
    }
}
