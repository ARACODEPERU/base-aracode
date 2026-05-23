<?php

return [
    'name' => 'Bibliodata',

    'reader_login' => [
        'app_name' => 'Biblio Data',
        'tagline' => 'Accede a tu biblioteca digital',
    ],

    'reader' => [
        'role' => 'Lector',
        // Temporal hasta fase lector: usar BibSubscriptionService::getActiveSubscriptionForUser()
        'default_book_id' => env('BIB_READER_DEFAULT_BOOK_ID', null),
    ],
];
