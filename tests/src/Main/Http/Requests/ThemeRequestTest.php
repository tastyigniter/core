<?php

namespace Igniter\Tests\Main\Http\Requests;

use Igniter\Main\Http\Requests\ThemeRequest;
use Igniter\Tests\Fixtures\Controllers\ThemeTestController;
use Illuminate\Support\Facades\Route;

beforeEach(function() {
    $this->themeRequest = new ThemeRequest;
    $this->themeRequest->setRouteResolver(fn() => Route::get('/users/{user}', [ThemeTestController::class, 'index']));
});

it('returns empty attributes when form context is not edit', function() {
    ThemeTestController::$context = 'create';

    $attributes = $this->themeRequest->attributes();

    expect($attributes)->toBe([]);
});

it('returns correct attribute labels', function() {
    ThemeTestController::$context = 'edit';
    $attributes = $this->themeRequest->attributes();

    expect($attributes)->toBe([
        'theme_website' => lang('igniter.main::default.theme_website_label'),
        'theme_background' => lang('igniter.main::default.theme_background_label'),
    ]);
});

it('returns empty rules when form context is not edit', function() {
    ThemeTestController::$context = 'create';

    $rules = $this->themeRequest->rules();

    expect($rules)->toBe([]);
});

it('returns correct validation rules', function() {
    ThemeTestController::$context = 'edit';
    $rules = $this->themeRequest->rules();

    expect($rules)->toBe([
        'theme_website' => 'nullable|string',
        'theme_background' => 'required|string',
        'social.*.class' => 'required',
    ]);
});

it('returns validation data correctly', function() {
    ThemeTestController::$context = 'edit';

    expect($this->themeRequest->validationData())->toBeArray();
});
