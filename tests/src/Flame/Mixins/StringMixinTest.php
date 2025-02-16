<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Mixins;

use Igniter\Flame\Mixins\StringMixin;

it('converts number to ordinal form correctly', function() {
    $ordinal = (new StringMixin)->ordinal();
    expect($ordinal(1))->toBe('1st')
        ->and($ordinal(2))->toBe('2nd')
        ->and($ordinal(3))->toBe('3rd')
        ->and($ordinal(4))->toBe('4th')
        ->and($ordinal(11))->toBe('11th')
        ->and($ordinal(21))->toBe('21st')
        ->and($ordinal(22))->toBe('22nd')
        ->and($ordinal(23))->toBe('23rd')
        ->and($ordinal(101))->toBe('101st');
});

it('normalizes EOL characters to \\r\\n', function() {
    $normalizeEol = (new StringMixin)->normalizeEol();
    expect($normalizeEol("Line1\nLine2\r\nLine3\r"))->toBe("Line1\r\nLine2\r\nLine3\r\n");
});

it('normalizes class name by removing starting slash', function() {
    $normalizeClassName = (new StringMixin)->normalizeClassName();
    expect($normalizeClassName('\\Namespace\\ClassName'))->toBe('\\Namespace\\ClassName')
        ->and($normalizeClassName('Namespace\\ClassName'))->toBe('\\Namespace\\ClassName')
        ->and($normalizeClassName(new \stdClass))->toBe('\\stdClass');
});

it('generates class ID correctly', function() {
    $getClassId = (new StringMixin)->getClassId();
    expect($getClassId('\\Namespace\\ClassName'))->toBe('namespace_classname')
        ->and($getClassId('Namespace\\ClassName'))->toBe('namespace_classname')
        ->and($getClassId(new \stdClass))->toBe('stdclass');
});

it('returns class namespace correctly', function() {
    $getClassNamespace = (new StringMixin)->getClassNamespace();
    expect($getClassNamespace('\\Namespace\\ClassName'))->toBe('\\Namespace')
        ->and($getClassNamespace('Namespace\\SubNamespace\\ClassName'))->toBe('\\Namespace\\SubNamespace');
});

it('returns number of preceding symbols correctly', function() {
    $getPrecedingSymbols = (new StringMixin)->getPrecedingSymbols();
    expect($getPrecedingSymbols('***Test', '*'))->toBe(3)
        ->and($getPrecedingSymbols('##Test', '#'))->toBe(2)
        ->and($getPrecedingSymbols('Test', '#'))->toBe(0);
});
