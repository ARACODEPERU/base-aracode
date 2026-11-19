<?php

namespace Modules\Ai\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * Mapa de pantallas del sistema construido en el servidor.
 *
 * Aracodito no debe depender de una lista enviada por el navegador: aquí se
 * leen las rutas reales de Laravel, se descartan las técnicas (guardar,
 * exportar, callbacks) y se valida el permiso o el rol que exige cada ruta
 * contra el usuario autenticado. Solo las pantallas que ese usuario puede
 * abrir llegan al modelo.
 *
 * El título se arma con el nombre de la ruta; si un token no está en el
 * diccionario se muestra tal cual, en lugar de inventar un nombre.
 */
class MapaSistema
{
    /**
     * Primer segmento de la dirección => nombre del módulo.
     */
    private const MODULOS = [
        'ai' => 'IA',
        'health' => 'Salud',
        'dental' => 'Dental',
        'treasury' => 'Tesorería',
        'sales' => 'Ventas',
        'purchases' => 'Compras',
        'academic' => 'Académico',
        'crm' => 'CRM',
        'cms' => 'CMS',
        'blog' => 'Blog',
        'onlineshop' => 'Tienda en línea',
        'restaurant' => 'Restaurante',
        'socialevents' => 'Eventos',
        'helpdesk' => 'Mesa de ayuda',
        'integrationhub' => 'Integraciones',
        'commercial' => 'Comercial',
        'bibliodata' => 'Biblioteca',
        'churchcommunity' => 'Comunidad',
        'performance' => 'Rendimiento',
        'security' => 'Configuración',
    ];

    /**
     * Prefijos de nombre de ruta que no aportan al título.
     */
    private const PREFIJOS = [
        'ai', 'heal', 'dental', 'treasury', 'sale', 'purc', 'aca', 'crm', 'cms',
        'blog', 'onli', 'res', 'even', 'help', 'comm', 'bib', 'cigle', 'performance',
        'integrationhub', 'security',
    ];

    /**
     * Tokens que descartan una ruta por ser una acción y no una pantalla.
     *
     * No incluye index/list/listado: en este sistema el listado ES la
     * pantalla (por ejemplo heal_attentions_list atiende /health/attentions).
     */
    private const TECNICAS = [
        'create', 'crear', 'edit', 'editar', 'store', 'update', 'destroy', 'delete',
        'export', 'exportar', 'pdf', 'csv', 'download', 'descargar', 'callback',
        'webhook', 'api', 'test', 'probar', 'preview', 'generar', 'generate', 'sync',
        'sincronizar', 'refresh', 'reset', 'status', 'print', 'imprimir', 'cards',
        'carnet', 'carnets', 'bulk', 'masivo', 'qr', 'scan', 'escaner', 'search',
        'buscar', 'link', 'my', 'null',
    ];

    /**
     * Tokens que no se muestran en el título (acciones y sufijos).
     */
    private const IGNORAR = [
        'index', 'list', 'listado', 'lista', 'create', 'crear', 'nuevo', 'edit',
        'editar', 'show', 'ver', 'detail', 'detalle', 'dashboard', 'admin', 'store',
        'update', 'destroy', 'delete', 'export', 'exportar', 'pdf', 'csv', 'download',
        'descargar', 'callback', 'webhook', 'api', 'test', 'probar', 'preview',
        'generar', 'generate', 'sync', 'sincronizar', 'refresh', 'reset', 'status',
        'print', 'imprimir', 'cards', 'carnet', 'carnets', 'bulk', 'masivo', 'null',
        'qr', 'scan', 'escaner', 'link', 'my',
    ];

