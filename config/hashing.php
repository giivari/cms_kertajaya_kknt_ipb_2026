<?php

return [
    'driver' => 'argon2id',
    // Existing bcrypt hashes must remain usable until each account changes its
    // password or Laravel rehashes it after a successful authenticated login.
    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => false,
    ],
    'argon' => [
        'memory' => env('ARGON_MEMORY', 65536),
        'threads' => env('ARGON_THREADS', 1),
        'time' => env('ARGON_TIME', 4),
        'verify' => false,
    ],
    'rehash_on_login' => true,
];
