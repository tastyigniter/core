<?php

namespace Igniter\Tests\Main\Template;

use Igniter\Main\Classes\Theme;
use Igniter\Main\Template\ComponentPartial;
use Igniter\Tests\Fixtures\Actions\TestControllerAction;
use Igniter\Tests\System\Fixtures\TestComponent;

it('initializes correctly', function() {
    $componentPartial = new ComponentPartial('source/path');
    expect($componentPartial->getDefaultExtension())->toBe('blade.php')
        ->and($componentPartial->getTemplateCacheKey())->toBeString();
});

it('extends widget class with action class', function() {
    ComponentPartial::implement(TestControllerAction::class);
    $componentPartial = new class('source/path') extends ComponentPartial
    {
        public array $implement = [TestControllerAction::class];
    };

    expect($componentPartial::testStaticFunction())->toBe('staticResult');
    ComponentPartial::clearExtendedClasses();
});

it('loads component partial successfully', function() {
    $componentPartial = ComponentPartial::loadCached('igniter.tests::views/_components/testcomponent', 'default');

    expect($componentPartial)->not->toBeNull()
        ->and($componentPartial->getFileName())->toBe('default.blade.php')
        ->and($componentPartial->getContent())->toContain('This is a test component partial content')
        ->and($componentPartial->getMarkup())->toContain("This is a test component partial content")
        ->and($componentPartial->getCode())->toBe('missing-code');

});

it('returns null when component partial file does not exist', function() {
    expect(ComponentPartial::load('source/path', 'nonexistent.blade.php'))->toBeNull();
});

it('loads component partial from override path', function() {
    $componentPartial = ComponentPartial::loadOverrideCached(
        new Theme(testThemePath(), ['code' => 'tests-theme']),
        'testComponent',
        'default',
    );

    expect($componentPartial)->not->toBeNull()
        ->and($componentPartial->getFileName())->toBe('testcomponent/default.blade.php');
});

it('checks if component partial exists', function() {
    expect(ComponentPartial::check(new TestComponent(), 'default'))->toBeTrue();
});

it('returns false if component partial does not exist', function() {
    expect(ComponentPartial::check(new TestComponent(), 'nonexistent.blade.php'))->toBeFalse();
});

it('returns correct file path for component partial', function() {
    $partial = new ComponentPartial((new TestComponent())->getPath());

    expect($partial->getFilePath('default.blade.php'))->toEndWith('_components/testcomponent/default.blade.php');
});

it('returns shared partial file path if not found in component path', function() {
    $partial = new ComponentPartial((new TestComponent())->getPath());

    expect($partial->getFilePath('test-partial'))->toEndWith('_partials/test-partial.blade.php');
});

it('returns base file name without extension', function() {
    $partial = new ComponentPartial('source/path');
    $partial->fileName = 'partial.blade.php';
    expect($partial->getBaseFileName())->toBe('partial.blade');

    $partial->fileName = 'partial';
    expect($partial->getBaseFileName())->toBe('partial')
        ->and($partial->getFilePath())->toBeString();
});
