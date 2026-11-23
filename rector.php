<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/examples',
    ])
    ->withSkipPath(__DIR__ . '/tests/Storage')
    ->withSkip([
        // PHPStan strict rules require static assertion calls (self::assert*()).
        PreferPHPUnitThisCallRector::class,
    ])
    ->withPhpSets(php82: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
        phpunitCodeQuality: true,
    )
    ->withComposerBased(phpunit: true)
    ->withImportNames(removeUnusedImports: true);
