<?php

namespace Igniter\Tests\System\Classes;

use Igniter\System\Classes\PackageInfo;

it('creates PackageInfo instance from array', function() {
    $data = [
        'code' => 'test-code',
        'package' => 'test-package',
        'type' => 'test-type',
        'name' => 'test-name',
        'version' => '1.0.0',
        'author' => 'test-author',
    ];

    $packageInfo = PackageInfo::fromArray($data);

    expect($packageInfo->code)->toBe('test-code')
        ->and($packageInfo->package)->toBe('test-package')
        ->and($packageInfo->type)->toBe('test-type')
        ->and($packageInfo->name)->toBe('test-name')
        ->and($packageInfo->version)->toBe('1.0.0')
        ->and($packageInfo->author)->toBe('test-author')
        ->and($packageInfo->description)->toBe('')
        ->and($packageInfo->icon)->toBe([])
        ->and($packageInfo->installedVersion)->toBe('')
        ->and($packageInfo->publishedAt)->toBe('')
        ->and($packageInfo->tags)->toBe([])
        ->and($packageInfo->hash)->toBe('')
        ->and($packageInfo->updatedAt)->toBe('')
        ->and($packageInfo->homepage)->toBe('')
        ->and($packageInfo->isCore())->toBeFalse();
});

it('returns default value if icon key does not exist and no default provided', function() {
    $packageInfo = new PackageInfo('test-code', 'test-package', 'test-type', 'test-name', '1.0.0', icon: ['icon-key' => 'icon-value']);

    expect($packageInfo->icon('non-existent-key', ''))->toBe('');
});

it('returns empty string if no changelog tag exists and no description provided', function() {
    $packageInfo = new PackageInfo('test-code', 'test-package', 'test-type', 'test-name', '1.0.0', tags: ['data' => []]);

    expect($packageInfo->changeLog())->toBe('');
});

it('returns changelog if tag exists', function() {
    $packageInfo = new PackageInfo('test-code', 'test-package', 'test-type', 'test-name', '1.0.0', tags: [
        'data' => [
            ['name' => 'tag1', 'description' => 'tag **description**'],
            ['name' => 'tag2', 'description' => ''],
        ],
    ]);

    expect((string)$packageInfo->changeLog())->toBe("<p>tag <strong>description</strong></p>\n");
});

it('returns formatted published date with custom format', function() {
    $packageInfo = new PackageInfo('test-code', 'test-package', 'test-type', 'test-name', '1.0.0', publishedAt: '2023-01-01');

    expect($packageInfo->publishedAt())->toBe('01 Jan 2023');
});