    /**
     * Equivalencias en español para los tokens más frecuentes.
     */
    private const ETIQUETAS = [
        'dashboard' => 'Panel',
        'accounts' => 'Cuentas',
        'transfers' => 'Transferencias',
        'transactions' => 'Movimientos',
        'payments' => 'Pagos',
        'forecast' => 'Previsión',
        'cash' => 'Efectivo',
        'collections' => 'Cobros',
        'documents' => 'Documentos',
        'reconciliation' => 'Conciliación',
        'periods' => 'Periodos',
        'detraction' => 'Detracción',
        'taxes' => 'Impuestos',
        'reports' => 'Reportes',
        'audit' => 'Auditoría',
        'categories' => 'Categorías',
        'configuration' => 'Configuración',
        'configuracion' => 'Configuración',
        'patients' => 'Pacientes',
        'patient' => 'Pacientes',
        'doctors' => 'Doctores',
        'doctor' => 'Doctores',
        'attentions' => 'Atenciones',
        'attention' => 'Atenciones',
        'agendas' => 'Agendas',
        'agenda' => 'Agendas',
        'clinical' => 'Historia clínica',
        'records' => 'Registros',
        'history' => 'Historial',
        'procedures' => 'Procedimientos',
        'charges' => 'Cobros',
        'notices' => 'Avisos',
        'settings' => 'Configuración',
        'parametros' => 'Parámetros',
        'parameters' => 'Parámetros',
        'calendar' => 'Calendario',
        'activities' => 'Actividades',
        'students' => 'Estudiantes',
        'student' => 'Estudiantes',
        'courses' => 'Cursos',
        'course' => 'Cursos',
        'teachers' => 'Docentes',
        'school' => 'Colegio',
        'subscriptions' => 'Suscripciones',
        'certificates' => 'Certificados',
        'attendance' => 'Asistencia',
        'notifications' => 'Notificaciones',
        'products' => 'Productos',
        'clients' => 'Clientes',
        'customers' => 'Clientes',
        'providers' => 'Proveedores',
        'suppliers' => 'Proveedores',
        'warehouse' => 'Almacén',
        'invoices' => 'Comprobantes',
        'orders' => 'Pedidos',
        'items' => 'Artículos',
        'sections' => 'Secciones',
        'pages' => 'Páginas',
        'subscribers' => 'Suscriptores',
        'testimonies' => 'Testimonios',
        'messages' => 'Mensajes',
        'contacts' => 'Contactos',
        'companies' => 'Empresas',
        'contracts' => 'Contratos',
        'negotiations' => 'Negociaciones',
        'books' => 'Libros',
        'authors' => 'Autores',
        'loans' => 'Préstamos',
        'members' => 'Miembros',
        'events' => 'Eventos',
        'teams' => 'Equipos',
        'editions' => 'Ediciones',
        'tickets' => 'Tickets',
        'incidents' => 'Incidentes',
        'users' => 'Usuarios',
        'roles' => 'Roles',
        'permissions' => 'Permisos',
        'consumo' => 'Consumo',
        'prices' => 'Precios',
        'precios' => 'Precios',
        'quality' => 'Calidad',
        'administration' => 'Administración',
        'structure' => 'Estructura',
        'options' => 'Opciones',
        'gate' => 'Portería',
        'sms' => 'SMS',
        'telegram' => 'Telegram',
        'grades' => 'Notas',
        'teacher' => 'Docente',
        'schedules' => 'Horarios',
        'day' => 'Día',
        'report' => 'Reporte',
        'attendance' => 'Asistencia',
        'enrollments' => 'Matrículas',
        'fees' => 'Pensiones',
        'areas' => 'Áreas',
        'competencies' => 'Competencias',
        'classroom' => 'Aula',
        'virtual' => 'Virtual',
        'tutorials' => 'Tutoriales',
        'reports' => 'Reportes',
    ];

    /**
     * Pantallas que el usuario puede abrir, ordenadas por módulo y nombre.
     *
     * @return array<int, array{texto: string, url: string, modulo: string, permiso: ?string}>
     */
    public static function paraUsuario(?Authenticatable $usuario, int $limite = 500): array
    {
        if ($usuario === null) {
            return [];
        }

        $permisos = method_exists($usuario, 'getAllPermissions')
            ? $usuario->getAllPermissions()->pluck('name')->sort()->values()->all()
            : [];

        // El límite forma parte de la clave: un mapa recortado no debe
        // reutilizarse cuando se pide el catálogo completo.
        $clave = 'ai:mapa:v4:' . $usuario->getAuthIdentifier() . ':' . $limite . ':' . md5(implode('|', $permisos));

        return Cache::remember($clave, now()->addMinutes(5), function () use ($usuario, $limite) {
            return self::construir($usuario, $limite);
        });
    }

