<?php

return [
    'name' => 'Socialevents',

    'rankings' => [
        'players' => [
            'goal' => 3,
            'assist' => 2,
            'mvp' => 5,
            'clean_sheet' => 1,
            'sanction_penalty' => 1,
        ],
        'goalkeepers' => [
            'save' => 0.5,
            'mvp' => 5,
            'clean_sheet' => 5,
            'sanction_penalty' => 1,
        ],
        'top_limit' => 5,
    ],

    'landing_cache_enabled' => env('SOCIALEVENTS_LANDING_CACHE', true),
    'landing_cache_ttl' => (int) env('SOCIALEVENTS_LANDING_CACHE_TTL', 120),

    /*
    | Módulos de evento previstos. Solo "sports" está operativo hoy; el resto se
    | mostrará en el dashboard como próximamente sin afectar rutas existentes.
    */
    'dashboard_active_module' => 'sports',

    'event_types' => [
        [
            'id' => 'sports',
            'label' => 'Deportes y torneos',
            'icon' => 'futbol',
            'enabled' => true,
            'permission' => 'even_ediciones_listado',
            'description' => 'Ediciones, equipos, fixture, tabla y landing pública.',
        ],
        [
            'id' => 'local_rentals',
            'label' => 'Alquiler de local',
            'icon' => 'house',
            'enabled' => true,
            'permission' => 'even_alquiler_local_listado',
            'description' => 'Reservas, adelantos, abonos y notas de venta.',
        ],
        [
            'id' => 'concerts',
            'label' => 'Conciertos',
            'icon' => 'music',
            'enabled' => false,
            'description' => 'Venta de entradas, artistas y cronograma (próximamente).',
        ],
        [
            'id' => 'seminars',
            'label' => 'Seminarios',
            'icon' => 'graduation',
            'enabled' => false,
            'description' => 'Inscripciones, cupos y certificados (próximamente).',
        ],
        [
            'id' => 'parties',
            'label' => 'Fiestas',
            'icon' => 'party',
            'enabled' => false,
            'description' => 'Invitados, mesas y venta anticipada (próximamente).',
        ],
    ],
];
