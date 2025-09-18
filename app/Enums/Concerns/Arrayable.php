<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

use BackedEnum;
use Illuminate\Support\Collection;

/**
 * @mixin BackedEnum
 */
trait Arrayable
{
    /**
     * @return array<int, string>
     */
    public static function toArray(): array
    {
        return collect(static::cases())
            ->map(fn (BackedEnum $case) => $case->value)
            ->toArray();
    }

    public static function collection(): Collection
    {
        return collect(static::cases());
    }
}
