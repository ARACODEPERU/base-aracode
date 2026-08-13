<?php

return [
    'name' => 'Bibliodata',

    'reader_login' => [
        'app_name' => 'Biblio Data',
        'tagline' => 'Accede a tu biblioteca digital',
        'image' => 'img/biblio-conta.png',
    ],

    'reader' => [
        'role' => 'Lector',
        'admin_role' => 'admin',
        'default_book_id' => env('BIB_READER_DEFAULT_BOOK_ID', null),
        'preview_pages_per_book' => 1,
    ],

    'reading' => [
        'cache_ttl' => env('BIB_READING_CACHE_TTL', 43200),
        'persist_interval_seconds' => env('BIB_READING_PERSIST_INTERVAL', 300),
        'persist_progress_delta' => env('BIB_READING_PERSIST_DELTA', 5),
    ],
];
