<?php

return [
    /**
     * The version of your app.
     */
    'version' => env('NATIVEPHP_APP_VERSION', '1.3.0'),

    /**
     * The ID of your application.
     */
    'app_id' => env('NATIVEPHP_APP_ID', 'com.nativephp.kipit'),

    /**
     * Deep link scheme.
     */
    'deeplink_scheme' => env('NATIVEPHP_DEEPLINK_SCHEME', 'kipit'),

    /**
     * Author of your application.
     */
    'author' => env('NATIVEPHP_APP_AUTHOR', 'Kipit'),

    /**
     * Copyright notice.
     */
    'copyright' => env('NATIVEPHP_APP_COPYRIGHT', 'Copyright © ' . date('Y') . ' Kipit'),

    /**
     * Description.
     */
    'description' => env('NATIVEPHP_APP_DESCRIPTION', 'Kipit Point of Sale - Desktop Edition'),

    /**
     * Website.
     */
    'website' => env('NATIVEPHP_APP_WEBSITE', 'https://nativephp.com'),

    /**
     * The default service provider for your application.
     */
    'provider' => \App\Providers\NativeAppServiceProvider::class,

    /**
     * Environment keys to cleanup for production.
     */
    'cleanup_env_keys' => [
        'AWS_*',
        'AZURE_*',
        'GITHUB_*',
        'DO_SPACES_*',
        '*_SECRET',
        'NATIVEPHP_UPDATER_PATH',
    ],

    'cleanup_exclude_files' => [
        'build',
        'temp',
        'content',
        'node_modules',
        '*/tests',
    ],

    'updater' => [
        'enabled' => env('NATIVEPHP_UPDATER_ENABLED', false),
        'default' => env('NATIVEPHP_UPDATER_PROVIDER', 'spaces'),
        'providers' => [
            'spaces' => [
                'driver' => 'spaces',
                'key' => env('DO_SPACES_KEY_ID'),
                'secret' => env('DO_SPACES_SECRET_ACCESS_KEY'),
                'name' => env('DO_SPACES_NAME'),
                'region' => env('DO_SPACES_REGION'),
                'path' => env('NATIVEPHP_UPDATER_PATH', null),
            ],
        ],
    ],

    'queue_workers' => [],
];
