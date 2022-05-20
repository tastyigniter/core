<?php

return [

    'passwords' => [
        'resets' => config('auth.defaults.passwords'),
        'activations' => config('auth.defaults.passwords'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | By default, TastyIgniter will use the `web` authentication guard. However,
    | if you want to run TastyIgniter alongside the default Laravel auth
    | guard, you can configure that for your admin and/or site.
    |
    */

    'guards' => [
        'admin' => 'admin',
        'web' => 'web',
    ],

];
