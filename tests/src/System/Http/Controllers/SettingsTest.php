<?php

namespace Igniter\Tests\System\Http\Controllers;

use Igniter\User\Facades\AdminAuth;
use Igniter\User\Models\User;
use Igniter\User\Models\UserRole;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

it('loads settings page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.settings'))
        ->assertOk();
});

it('loads general settings page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.settings', ['slug' => 'edit/general']))
        ->assertOk();
});

it('flashes error when accessing restricted settings page', function() {
    $role = UserRole::factory()->create(['permissions' => ['Site.Settings' => 1]]);
    $user = User::factory()->for($role, 'role')->create();

    $this->actingAs($user, 'igniter-admin')
        ->get(route('igniter.system.settings', ['slug' => 'edit/statuses']))
        ->assertSee(lang('igniter::admin.alert_user_restricted'));
});

it('updates general settings', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.settings', ['slug' => 'edit/general']), [
            'Setting' => [
                'site_name' => 'New Site Name',
                'site_email' => 'site@example.com',
                'site_logo' => 'path/to/logo.png',
                'distance_unit' => 'km',
                'default_geocoder' => 'nominatim',
                'timezone' => 'Europe/London',
                'detect_language' => '0',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertOk();
});

it('flashes error when updating restricted settings', function() {
    $role = UserRole::factory()->create(['permissions' => ['Site.Settings' => 1]]);
    $user = User::factory()->for($role, 'role')->create();

    $this->actingAs($user, 'igniter-admin')
        ->post(route('igniter.system.settings', ['slug' => 'edit/statuses']), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertSee(lang('igniter::admin.alert_user_restricted'));
});

it('flashes error when updates settings fails validation', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.settings', ['slug' => 'edit/general']), [
            'Setting' => [
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertStatus(406);
});

it('updates extension settings and redirects to settings page', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.settings', ['slug' => 'edit/general']), [
            'close' => '1',
            'Setting' => [
                'site_name' => 'New Site Name',
                'site_email' => 'site@example.com',
                'site_logo' => 'path/to/logo.png',
                'distance_unit' => 'km',
                'default_geocoder' => 'nominatim',
                'timezone' => 'Europe/London',
                'detect_language' => '0',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertOk();
});

it('sends test email', function() {
    Mail::shouldReceive('raw')->withArgs(function($content, $callback) {
        $message = mock(Message::class);
        $message->shouldReceive('to')->andReturnSelf();
        $message->shouldReceive('subject')->with('This a test email')->andReturnSelf();
        $callback($message);
        return true;
    })->once();

    actingAsSuperUser()
        ->post(route('igniter.system.settings', ['slug' => 'edit/mail']), [
            'Setting' => [
                'sender_name' => 'Test Sender',
                'sender_email' => 'sender@example.com',
                'protocol' => 'log',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onTestMail',
        ])
        ->assertOk();

    expect(flash()->messages()->first())->message->toBe(sprintf(lang('igniter::system.settings.alert_email_sent'), AdminAuth::getStaffEmail()));
});

it('flashes error when sending test email fails', function() {
    Mail::shouldReceive('raw')->andThrow(new \Exception('Test exception'));

    actingAsSuperUser()
        ->post(route('igniter.system.settings', ['slug' => 'edit/mail']), [
            'Setting' => [
                'sender_name' => 'Test Sender',
                'sender_email' => 'sender@example.com',
                'protocol' => 'log',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onTestMail',
        ])
        ->assertOk();

    expect(flash()->messages()->first())->message->toBe('Test exception');
});
