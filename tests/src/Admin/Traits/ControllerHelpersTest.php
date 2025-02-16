<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Traits;

use Igniter\Admin\Classes\AdminController;

beforeEach(function() {
    $this->controller = new class extends AdminController {};
});

it('returns correct URL with parameters', function() {
    $url = $this->controller->pageUrl('dashboard', ['param' => 'value']);

    expect($url)->toBe('http://localhost/admin/dashboard/value');
});

it('returns correct secure URL', function() {
    $url = $this->controller->pageUrl('dashboard', [], true);

    expect($url)->toStartWith('https://');
});

it('redirects to correct URL', function() {
    $response = $this->controller->redirect('dashboard');

    expect($response->getTargetUrl())->toBe('http://localhost/admin/dashboard');
});

it('redirects guest to correct URL', function() {
    $response = $this->controller->redirectGuest('login');

    expect($response->getTargetUrl())->toBe('http://localhost/admin/login');
});

it('redirects to intended URL', function() {
    $response = $this->controller->redirectIntended('dashboard');

    expect($response->getTargetUrl())->toBe('http://localhost/admin/dashboard');
});

it('redirects back to fallback URL', function() {
    $response = $this->controller->redirectBack(302, [], 'fallback');

    expect($response->getTargetUrl())->toBe('http://localhost/admin/fallback');
});

it('refreshes the current page', function() {
    $response = $this->controller->refresh();

    expect($response->getTargetUrl())->toBe('http://localhost');
});
