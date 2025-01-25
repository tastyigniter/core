<?php

namespace Igniter\Tests\System\Models;

use Igniter\System\Models\Translation;

it('updates and locks translation with new text', function() {
    $translation = Translation::create(['text' => 'Old Text', 'locked' => false]);
    $result = $translation->updateAndLock('New Text');

    expect($result)->toBeTrue()
        ->and($translation->fresh()->text)->toBe('New Text')
        ->and($translation->fresh()->locked)->toBeTrue();
});
