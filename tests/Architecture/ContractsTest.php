<?php

declare(strict_types=1);

arch('only interfaces in contracts folder')
    ->expect('App\Contracts')
    ->toBeInterface();

arch('no interfaces in app except in contracts folder')
    ->expect('App')
    ->not->toBeInterfaces()
    ->ignoring('App\Contracts');
