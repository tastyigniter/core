<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Pagic;

use Exception;
use Igniter\Flame\Pagic\Cache\FileSystem;
use Igniter\Flame\Pagic\Environment;
use Igniter\Flame\Pagic\Template;
use Igniter\Flame\Support\PagicHelper;
use Igniter\Main\Template\Page;

it('sets and gets debug mode correctly', function() {
    $environment = resolve(Environment::class);
    $environment->setDebug(true);

    expect($environment->getDebug())->toBeTrue();
    $environment->setDebug(false);
    expect($environment->getDebug())->toBeFalse();
});

it('sets and gets template class correctly', function() {
    $environment = resolve(Environment::class);
    $environment->setTemplateClass('NewTemplateClass');

    expect($environment->getTemplateClass())->toBe('NewTemplateClass');
});

it('sets and gets charset correctly', function() {
    $environment = resolve(Environment::class);
    $environment->setCharset('ISO-8859-1');

    expect($environment->getCharset())->toBe('ISO-8859-1');
});

it('sets and gets cache correctly', function() {
    $cache = mock(FileSystem::class);
    $environment = resolve(Environment::class);
    $environment->setCache($cache);

    expect($environment->getCache())->toBe($cache);
});

it('renders template correctly', function() {
    $source = Page::load('tests-theme', 'nested-page');
    $environment = resolve(Environment::class);
    $environment->getLoader()->setSource($source);
    $viewData = [
        'name' => 'World',
        'this' => [
            'page' => 'page',
            'layout' => 'layout',
            'theme' => 'theme',
            'param' => 'param',
            'controller' => 'controller',
            'session' => 'session',
        ],
        'htmlContent' => view('tests.admin::test'),
    ];
    $result = $environment->render('tests.admin::test', $viewData);
    expect($result)->toContain('This is a test view content');
});

it('throws exception when template path is invalid', function() {
    $environment = resolve(Environment::class);
    $template = new Template($environment, '/path/to/invalid/template');
    expect(fn() => $template->render())->toThrow(Exception::class);
});

it('renders source with provided variables', function() {
    $source = Page::load('tests-theme', 'nested-page');
    $environment = resolve(Environment::class);
    $environment->getLoader()->setSource($source);
    $result = $environment->renderSource($source, ['name' => 'World']);
    expect($result)->toContain('Test nested page content')
        ->and($result)->toContain($environment->renderSource($source));
});

it('creates template and returns template instance', function() {
    $environment = resolve(Environment::class);
    $template = $environment->createTemplate('tests.admin::test');
    expect($template)->toBeInstanceOf(Template::class);
});

it('adds and gets global variables correctly', function() {
    $environment = resolve(Environment::class);
    $environment->addGlobal('key', 'value');

    expect($environment->getGlobals())->toHaveKey('key', 'value');
});

it('merges globals with context correctly', function() {
    $environment = resolve(Environment::class);
    $environment->addGlobal('globalKey', 'globalValue');

    $context = ['contextKey' => 'contextValue'];
    $merged = $environment->mergeGlobals($context);
    expect($merged)->toHaveKey('globalKey', 'globalValue')
        ->and($merged)->toHaveKey('contextKey', 'contextValue');
});

it('parses blade contents with/without variables', function() {
    expect(PagicHelper::parse('Hello, {{ $name }}!', ['name' => 'John']))->toBe('Hello, John!')
        ->and(PagicHelper::parse('Hello, World!'))->toBe('Hello, World!');
});
