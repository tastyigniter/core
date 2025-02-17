<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Mail;

use Igniter\Flame\Mail\Mailable;
use Illuminate\Support\Facades\App;

it('builds view data with restored property values', function() {
    $mailable = new Mailable;
    $mailable->viewData = ['key' => 'value'];
    $data = $mailable->buildViewData();
    expect($data)->toHaveKey('key', 'value');
});

it('sets serialized view data with current locale', function() {
    $mailable = new Mailable;
    $mailable->withSerializedData(['key' => 'value']);

    expect($mailable->viewData)->toBe(['_current_locale' => App::getLocale(), 'key' => 'value']);
});
