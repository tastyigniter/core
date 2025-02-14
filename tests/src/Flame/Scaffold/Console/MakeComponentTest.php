<?php

namespace Igniter\Tests\Flame\Scaffold\Console;

use Igniter\Flame\Support\Facades\File;

it('does not run command when not confirmed', function() {
    $this->app['env'] = 'production';

    $this->artisan('make:igniter-component', ['extension' => 'Custom.Component', 'component' => 'TestComponent'])
        ->expectsConfirmation('Are you sure you want to run this command?', 'no')
        ->doesntExpectOutput('Component created successfully.')
        ->assertExitCode(0);
});

it('creates a new component with valid extension and component names', function() {
    File::partialMock()->shouldReceive('makeDirectory');
    File::partialMock()->shouldReceive('put')->twice();
    $this->artisan('make:igniter-component', ['extension' => 'Custom.Component', 'component' => 'TestComponent'])
        ->expectsOutput('Component created successfully.')
        ->assertExitCode(0);

    File::partialMock()->shouldReceive('exists')->andReturnTrue()->twice();
    $this->artisan('make:igniter-component', ['extension' => 'Custom.Component', 'component' => 'TestComponent'])
        ->expectsOutput('Component already exists! '.base_path('extensions/custom/component/src/Components/TestComponent.php'))
        ->expectsOutput('Component already exists! '.base_path('extensions/custom/component/resources/views/_components/testcomponent/default.blade.php'))
        ->assertExitCode(0);
});

it('throws error with invalid extension name', function() {
    $this->artisan('make:igniter-component', ['extension' => 'In.valid.Extension', 'component' => 'TestComponent'])
        ->expectsOutput('Invalid extension name, Example name: AuthorName.ExtensionName')
        ->doesntExpectOutput('Component created successfully.')
        ->assertExitCode(0);
});
