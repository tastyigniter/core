<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TastyIgniter Domain Name
    |--------------------------------------------------------------------------
    |
    | This value is the "domain name" associated with your application.
    |
    */

    'domain' => env('IGNITER_DOMAIN_NAME', null),

    'adminDomain' => env('IGNITER_DOMAIN_NAME', null),

    /*
    |--------------------------------------------------------------------------
    | Front-end URI
    |--------------------------------------------------------------------------
    |
    | Specifies the URI prefix used for accessing customer (front-end) pages.
    |
    */

    'uri' => null,

    /*
    |--------------------------------------------------------------------------
    | Back-end URI
    |--------------------------------------------------------------------------
    |
    | Specifies the URI prefix used for accessing admin (back-end) pages.
    |
    */

    'adminUri' => '/admin',

    /*
    |--------------------------------------------------------------------------
    | Assets combiner URI
    |--------------------------------------------------------------------------
    |
    | Specifies the URI prefix used for accessing combined assets.
    |
    */

    'assetsCombinerUri' => '/_assets',

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    |
    */

    'middleware' => [
        'web', 'igniter',
    ],

    'adminMiddleware' => [
        'web', 'igniter', 'igniter:admin',
    ],

    'coreNamespaces' => [
        'Igniter\\Admin\\',
        'Igniter\\Main\\',
        'Igniter\\System\\',
    ],
];
