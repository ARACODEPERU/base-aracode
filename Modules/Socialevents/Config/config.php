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
];
