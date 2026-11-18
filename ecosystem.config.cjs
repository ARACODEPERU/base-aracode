/**
 * Procesos del sistema para pm2 (producción).
 *
 * Tres procesos son los que hacen que lo programado y lo encolado corra de
 * verdad:
 *
 *   - Aracode-integration-scheduler: `php artisan schedule:work`, el equivalente
 *     al `* * * * * php artisan schedule:run` del cron de Linux (pm2 lo
 *     reemplaza). De aquí salen las tareas de Académico, Tesorería,
 *     IntegrationHub, Salud y SUNAT.
 *   - Aracode-integration-queue: `queue:work` de las colas `default` y
 *     `exports`. En `default` viven los correos de suscripciones, los jobs de
 *     Tesorería, las notificaciones de Salud, las campañas académicas y las
 *     programaciones de IntegrationHub; `exports` trae las exportaciones de
 *     Excel (Ventas, CRM, Académico). `default` va primero para que un correo no
 *     espere detrás de una exportación.
 *   - Aracode-integration-imports: worker aparte para `imports` (cargas de
 *     Facturador3). Se separa porque cada chunk puede tardar hasta 10 minutos y
 *     no debe frenar los correos.
 *
 * `queue:work` sin `--queue` solo consume `default`: por eso las colas van
 * explícitas. Si algún día se despacha a otra cola con `onQueue(...)`, hay que
 * agregarla aquí.
 *
 * El worker se recicla solo con `--max-time` (libera memoria) y pm2 lo vuelve a
 * levantar porque `autorestart` está activo.
 *
 * Interprete: PHP 8.5 del servidor Linux (/usr/bin/php8.5). Este archivo es solo
 * para producción; en Windows/Laragon los workers se arrancan con `php artisan
 * queue:work --queue=...`.
 *
 * El archivo usa CommonJS (module.exports) y por eso la extensión es .cjs: con
 * "type": "module" en package.json, un .js se trataría como ESM y pm2 fallaría
 * al cargarlo ("module is not defined in ES module scope").
 *
 * Uso:  pm2 start ecosystem.config.cjs && pm2 save
 * Logs: pm2 logs Aracode-integration-queue / pm2 logs Aracode-integration-imports
 *
 * Si en el servidor ya existe el cron de Linux (`* * * * * php artisan
 * schedule:run`), el proceso Aracode-integration-scheduler sobra: se puede
 * borrar de este archivo y dejar solo los workers de la cola.
 *
 * Requisito: en el .env del servidor debe estar QUEUE_CONNECTION=database.
 */
module.exports = {
    apps: [
        {
            name: 'Aracode-integration-scheduler',
            script: 'artisan',
            interpreter: '/usr/bin/php8.5',
            args: 'schedule:work',
            cwd: __dirname,
            autorestart: true,
            max_restarts: 10,
            restart_delay: 5000,
        },
        {
            name: 'Aracode-integration-queue',
            script: 'artisan',
            interpreter: '/usr/bin/php8.5',
            // default y exports; `--timeout` cubre los jobs que no declaran el
            // suyo (exportaciones). Debe ser menor que retry_after (3900 s).
            args: 'queue:work --queue=default,exports --sleep=1 --tries=3 --timeout=300 --max-time=3600',
            cwd: __dirname,
            autorestart: true,
            max_restarts: 10,
            restart_delay: 5000,
        },
        {
            name: 'Aracode-integration-imports',
            script: 'artisan',
            interpreter: '/usr/bin/php8.5',
            // Un solo intento: los chunks de Facturador3 marcan el job como
            // fallido en su método failed() y reintentar 3 veces la carga
            // completa deja el job colgado horas.
            args: 'queue:work --queue=imports --sleep=2 --tries=1 --timeout=900 --max-time=7200',
            cwd: __dirname,
            autorestart: true,
            max_restarts: 10,
            restart_delay: 5000,
        },
    ],
};
