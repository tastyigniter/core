<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Scaffold\Console;

use Igniter\Flame\Support\Facades\File;

it('creates a new model with valid extension and model names', function() {
    File::partialMock()->shouldReceive('makeDirectory');
    File::partialMock()->shouldReceive('put')->times(3);
    $this->artisan('make:igniter-model', ['extension' => 'Custom.Model', 'model' => 'TestModel'])
        ->expectsOutput('Model created successfully.')
        ->assertExitCode(0);

    File::partialMock()->shouldReceive('exists')->andReturnTrue()->times(3);
    $this->artisan('make:igniter-model', ['extension' => 'Custom.Model', 'model' => 'TestModel'])
        ->expectsOutput('Model already exists! '.base_path('extensions/custom/model/src/Models/TestModel.php'))
        ->expectsOutput('Model already exists! '.base_path('extensions/custom/model/resources/models/testmodel.php'))
        ->assertExitCode(0);
});

it('throws error with invalid extension name', function() {
    $this->artisan('make:igniter-model', ['extension' => 'In.valid.Extension', 'model' => 'TestModel'])
        ->expectsOutput('Invalid extension name, Example name: AuthorName.ExtensionName')
        ->doesntExpectOutput('Model created successfully.')
        ->assertExitCode(0);
});
