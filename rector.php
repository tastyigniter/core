<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNewArrayRector;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/src', __DIR__.'/tests'])
    ->withSkip([
        ReturnTypeFromStrictNewArrayRector::class,
    ])
    ->withTypeCoverageLevel(1)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
