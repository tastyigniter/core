<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Helpers;

use Igniter\System\Facades\Assets;
use Illuminate\Http\Response;

it('returns combined contents of assets', function() {
    Assets::shouldReceive('combineGetContents')->with('combined')->once()->andReturn(new Response('combined-contents'));
    Assets::shouldReceive('clearInternalCache');

    $response = $this->get('/_assets/combined-cache-key');
    expect($response->getContent())->toBe('combined-contents');
});

it('returns combined contents of assets using version query', function() {
    Assets::shouldReceive('combineGetContents')->with('combined')->once()->andReturn(new Response('combined-contents'));
    Assets::shouldReceive('clearInternalCache');

    $response = $this->get('/_assets/app.js?v=combined-123456');
    expect($response->getContent())->toBe('combined-contents');
});
