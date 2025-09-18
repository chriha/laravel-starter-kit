<?php

namespace App\Enums\Concerns;

use BackedEnum;

/**
 * @mixin BackedEnum
 */
trait Conditionable
{
    public function is(self|string|null $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return $this === self::from($value);
        }

        return $this === $value;
    }

    public function isNot(self|string|null $value): bool
    {
        return ! $this->is($value);
    }

    public function isAny(self|string|null ...$values): bool
    {
        foreach ($values as $value) {
            if (is_null($value)) {
                continue;
            }

            $enum = $value instanceof self ? $value : self::from($value);

            if ($this->is($enum)) {
                return true;
            }
        }

        return false;
    }
}
