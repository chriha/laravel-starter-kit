<?php

declare(strict_types=1);

arch('only enums in enums folder')
    ->expect('App\Enums')
    ->toBeEnums()
    ->ignoring('App\Enums\Concerns');