    /**
     * @return array<int, array{texto: string, url: string, modulo: string, permiso: ?string}>
     */
    private static function construir(Authenticatable $usuario, int $limite): array
    {
        $vistos = [];
        $pantallas = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = trim($route->uri(), '/');

            if ($uri === '' || str_contains($uri, '{')) {
                continue;
            }

            $segmento = explode('/', $uri)[0];

            if (! isset(self::MODULOS[$segmento])) {
                continue;
            }

            $nombre = (string) $route->getName();

            if ($nombre === '' || isset($vistos[$uri])) {
                continue;
            }

            $middleware = $route->gatherMiddleware();

            if (! self::requiereSesion($middleware) || self::esTecnica($nombre, $uri)) {
                continue;
            }

            if (! self::autorizado($usuario, $middleware)) {
                continue;
            }

            $vistos[$uri] = true;

            $permisos = self::permisosDe($middleware);

            $pantallas[] = [
                'texto' => self::etiqueta($segmento, $nombre),
                'url' => '/' . $uri,
                'modulo' => self::MODULOS[$segmento],
                'permiso' => $permisos[0] ?? null,
            ];
        }

        // Varias rutas pueden representar la misma pantalla (por ejemplo
        // "Parámetros" y "Parámetros · Enlace"): se conserva la primera.
        $unicas = [];

        foreach ($pantallas as $pantalla) {
            $clave = $pantalla['modulo'] . '|' . $pantalla['texto'];

            if (! isset($unicas[$clave])) {
                $unicas[$clave] = $pantalla;
            }
        }

        $pantallas = array_values($unicas);

        usort($pantallas, function (array $a, array $b) {
            return [$a['modulo'], $a['texto']] <=> [$b['modulo'], $b['texto']];
        });

        return array_slice($pantallas, 0, $limite);
    }

    /**
     * @param  array<int, string>  $middleware
     */
    private static function requiereSesion(array $middleware): bool
    {
        foreach ($middleware as $item) {
            if (str_starts_with((string) $item, 'auth')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Descarta rutas de acciones y utilidades: Aracodito guía a pantallas,
     * no a endpoints que guardan, exportan o sincronizan.
     */
    private static function esTecnica(string $nombre, string $uri): bool
    {
        foreach (preg_split('/[_\-\/]+/', $nombre . '_' . $uri) ?: [] as $token) {
            if (in_array(strtolower($token), self::TECNICAS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $middleware
     */
    private static function autorizado(Authenticatable $usuario, array $middleware): bool
    {
        $permisos = self::permisosDe($middleware);

        if ($permisos !== []) {
            $tiene = method_exists($usuario, 'hasAnyPermission')
                ? $usuario->hasAnyPermission($permisos)
                : $usuario->can($permisos[0]);

            if (! $tiene) {
                return false;
            }
        }

        foreach ($middleware as $item) {
            $item = (string) $item;

            if (! str_starts_with($item, 'role:')) {
                continue;
            }

            $roles = array_filter(explode('|', substr($item, 5)));

            if ($roles === []) {
                continue;
            }

            $tieneRol = method_exists($usuario, 'hasAnyRole')
                ? $usuario->hasAnyRole($roles)
                : false;

            if (! $tieneRol) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, string>  $middleware
     * @return array<int, string>
     */
    private static function permisosDe(array $middleware): array
    {
        foreach ($middleware as $item) {
            $item = (string) $item;

            if (str_starts_with($item, 'permission:')) {
                return array_values(array_filter(explode('|', substr($item, 11))));
            }
        }

        return [];
    }

    /**
     * Título legible a partir del nombre de la ruta.
     */
    private static function etiqueta(string $segmento, string $nombre): string
    {
        $tokens = array_values(array_filter(explode('_', mb_strtolower($nombre))));

        if ($tokens !== [] && in_array($tokens[0], self::PREFIJOS, true)) {
            array_shift($tokens);
        }

        $palabras = [];

        foreach ($tokens as $token) {
            if (in_array($token, self::IGNORAR, true)) {
                continue;
            }

            $palabras[] = self::ETIQUETAS[$token] ?? ucfirst(str_replace(['-', '/'], ' ', $token));
        }

        if ($palabras === []) {
            return self::MODULOS[$segmento] . ' · Principal';
        }

        return implode(' · ', array_slice(array_unique($palabras), 0, 3));
    }
}
