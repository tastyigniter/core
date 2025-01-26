<?php

namespace Igniter\Tests\System\Helpers;

use Igniter\System\Facades\Assets;
use Illuminate\Http\Response;

it('returns combined contents of assets', function() {
    Assets::shouldReceive('combineGetContents')->with('combined')->once()->andReturn(new Response('combined-contents'));

    $response = $this->get('/_assets/combined-cache-key');
    expect($response->getContent())->toBe('combined-contents');
});
