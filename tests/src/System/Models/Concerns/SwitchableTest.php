<?php

namespace Igniter\Tests\System\Models\Concerns;

use Igniter\System\Models\Concerns\Switchable;
use Igniter\System\Models\Page;

it('returns correct switchable column when defined', function() {
    $model = new class
    {
        use Switchable;

        const SWITCHABLE_COLUMN = 'custom_status';
    };

    $column = $model->switchableGetColumn();

    expect($column)->toBe('custom_status');
});

it('returns default switchable column when not defined', function() {
    $model = new class
    {
        use Switchable;
    };

    $column = $model->switchableGetColumn();

    expect($column)->toBe('status');
});

it('checks if model is enabled', function() {
    $model = new class
    {
        use Switchable;

        public $status = true;
    };

    $isEnabled = $model->isEnabled();

    expect($isEnabled)->toBeTrue();
});

it('checks if model is disabled', function() {
    $model = new class
    {
        use Switchable;

        public $status = false;
    };

    $isDisabled = $model->isDisabled();

    expect($isDisabled)->toBeTrue();
});

it('applies scope to get enabled models', function() {
    expect(Page::query()->isEnabled()->toSql())
        ->toContain('where `pages`.`status` is not null and `pages`.`status` = ?');
});

it('applies scope to get disabled models', function() {
    expect(Page::whereIsDisabled()->toSql())->toContain('where `pages`.`status` != ?');
});

it('applies switchable scope correctly', function() {
    expect(Page::applySwitchable(true)->toRawSql())->toContain('where `pages`.`status` = 1');
    expect(Page::applySwitchable(false)->toRawSql())->toContain('where `pages`.`status` = 0');
});
