<?php

declare(strict_types=1);

arch('exceptions only in exceptions folder')
    ->expect('App')
    ->classes()
    ->not->toExtend(Exception::class)
    ->ignoring('App\Exceptions');
