<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\FuncCall\FunctionFirstClassCallableRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamStringTypeFromSprintfUseRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Function_\AddFunctionVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withCache(
        cacheClass: FileCacheStorage::class,
        cacheDirectory: '/tmp/rector'
    )
    ->withImportNames(removeUnusedImports: true)
    ->withPaths([__DIR__.'/src', __DIR__.'/tests/src'])
    ->withRules([
        DeclareStrictTypesRector::class,
    ])
    ->withSkip([
        AddArrowFunctionReturnTypeRector::class,
        AddClosureVoidReturnTypeWhereNoReturnRector::class,
        AddFunctionVoidReturnTypeWhereNoReturnRector::class,
        AddParamStringTypeFromSprintfUseRector::class => [
            __DIR__.'/src/Admin/Traits/ControllerUtils.php',
        ],
        AddVoidReturnTypeWhereNoReturnRector::class => [
            __DIR__.'/src/Flame/Providers/EventServiceProvider.php',
        ],
        RemoveUselessParamTagRector::class => [
            __DIR__.'/src/Flame/Database/Concerns/HasRelationships.php',
        ],
        FunctionFirstClassCallableRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,
        IssetOnPropertyObjectToPropertyExistsRector::class,
        NewlineBeforeNewAssignSetRector::class,
    ])
    ->withPhpSets(php83: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
    );
