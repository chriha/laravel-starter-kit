<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

arch('only controllers in app controllers folder')
    ->expect('App\Http\Controllers')
    ->toBeClasses();

arch('controllers concerns are isolated')
    ->expect('App\Http\Concerns\Controllers')
    ->traits()
    ->toOnlyBeUsedIn('App\Http\Controllers');

arch('controllers have Controller suffix')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('controllers do not use facades')
    ->expect('Illuminate\Support\Facades')
    ->not->toBeUsedIn('App\Http\Controllers');

arch('controllers do not directly use models')
    ->expect('App\Http\Controllers')
    ->not->toUse('App\Models')
    ->ignoring(Model::class);
