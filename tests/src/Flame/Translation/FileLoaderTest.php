<?php

namespace Igniter\Tests\Flame\Translation;

use Igniter\Flame\Filesystem\Filesystem;
use Igniter\Flame\Translation\FileLoader;

it('loads namespace overrides from vendor path with slash namespace', function() {
    $files = mock(Filesystem::class);
    $files->shouldReceive('exists')->with('/path/to/translations/locale/group.php')->andReturnTrue();
    $files->shouldReceive('getRequire')->with('/path/to/translations/locale/group.php')->andReturn(['key' => 'value']);
    $files->shouldReceive('exists')->with('/path/to/translations')->andReturn(false, true);
    $files->shouldReceive('exists')->with('/path/to/translations/vendor/namespace/locale/group.php')->andReturnTrue();
    $files->shouldReceive('getRequire')->with('/path/to/translations/vendor/namespace/locale/group.php')->andReturn(['key' => 'override-value']);

    $fileLoader = new FileLoader($files, '/path/to/translations');
    $fileLoader->addNamespace('namespace', '/path/to/translations');

    expect($fileLoader->load('locale', 'group', 'namespace'))->toBe(['key' => 'value'])
        ->and($fileLoader->load('locale', 'group', 'namespace'))->toBe(['key' => 'override-value']);
});
