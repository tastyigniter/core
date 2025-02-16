<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Template;

use Igniter\Main\Template\Code\LayoutCode;
use Igniter\Main\Template\Layout;

it('initializes correctly', function() {
    expect(Layout::DIR_NAME)->toBe('_layouts');
});

it('initializes fallback layout with default markup and filename', function() {
    $layout = Layout::initFallback('source');

    expect($layout->markup)->toBe('<?= page(); ?>')
        ->and($layout->fileName)->toBe('default.blade.php');
});

it('returns correct code class parent', function() {

    expect((new Layout)->getCodeClassParent())->toBe(LayoutCode::class);
});
