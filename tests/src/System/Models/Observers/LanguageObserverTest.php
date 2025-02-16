<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Models\Observers;

use Igniter\System\Models\Language;
use Igniter\System\Models\Observers\LanguageObserver;

it('sets idiom to code when creating language', function() {
    $language = new Language(['code' => 'en']);

    (new LanguageObserver)->creating($language);

    expect($language->idiom)->toBe('en');
});

it('applies supported languages after saving', function() {
    $language = mock(Language::class)->makePartial();

    $language->shouldReceive('restorePurgedValues')->once();
    $language->shouldReceive('getAttributes')->andReturn([
        'translations' => [],
    ]);

    (new LanguageObserver)->saved($language);
});
