<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\ArrowFunction\StaticArrowFunctionRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database/migrations',
        __DIR__ . '/database/seeders',
        __DIR__ . '/public',
        __DIR__ . '/resources',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    // in case we run into a timeout or memory issue, we can either increase
    // the memory_limit (--memory-limit=1G) or prevent running in parallel
    // ->withoutParallel()
    // ->withSetProviders(
    //    LaravelSetProvider::class,
    // )
    ->withPhpSets(php84: true)
    ->withSkip([
        ClosureToArrowFunctionRector::class,
        EncapsedStringsToSprintfRector::class,
        NullToStrictStringFuncCallArgRector::class,
    ])
    // ->withConfiguredRule(DeclareStrictTypesRector::class)
    ->withRules([
        DeclareStrictTypesRector::class,
        StaticArrowFunctionRector::class,
    ])
    ->withImportNames(
        removeUnusedImports: true,
    )
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        earlyReturn: true,
    );
