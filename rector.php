<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/public',
        __DIR__ . '/resources',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    // in case we run into a timeout or memory issue, we can either increase
    // the memory_limit (--memory-limit=1G) or prevent running in parallel
    // ->withoutParallel()
    ->withPhpSets(php84: true)
    ->withSkip([
        NullToStrictStringFuncCallArgRector::class,
    ])
    // ->withConfiguredRule(DeclareStrictTypesRector::class)
    ->withRules([
        DeclareStrictTypesRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        earlyReturn: true,
    );
