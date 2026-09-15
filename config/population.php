<?php

return [
    // Perfil da população demo: pequena, média ou grande.
    'profile' => env('SCHOOL_POPULATION_PROFILE', 'media'),
    'profiles' => [
        'pequena' => [
            'students_per_class' => 8,
            'teachers_per_subject' => 2,
        ],
        'media' => [
            'students_per_class' => 12,
            'teachers_per_subject' => 3,
        ],
        'grande' => [
            'students_per_class' => 24,
            'teachers_per_subject' => 4,
        ],
    ],
    // Cinco anos incluindo o atual; o próximo ano contém apenas planejamento.
    'history_years' => (int) env('SCHOOL_HISTORY_YEARS', 5),
    'students_per_class' => (int) env('SCHOOL_STUDENTS_PER_CLASS', 12),
    'password' => env('SCHOOL_DEFAULT_PASSWORD', '123456'),
];
