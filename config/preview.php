<?php

return [
    // Preview backend remains available, while its form actions stay hidden until manual acceptance succeeds.
    'ui_enabled' => false,
    'ttl_minutes' => 30,
    'max_active' => 5,
    'max_payload_bytes' => 2 * 1024 * 1024,
    'supported_types' => [
        'news',
        'page',
        'location',
        'gallery',
        'document',
        'media',
        'menu',
        'location-category',
        'news-category',
        'document-category',
        'settings',
    ],
];
