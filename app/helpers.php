<?php

declare(strict_types=1);

namespace App;

use Countable;

if (! function_exists('is_empty')) {
    function is_empty(mixed $value, string|int|null $key = null): bool
    {
        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        if (is_array($value) && $key !== null) {
            $value = $value[$key] ?? null;
        }

        return in_array($value, [null, '', []], true);
    }
}
