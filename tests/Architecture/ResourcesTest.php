<?php

declare(strict_types=1);

use Illuminate\Http\Resources\Json\JsonResource;

arch('resources extend JsonResource')
    ->expect('App\Http\Resources')
    ->classes()
    ->toExtend(JsonResource::class);

arch('resources have Resource suffix')
    ->expect('App\Http\Resources')
    ->toHaveSuffix('Resource');

arch('resources do not use facades')
    ->expect('Illuminate\Support\Facades')
    ->not->toBeUsedIn('App\Http\Resources');
