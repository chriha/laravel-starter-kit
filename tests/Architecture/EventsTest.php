<?php

declare(strict_types=1);

arch('event is immutable')->expect('App\Events')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();
