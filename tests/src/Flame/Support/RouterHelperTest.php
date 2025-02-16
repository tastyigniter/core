<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Support;

use Igniter\Flame\Support\RouterHelper;

it('adds leading slash and removes trailing slash from url', function() {
    expect(RouterHelper::normalizeUrl('example/path/'))->toBe('/example/path')
        ->and(RouterHelper::normalizeUrl(''))->toBe('/')
        ->and(RouterHelper::rebuildUrl(['example', 'path', 'to', 'resource']))->toBe('/example/path/to/resource');
});
it('replaces column names with object/array values in url template', function() {
    $object = (object)['id' => 1, 'name' => 'Joe'];
    expect(RouterHelper::parseValues($object, ['id', 'name', 'foo-column'], '/user/:id/:name'))->toBe('/user/1/Joe');

    $object = ['id' => 1, 'name' => 'Joe'];
    expect(RouterHelper::parseValues($object, ['id', 'name'], '/user/:id/:name'))->toBe('/user/1/Joe');
});

it('replaces parameters in url template', function() {
    $object = (object)['id' => 1, 'name' => 'Joe'];
    expect(RouterHelper::replaceParameters($object, '/user/:id/:name'))->toBe('/user/1/Joe')
        ->and(RouterHelper::replaceParameters($object, '/user/'))->toBe('/user/')
        ->and(RouterHelper::getParameterName(':id?*'))->toBe('id')
        ->and(RouterHelper::getParameterName(':id*'))->toBe('id')
        ->and(RouterHelper::getParameterName(':id|[0-9]+?'))->toBe('id')
        ->and(RouterHelper::getParameterName(':id?|[0-9]+'))->toBe('id')
        ->and(RouterHelper::getParameterName(':id?'))->toBe('id');
});

it('checks for route segment', function() {
    expect(RouterHelper::segmentizeUrl('/example/path/to/resource'))
        ->toBe(['example', 'path', 'to', 'resource']) // Split into array
        ->and(RouterHelper::segmentizeUrl('/'))->toBe([]) // Root
        ->and(RouterHelper::segmentIsWildcard(':id*'))->toBeTrue() // Wildcard
        ->and(RouterHelper::segmentIsWildcard(':id'))->toBeFalse() // Non-wildcard
        ->and(RouterHelper::segmentIsOptional(':id?'))->toBeTrue() // Optional
        ->and(RouterHelper::segmentIsOptional(':id?|[0-9]+'))->toBeTrue() // Optional
        ->and(RouterHelper::segmentIsOptional(':id|[0-9]+?'))->toBeFalse() // Optional
        ->and(RouterHelper::segmentIsOptional(':id'))->toBeFalse() // Non-optional
        ->and(RouterHelper::getSegmentRegExp(':id|[0-9]+'))->toBe('/[0-9]+/') // Regex
        ->and(RouterHelper::getSegmentRegExp(':id|'))->toBeFalse() // Non-regex
        ->and(RouterHelper::getSegmentRegExp(':id'))->toBeFalse() // Non-regex
        ->and(RouterHelper::getSegmentDefaultValue(':id?default'))->toBe('default') // Default value
        ->and(RouterHelper::getSegmentDefaultValue(':id?default|[a-z]+'))->toBe('default') // Default value
        ->and(RouterHelper::getSegmentDefaultValue(':id?default*'))->toBe('default') // Default value
        ->and(RouterHelper::getSegmentDefaultValue(':id'))->toBeFalse();  // Non-default value

});

it('converts page info to route properties', function() {
    $pageInfo = ['pattern' => '/user/:id|[0-9]+/:name?default/:foo*'];
    expect(RouterHelper::convertToRouteProperties($pageInfo))->toBe([
        'pattern' => '/user/:id|[0-9]+/:name?default/:foo*',
        'uri' => '/user/{id}/{name?}/{foo}',
        'defaults' => ['name' => 'default'],
        'constraints' => ['id' => '/[0-9]+/', 'foo' => '.*'],
    ]);
});
