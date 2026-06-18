<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes;

use Igniter\Flame\Exception\MarketplaceException;
use Igniter\System\Classes\MarketplaceErrorPresenter;

it('returns translated message for known marketplace error codes', function(): void {
    $message = MarketplaceErrorPresenter::message(new MarketplaceException(
        'Raw API message',
        'installation_mismatch',
    ));

    expect($message)->toBe(lang('igniter::system.updates.marketplace_errors.installation_mismatch'));
});

it('falls back to the exception message for unknown error codes', function(): void {
    $message = MarketplaceErrorPresenter::message(new MarketplaceException(
        'Something went wrong',
        'unknown_error',
    ));

    expect($message)->toBe('Something went wrong');
});
