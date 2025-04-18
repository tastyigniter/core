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
