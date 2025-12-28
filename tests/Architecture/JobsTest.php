<?php

declare(strict_types=1);

use Illuminate\Contracts\Queue\ShouldQueue;

arch('jobs concerns are isolated')
    ->expect('App\Jobs\Concerns')
    ->traits()
    ->toOnlyBeUsedIn('App\Jobs');

arch('jobs implement ShouldQueue')
    ->expect('App\Jobs')
    ->classes()
    ->toImplement(ShouldQueue::class)
    ->ignoring('App\Jobs\Concerns');

arch('jobs have Job suffix')
    ->expect('App\Jobs')
    ->toHaveSuffix('Job')
    ->ignoring('App\Jobs\Concerns');

arch('jobs do not use facades')
    ->expect('Illuminate\Support\Facades')
    ->not->toBeUsedIn('App\Jobs');
