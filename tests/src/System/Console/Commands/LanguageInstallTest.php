<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Console\Commands;

use Igniter\System\Classes\LanguageManager;
use Igniter\System\Models\Extension;

it('installs language pack successfully', function() {
    $languageManager = mock(LanguageManager::class);
    app()->instance(LanguageManager::class, $languageManager);
    $languageManager->shouldReceive('applyLanguagePack')->andReturn([
        [
            'name' => 'Igniter.Api',
            'code' => 'fr',
            'type' => 'module',
            'version' => '1.0.0+1',
            'hash' => 'hash',
        ],
    ]);
    Extension::create(['name' => 'Igniter.Api', 'status' => 1, 'version' => '1.0.0']);
    $languageManager->shouldReceive('installLanguagePack')->once()->with('fr', [
        'name' => 'fr',
        'type' => 'module',
        'ver' => '1.0.0',
        'build' => '1',
        'hash' => 'hash',
    ]);

    $this->artisan('igniter:language-install fr')
        ->expectsOutput('1 translated strings found')
        ->expectsOutput('Installing fr translated strings for Igniter.Api')
        ->assertExitCode(0);
});

it('skips installation if no new translated strings found', function() {
    $languageManager = mock(LanguageManager::class);
    app()->instance(LanguageManager::class, $languageManager);
    $languageManager->shouldReceive('applyLanguagePack')->andReturn([]);

    $this->artisan('igniter:language-install fr')
        ->expectsOutput('No new translated strings found')
        ->assertExitCode(0);
});

it('checks for updates only', function() {
    $languageManager = mock(LanguageManager::class);
    app()->instance(LanguageManager::class, $languageManager);
    $languageManager->shouldReceive('applyLanguagePack')->andReturn([
        [
            'name' => 'Igniter.Api',
            'code' => 'fr',
            'type' => 'module',
            'version' => '1.0.0+1',
            'hash' => 'hash',
        ],
    ]);

    $this->artisan('igniter:language-install fr --check')
        ->assertExitCode(0);
});
