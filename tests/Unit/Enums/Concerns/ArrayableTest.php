<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
 */

declare(strict_types=1);

use App\Enums\Concerns\Arrayable;
use Illuminate\Support\Collection;

// Test enum local to this test file
enum ArrayableSample: string
{
    use Arrayable;

    case ALPHA = 'alpha';
    case BETA = 'beta';
    case GAMMA = 'gamma';
}

it('converts enum cases to a simple values array', function (): void {
    expect(ArrayableSample::toArray())
        ->toBe(['alpha', 'beta', 'gamma']);
});

it('returns a collection of enum cases', function (): void {
    $collection = ArrayableSample::collection();

    expect($collection)
        ->toBeInstanceOf(Collection::class)
        ->and($collection->all())
        ->toBe(ArrayableSample::cases());
});
