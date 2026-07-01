<?php

declare(strict_types=1);

use Illuminate\Support\Collection;

use function App\is_empty;

test('is_empty treats countable with zero count as empty', function (): void {
    expect(is_empty(new Collection))->toBeTrue();
    expect(is_empty(new Collection(['a'])))->toBeFalse();
});

test('is_empty reads array key when key given', function (): void {
    expect(is_empty(['name' => ''], 'name'))->toBeTrue();
    expect(is_empty(['name' => 'value'], 'name'))->toBeFalse();
    expect(is_empty(['name' => 'value'], 'missing'))->toBeTrue();
});

test('is_empty treats null, empty string, and empty array as empty', function (): void {
    expect(is_empty(null))->toBeTrue();
    expect(is_empty(''))->toBeTrue();
    expect(is_empty([]))->toBeTrue();
});

test('is_empty treats other values as not empty', function (): void {
    expect(is_empty('value'))->toBeFalse();
    expect(is_empty(0))->toBeFalse();
    expect(is_empty(false))->toBeFalse();
});
