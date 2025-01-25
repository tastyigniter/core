<?php

namespace Igniter\Tests\System\Providers;

use Facades\Igniter\System\Helpers\CacheHelper;
use Igniter\System\Models\Language;
use Igniter\System\Models\Observers\LanguageObserver;
use Igniter\System\Providers\EventServiceProvider;
use Illuminate\Support\Facades\Event;

it('clears internal cache after clearing app cache', function() {
    CacheHelper::shouldReceive('clearInternal')->once();

    Event::dispatch('cache:cleared');
});

it('registers model observer correctly', function() {
    $eventServiceProvider = new class(app()) extends EventServiceProvider
    {
        public function listObservers()
        {
            return $this->observers;
        }
    };

    expect($eventServiceProvider->listObservers())->toBe([
        Language::class => LanguageObserver::class,
    ]);
});
