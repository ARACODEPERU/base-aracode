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
 * Logs: pm2 logs <prefijo>-integration-queue / pm2 logs <prefijo>-integration-imports
 *
 * Si en el servidor ya existe el cron de Linux (`* * * * * php artisan
 * schedule:run`), el proceso Aracode-integration-scheduler sobra: se puede
 * borrar de este archivo y dejar solo los workers de la cola.
 *
 * Requisito: en el .env del servidor debe estar QUEUE_CONNECTION=database.
 *
 * Varias copias del proyecto en el mismo servidor: pm2 no admite dos procesos
 * con el mismo nombre, así que el prefijo sale del APP_NAME de cada .env
 *
 *     APP_NAME="Demo Aracode"
 *
 * y los procesos quedan como Demo-Aracode-integration-queue, Demo-Aracode-
 * integration-imports, etc. (se quitan espacios, acentos y caracteres raros).
 * Si las dos copias comparten el mismo APP_NAME, cada una puede forzar su
 * prefijo con PM2_APP_NAME, que manda sobre APP_NAME. Sin ninguna de las dos se
 * usa Aracode, el nombre histórico.
 */
const fs = require('node:fs');
const path = require('node:path');

/**
 * Lee una clave del .env del proyecto.
 *
 * pm2 carga este archivo con Node, no con Laravel, así que el .env no está
 * disponible como variable de entorno. Se lee sin dependencias (dotenv no está
 * instalado). Avisa por consola del motivo cuando no encuentra el valor: en
 * Linux lo habitual es que el .env no sea legible por el usuario que corre pm2
 * (por ejemplo 600 y dueño www-data).
 */
function leerEnv(clave) {
    const ruta = path.join(__dirname, '.env');
    let contenido;

    try {
        contenido = fs.readFileSync(ruta, 'utf8');
    } catch (error) {
        console.warn(`[pm2] No se pudo leer ${ruta} (${error.code || error.message}). Se usara el nombre por defecto.`);

        return '';
    }

    // BOM que agregan algunos editores al inicio del archivo.
    contenido = contenido.replace(/^\uFEFF/, '');

    for (const linea of contenido.split(/\r?\n/)) {
        const limpia = linea.trim();

        if (limpia === '' || limpia.startsWith('#')) {
            continue;
        }

        // Soporta `KEY=valor`, `export KEY=valor` y espacios alrededor del `=`.
        const sinExport = limpia.startsWith('export ') ? limpia.slice(7).trim() : limpia;
        const separador = sinExport.indexOf('=');

        if (separador === -1 || sinExport.slice(0, separador).trim() !== clave) {
            continue;
        }

        let valor = sinExport.slice(separador + 1).trim();
        const entreComillas = /^(['"])([\s\S]*)\1$/.exec(valor);

        // Con comillas se respeta el contenido; sin comillas se corta un comentario final.
        valor = entreComillas ? entreComillas[2] : valor.replace(/\s+#.*$/, '').trim();

        if (valor === '') {
            console.warn(`[pm2] ${clave} esta vacio en ${ruta}. Se usara el nombre por defecto.`);
        }

        return valor;
    }

    console.warn(`[pm2] ${clave} no existe en ${ruta}. Se usara el nombre por defecto.`);

    return '';
}

/**
 * Prefijo de los procesos: PM2_APP_NAME del entorno (permite probar sin tocar
 * el .env) o del .env. Se limpia para que el nombre no tenga espacios ni
 * caracteres que rompan `pm2 logs <nombre>`.
 */
/**
 * Nombre base: variable de entorno (util para probar sin tocar el .env),
 * PM2_APP_NAME y luego APP_NAME del .env.
 */
let nombreBase = process.env.PM2_APP_NAME
    || leerEnv('PM2_APP_NAME')
    || leerEnv('APP_NAME')
    || 'Aracode';

// "Laravel" es el APP_NAME de fabrica: no identifica a la copia y haria que dos
// instalaciones choquen en el mismo daemon de pm2.
if (/^laravel$/i.test(nombreBase.trim())) {
    console.warn('[pm2] APP_NAME sigue en su valor por defecto ("Laravel"). Escribe APP_NAME o PM2_APP_NAME con el nombre de esta copia; por ahora se usa Aracode.');
    nombreBase = 'Aracode';
}

/**
 * Nombre apto para pm2: sin acentos (Nino -> Nino, no Ni-o), sin espacios y sin
 * simbolos que rompan `pm2 logs <nombre>`.
 */
const prefijo = nombreBase
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .replace(/[^A-Za-z0-9._-]+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-+|-+$/g, '') || 'Aracode';

// Deja rastro en la salida de `pm2 start` de que valor se resolvio y de donde.
console.log(`[pm2] prefijo de procesos: ${prefijo}${prefijo === nombreBase ? '' : ` (desde "${nombreBase}")`}`);

module.exports = {
    apps: [
        {
            name: `${prefijo}-integration-scheduler`,
            script: 'artisan',
            interpreter: '/usr/bin/php8.5',
            args: 'schedule:work',
            cwd: __dirname,
            autorestart: true,
            max_restarts: 10,
            restart_delay: 5000,
        },
        {
            name: `${prefijo}-integration-queue`,
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
            name: `${prefijo}-integration-imports`,
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
