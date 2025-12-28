<?php

declare(strict_types=1);

arch('models should not depend on controllers')
    ->expect('App\Models')
    ->not->toUse('App\Http\Controllers');

arch('models should not depend on jobs')
    ->expect('App\Models')
    ->not->toUse('App\Jobs');

arch('services should not depend on controllers')
    ->expect('App\Services')
    ->not->toUse('App\Http\Controllers');
