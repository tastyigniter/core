<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes\MailBuilder;

use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\MailVariables;

it('parses registered variables', function() {
    $variables = new MailVariables([
        'igniter::system.mail_variables.text_group_order' => [
            '{{ $order_number }}' => 'Order number',
            '{{ $other_item[\'x\'] }}' => 'Item before any array',
            '$order_menus[]' => 'Menus',
            '{{ $order_menu[\'menu_name\'] }}' => 'Menu name',
            '{!! $order_menu[\'menu_options\'] !!}' => 'Menu options',
            '{{ $different[\'x\'] }}' => 'Item of another variable',
            '$empty_list[]' => 'No item fields',
            'not a token' => 'Ignored',
        ],
    ]);

    expect($variables->scalars())->toBe(['order_number' => 'Order number'])
        ->and($variables->isScalar('order_number'))->toBeTrue()
        ->and($variables->isArray('order_menus'))->toBeTrue()
        ->and($variables->isArray('empty_list'))->toBeFalse()
        ->and($variables->arrays())->toHaveKey('order_menus')
        ->and($variables->arrays())->not->toHaveKey('empty_list')
        ->and($variables->itemName('order_menus'))->toBe('order_menu')
        ->and($variables->hasItemField('order_menus', 'menu_name'))->toBeTrue()
        ->and($variables->hasItemField('order_menus', 'x'))->toBeFalse()
        ->and($variables->isRawItemField('order_menus', 'menu_options'))->toBeTrue()
        ->and($variables->isRawItemField('order_menus', 'menu_name'))->toBeFalse()
        ->and($variables->itemFields('order_menus'))->toHaveKeys(['menu_name', 'menu_options'])
        ->and($variables->groups()[0]['code'])->toBe('text-group-order')
        ->and($variables->groups()[0]['variables'])->toHaveCount(5);
});

it('scopes item fields to the loop being compiled', function() {
    $variables = new MailVariables(['Group' => [
        '$order_menus[]' => 'Menus',
        '{{ $order_menu[\'menu_name\'] }}' => 'Menu name',
        '{!! $order_menu[\'menu_options\'] !!}' => 'Menu options',
    ]]);
    $context = new CompileContext(CompileContext::MODE_TEMPLATE, $variables);

    expect($context->allowsItemField('order_menu', 'menu_name'))->toBeFalse()
        ->and($context->isRawItemField('order_menu', 'menu_options'))->toBeFalse();

    $context->enterLoop('order_menus', 'order_menu');

    expect($context->allowsItemField('order_menu', 'menu_name'))->toBeTrue()
        ->and($context->isRawItemField('order_menu', 'menu_options'))->toBeTrue()
        ->and($context->isMode(CompileContext::MODE_TEMPLATE))->toBeTrue()
        ->and($context->withPreview()->preview)->toBeTrue();

    $context->leaveLoop();

    expect($context->allowsItemField('order_menu', 'menu_name'))->toBeFalse()
        ->and($context->paramNames())->toBe([]);
});
