<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Notifications;

use Igniter\System\Notifications\SystemUpdateNotification;
use Igniter\User\Models\User;

it('returns recipients who are enabled super users', function() {
    $enabledSuperUser = User::factory()->superUser()->create(['status' => true]);
    $disabledSuperUser = User::factory()->superUser()->create(['status' => false]);
    User::factory()->create(['status' => true]);

    $notification = new SystemUpdateNotification;
    $recipients = $notification->getRecipients();

    expect(collect($recipients)->pluck('user_id'))->toContain($enabledSuperUser->getKey())
        ->and(collect($recipients)->pluck('user_id'))->not->toContain($disabledSuperUser->getKey());
});

it('returns correct title', function() {
    $notification = new SystemUpdateNotification;
    $title = $notification->getTitle();

    expect($title)->toBe(lang('igniter::system.updates.notify_no_update_found_title'));
    $notification = new SystemUpdateNotification(3);
    $title = $notification->getTitle();

    expect($title)->toBe(lang('igniter::system.updates.notify_new_update_found_title'));
});

it('returns correct URL', function() {
    $notification = new SystemUpdateNotification;
    $url = $notification->getUrl();

    expect($url)->toBe(admin_url('updates'));
});

it('returns correct message for single update', function() {
    $notification = new SystemUpdateNotification(1);
    $message = $notification->getMessage();

    expect($message)->toBe(lang('igniter::system.updates.notify_new_update_found'));
});

it('returns correct message for multiple updates', function() {
    $notification = new SystemUpdateNotification(5);
    $message = $notification->getMessage();

    expect($message)->toBe(sprintf(lang('igniter::system.updates.notify_new_updates_found'), 5));
});

it('returns correct icon', function() {
    $notification = new SystemUpdateNotification;
    $icon = $notification->getIcon();

    expect($icon)->toBe('fa-cloud-arrow-down');
});

it('returns correct icon color', function() {
    $notification = new SystemUpdateNotification;
    $iconColor = $notification->getIconColor();

    expect($iconColor)->toBe('success');
});

it('returns correct alias', function() {
    $notification = new SystemUpdateNotification;
    $alias = $notification->getAlias();

    expect($alias)->toBe('update-found');
});
