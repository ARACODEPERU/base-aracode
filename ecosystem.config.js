/**
 * Procesos de Integrationhub para pm2 (producción).
 *
 * Dos procesos son los que hacen que las programaciones corran de verdad:
 *
 *   - integration-scheduler: `php artisan schedule:work`, el equivalente al
 *     `* * * * * php artisan schedule:run` del cron de Linux (pm2 lo reemplaza).
 *   - integration-queue: `php artisan queue:work`, el worker de la cola
 *     `database` donde viven los jobs de cada programación.
 *
 * Uso:  pm2 start ecosystem.config.js && pm2 save
 * Logs: pm2 logs integration-scheduler / pm2 logs integration-queue
 *
 * Si en el servidor ya existe el cron de Linux (`* * * * * php artisan
 * schedule:run`), el proceso integration-scheduler sobra: se puede borrar de
 * este archivo y dejar solo el worker de la cola.
 */
module.exports = {
    apps: [
        {
            name: 'integration-scheduler',
            script: 'php',
            args: 'artisan schedule:work',
            cwd: __dirname,
            autorestart: true,
            max_restarts: 10,
            restart_delay: 5000,
        },
        {
            name: 'integration-queue',
            script: 'php',
            args: 'artisan queue:work --sleep=1 --tries=3 --max-time=3600',
            cwd: __dirname,
            autorestart: true,
            max_restarts: 10,
            restart_delay: 5000,
        },
    ],
};
