<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->name('igniter.admin.')
    ->prefix(config('igniter.system.adminUri', 'admin'))
    ->group(__DIR__.'/admin.php');

Route::middleware('web')->group(__DIR__.'/web.php');
