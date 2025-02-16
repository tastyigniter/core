<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Scaffold\Console;

use Igniter\Flame\Support\Facades\File;

it('does not run command when not confirmed', function() {
    $this->app['env'] = 'production';

    $this->artisan('make:igniter-controller', ['extension' => 'Custom.Controller', 'controller' => 'TestController'])
        ->expectsConfirmation('Are you sure you want to run this command?', 'no')
        ->doesntExpectOutput('Controller created successfully.')
        ->assertExitCode(0);
});

it('creates a new controller with valid extension and controller names', function() {
    File::partialMock()->shouldReceive('makeDirectory');
    File::partialMock()->shouldReceive('put')->once();
    $this->artisan('make:igniter-controller', ['extension' => 'Custom.Controller', 'controller' => 'TestController'])
        ->expectsOutput('Controller created successfully.')
        ->assertExitCode(0);

    File::partialMock()->shouldReceive('exists')->andReturnTrue()->once();
    $this->artisan('make:igniter-controller', ['extension' => 'Custom.Controller', 'controller' => 'TestController'])
        ->expectsOutput('Controller already exists! '.base_path('extensions/custom/controller/src/Http/Controllers/TestController.php'))
        ->assertExitCode(0);
});

it('throws error with invalid extension name', function() {
    $this->artisan('make:igniter-controller', ['extension' => 'In.valid.Extension', 'controller' => 'TestController'])
        ->expectsOutput('Invalid extension name, Example name: AuthorName.ExtensionName')
        ->doesntExpectOutput('Controller created successfully.')
        ->assertExitCode(0);
});
