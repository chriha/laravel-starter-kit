<?php

declare(strict_types=1);

arch('action is a class')
    ->expect('App\Actions')
    ->toBeClasses()
    ->ignoring('App\Actions\Concerns');

arch('action has handle method')
    ->expect('App\Actions')
    ->classes()
    ->toHaveMethod('handle');

arch('action does not use facades')
    ->expect('Illuminate\Support\Facades')
    ->not->toBeUsedIn('App\Actions')
    ->ignoring(['App\Actions\HealthChecks', 'App\Actions\Auth']);

arch('action does not directly interact with http')
    ->expect('App\Actions')
    ->not->toUse(['request', 'Illuminate\Http']);

arch('action extends nothing')
    ->expect('App\Actions')
    ->classes()
    ->toExtendNothing();
