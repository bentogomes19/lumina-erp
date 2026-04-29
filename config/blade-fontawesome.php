<?php

$defaultAttributes = [
    'width' => '1em',
    'height' => '1em',
    'aria-hidden' => 'true',
];

return [
    'brands' => [
        'prefix' => 'fab',
        'fallback' => '',
        'class' => '',
        'attributes' => $defaultAttributes,
    ],

    'regular' => [
        'prefix' => 'far',
        'fallback' => '',
        'class' => '',
        'attributes' => $defaultAttributes,
    ],

    'solid' => [
        'prefix' => 'fas',
        'fallback' => '',
        'class' => '',
        'attributes' => $defaultAttributes,
    ],
];
