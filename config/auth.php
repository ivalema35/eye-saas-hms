<?php

use App\Models\User;

return [

    'defaults' => [
        'guard'     => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        // ── Platform Super Admin ──────────────────────────────────
        // Access: /superadmin/*
        'superadmin' => [
            'driver'   => 'session',
            'provider' => 'platform_admins',
        ],

        // ── Hospital Users (UNIFIED — replaces 4 separate guards) ──
        // Access: /{slug}/*
        // Covers: hospital_admin, doctor, reception, ot_receptionist,
        //         accountant, ot_doctor, ot_assistant — sab ek hi guard
        // Role is determined by hospital_users.role_id → roles.slug
        'hospital_user' => [
            'driver'   => 'session',
            'provider' => 'hospital_users',
        ],

        // ── API Guard (Android app — Sanctum) ─────────────────────
        'api' => [
            'driver'   => 'sanctum',
            'provider' => 'hospital_users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => env('AUTH_MODEL', User::class),
        ],

        'platform_admins' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Platform\PlatformAdmin::class,
        ],

        // UNIFIED: single provider for all hospital staff
        'hospital_users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Hospital\HospitalUser::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],
        'platform_admins' => [
            'provider' => 'platform_admins',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
        'hospital_users' => [
            'provider' => 'hospital_users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
