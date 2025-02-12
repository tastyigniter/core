<?php

namespace Igniter\Tests\Flame\Scaffold\Console;

use Igniter\Flame\Support\Facades\File;

it('does not run command when not confirmed', function() {
    $this->app['env'] = 'production';

    $this->artisan('make:igniter-extension', ['extension' => 'In.valid.Extension'])
        ->expectsConfirmation('Are you sure you want to run this command?', 'no')
        ->doesntExpectOutput('Extension created successfully.')
        ->assertExitCode(0);
});

it('creates a new extension with valid extension', function() {
    File::partialMock()->shouldReceive('makeDirectory');
    File::partialMock()->shouldReceive('put')->twice();
    $this->artisan('make:igniter-extension', ['extension' => 'Custom.Extension'])
        ->expectsOutput('Extension created successfully.')
        ->assertExitCode(0);
});

it('throws error with invalid extension name', function() {
    $this->artisan('make:igniter-extension', ['extension' => 'In.valid.Extension'])
        ->expectsOutput('Invalid extension name, Example name: AuthorName.ExtensionName')
        ->doesntExpectOutput('Extension created successfully.')
        ->assertExitCode(0);
});
