<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Console\Commands;

use Igniter\System\Classes\LanguageManager;
use Igniter\System\Models\Extension;

it('installs language pack successfully', function() {
    $languageManager = mock(LanguageManager::class);
    app()->instance(LanguageManager::class, $languageManager);
    $languageManager->shouldReceive('findLanguage')->andReturn(['name' => 'French']);
    $languageManager->shouldReceive('applyLanguagePack')->andReturn([
        [
            'name' => 'Igniter.Api',
            'code' => 'fr',
            'type' => 'extension',
            'files' => [
                [
                    'name' => 'default.php',
                    'hash' => 'hash',
                ],
            ],
        ],
    ]);
    Extension::create(['name' => 'Igniter.Api', 'status' => 1, 'version' => '1.0.0']);
    $languageManager->shouldReceive('installLanguagePack')->once()->with('fr', [
        'name' => 'fr',
        'type' => 'extension',
        'ver' => '0.1.0',
        'file' => 'default.php',
        'hash' => 'hash',
    ]);

    $this->artisan('igniter:language-install fr')
        ->expectsOutput('New translated strings found')
        ->expectsOutput(sprintf(lang('igniter::system.languages.alert_update_file_progress'), 'fr', 'Igniter.Api', 'default.php'))
        ->assertExitCode(0);
});

it('skips installation if no new translated strings found', function() {
    $languageManager = mock(LanguageManager::class);
    app()->instance(LanguageManager::class, $languageManager);
    $languageManager->shouldReceive('findLanguage')->andReturn(['name' => 'French']);
    $languageManager->shouldReceive('applyLanguagePack')->andReturn([]);

    $this->artisan('igniter:language-install fr')
        ->expectsOutput('No new translated strings found')
        ->assertExitCode(0);
});
