<?php

declare(strict_types=1);

arch('service it is class')
    ->expect('App\Services')
    ->toBeClasses()
    ->ignoring('App\Services\Concerns');

arch('service have Service suffix')
    ->expect('App\Services')
    ->toHaveSuffix('Service')
    ->ignoring('App\Services\Concerns');

arch('service does not directly interact with http')
    ->expect('App\Services')
    ->not->toUse(['request', 'Illuminate\Http']);

arch('service extends nothing')
    ->expect('App\Services')
    ->classes()
    ->toExtendNothing();
