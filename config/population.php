<?php

return [
    // Cinco anos incluindo o atual; o próximo ano contém apenas planejamento.
    'history_years' => (int) env('SCHOOL_HISTORY_YEARS', 5),
    'students_per_class' => (int) env('SCHOOL_STUDENTS_PER_CLASS', 12),
    'password' => env('SCHOOL_DEFAULT_PASSWORD', '123456'),
];
