<?php

namespace Igniter\Tests\Flame\Currency\Formatters;

use Igniter\Flame\Currency\Formatters\PHPIntl;

it('formats currency correctly', function() {
    $formatter = new PHPIntl;

    expect($formatter->format(123.45, 'USD'))->toEqual('$123.45')
        ->and($formatter->format(123.45, 'EUR'))->toEqual('€123.45')
        ->and($formatter->format(123.45, 'GBP'))->toEqual('£123.45');
});

it('formats currency correctly with custom locale', function() {
    $formatter = new PHPIntl;

    expect($formatter->format(123.45, 'USD'))->toEqual('$123.45')
        ->and($formatter->format(123.45, 'EUR'))->toEqual('€123.45')
        ->and($formatter->format(123.45, 'GBP'))->toEqual('£123.45');
});

