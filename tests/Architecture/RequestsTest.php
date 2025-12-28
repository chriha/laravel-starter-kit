<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\FormRequest;

arch('requests extend FormRequest')
    ->expect('App\Http\Requests')
    ->classes()
    ->toExtend(FormRequest::class);

arch('requests have Request suffix')
    ->expect('App\Http\Requests')
    ->toHaveSuffix('Request');

arch('requests have rules method')
    ->expect('App\Http\Requests')
    ->classes()
    ->toHaveMethod('rules');

arch('requests do not use facades')
    ->expect('Illuminate\Support\Facades')
    ->not->toBeUsedIn('App\Http\Requests');
