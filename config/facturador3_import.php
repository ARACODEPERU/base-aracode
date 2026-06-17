<?php

return [
    'connection' => 'facturador3_tenant',

    'chunk_size' => (int) env('FACTURADOR3_IMPORT_CHUNK_SIZE', 1000),

    'queue' => env('FACTURADOR3_IMPORT_QUEUE', 'imports'),

    'kardex_description' => 'Importación Facturador3',
    'kardex_document_entity' => 'Facturador3Import',

    'default_image' => 'img/imagen-no-disponible.jpg',
];
