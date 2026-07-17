<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    |
    | These options configures if and how Inertia uses Server Side Rendering
    | to pre-render each initial request made to your application's pages
    | so that server rendered HTML is delivered for the user's browser.
    |
    | See: https://inertiajs.com/server-side-rendering
    |
    */

    'ssr' => [
        'enabled' => true,
        'url' => 'http://127.0.0.1:13714',
        // 'bundle' => base_path('bootstrap/ssr/ssr.mjs'),

    ],

    /*
    |--------------------------------------------------------------------------
    | History Encryption
    |--------------------------------------------------------------------------
    |
    | Encrypts the Inertia page data stored in the browser's history state.
    | Enabled by default because the whole application exposes institutional
    | data (pagos, proveedores, expedientes): without this, pressing "back"
    | after logout restores the last authenticated page from history without
    | a fresh request to the server. Cleared on logout via Inertia::clearHistory().
    |
    | Requires a secure context (HTTPS, or the literal "localhost" host) —
    | window.crypto.subtle is unavailable otherwise, which makes the Inertia
    | client's history-encryption promise chain hang indefinitely instead of
    | rejecting (upstream bug: getPageData() calls encryptHistory().then(resolve)
    | with no reject handler). Disable via INERTIA_HISTORY_ENCRYPT=false in local
    | .env when developing over plain HTTP on a non-"localhost" host (e.g. a
    | Laragon *.test vhost without SSL) — production has no env override, so it
    | stays protected by default.
    |
    | See: https://inertiajs.com/docs/v3/security/history-encryption
    |
    */

    'history' => [
        'encrypt' => env('INERTIA_HISTORY_ENCRYPT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | These options configure how Inertia discovers page components on the
    | filesystem. The paths and extensions are used to locate components
    | when rendering responses and during testing assertions.
    |
    */

    'pages' => [

        'paths' => [
            resource_path('js/pages'),
        ],

        'extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    |
    | The values described here are used to locate Inertia components on the
    | filesystem. For instance, when using `assertInertia`, the assertion
    | attempts to locate the component as a file relative to the paths.
    |
    */

    'testing' => [

        'ensure_pages_exist' => true,

    ],

];
