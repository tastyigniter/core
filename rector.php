<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector;
use Rector\CodeQuality\Rector\Ternary\SwitchNegatedTernaryRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StrictStringParamConcatRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Function_\AddFunctionVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withCache(
        cacheClass: FileCacheStorage::class,
        cacheDirectory: '/tmp/rector'
    )
    ->withImportNames(removeUnusedImports: true)
    ->withPaths([__DIR__.'/src', __DIR__.'/tests'])
    ->withRules([
        DeclareStrictTypesRector::class,
    ])
    ->withSkip([
        AddArrowFunctionReturnTypeRector::class,
        AddClosureVoidReturnTypeWhereNoReturnRector::class,
        AddFunctionVoidReturnTypeWhereNoReturnRector::class,
        AddVoidReturnTypeWhereNoReturnRector::class => [
            __DIR__.'/src/Flame/Providers/EventServiceProvider.php',
        ],
        DisallowedEmptyRuleFixerRector::class,
        RemoveUselessParamTagRector::class => [
            __DIR__.'/src/Flame/Database/Concerns/HasRelationships.php',
        ],
        SimplifyEmptyCheckOnEmptyArrayRector::class,
        StrictStringParamConcatRector::class => [
            __DIR__.'/src/Flame/Database/Concerns/HasRelationships.php',
        ],
        SwitchNegatedTernaryRector::class,
        IssetOnPropertyObjectToPropertyExistsRector::class,
    ])
    ->withPhpSets(php83: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
    );
